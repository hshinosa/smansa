# Docker Hosting Guide (Production)

Dokumen ini adalah panduan deploy komprehensif untuk SMANSA Web dengan Docker agar stabil dan minim bug.

## 1) Prasyarat Server

- Docker Engine 24+
- Docker Compose v2
- Minimal 2 vCPU, 4GB RAM (disarankan 8GB untuk beban AI/queue)
- Domain sudah mengarah ke server

## 2) File yang Dipakai

- `docker-compose.prod.yml`
- `docker/nginx/nginx.conf`
- `docker/nginx/sites/production.conf`
- `.env.production` (buat dari `.env.production.example`)

## 3) Setup Environment

```bash
cp .env.production.example .env.production
```

Wajib isi:

- `APP_KEY` (generate di langkah bootstrap)
- `APP_URL`
- `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `GROQ_API_KEY` (jika dipakai)

## 4) Bootstrap Pertama Kali

### A. Build image dan start service

```bash
docker compose --env-file .env.production -f docker-compose.prod.yml up -d --build
```

### B. Generate APP_KEY

```bash
docker compose --env-file .env.production -f docker-compose.prod.yml exec app php artisan key:generate --force
```

Lalu copy value `APP_KEY=...` ke `.env.production` dan restart app:

```bash
docker compose --env-file .env.production -f docker-compose.prod.yml up -d app queue scheduler
```

### C. Jalankan migration

```bash
docker compose --env-file .env.production -f docker-compose.prod.yml exec app php artisan migrate --force
```

## 5) Operasional Harian

### Start/Stop

```bash
docker compose --env-file .env.production -f docker-compose.prod.yml up -d
docker compose --env-file .env.production -f docker-compose.prod.yml down
```

### Cek status

```bash
docker compose --env-file .env.production -f docker-compose.prod.yml ps
docker compose --env-file .env.production -f docker-compose.prod.yml logs -f app
docker compose --env-file .env.production -f docker-compose.prod.yml logs -f nginx
docker compose --env-file .env.production -f docker-compose.prod.yml logs -f queue
```

### Health check cepat

```bash
curl -f http://localhost/health
```

## 6) Update Deploy

```bash
git pull
docker compose --env-file .env.production -f docker-compose.prod.yml up -d --build
docker compose --env-file .env.production -f docker-compose.prod.yml exec app php artisan migrate --force
```

## 7) Troubleshooting Anti-Bug

1. **Queue tidak jalan**
   - Cek: `docker compose --env-file .env.production -f docker-compose.prod.yml logs -f queue`
   - Pastikan `QUEUE_CONNECTION=database` dan tabel jobs sudah ada.

2. **Session/cache error**
   - Pastikan Redis healthy dan env:
     - `SESSION_DRIVER=redis`
     - `CACHE_STORE=redis`
     - `REDIS_HOST=redis`

3. **Upload file tidak muncul**
   - Jalankan: `docker compose --env-file .env.production -f docker-compose.prod.yml exec app php artisan storage:link`
   - Pastikan service `nginx` mount `./storage:/var/www/storage:ro`.

4. **500 saat boot pertama**
   - Cek `APP_KEY` sudah valid.
   - Jalankan ulang cache:
     ```bash
     docker compose --env-file .env.production -f docker-compose.prod.yml exec app php artisan optimize:clear
     docker compose --env-file .env.production -f docker-compose.prod.yml exec app php artisan config:cache
     ```

5. **Embedding tidak reachable**
   - Cek logs `embedding`
   - Pastikan env `EMBEDDING_BASE_URL=http://embedding:8080`

## 8) Security Baseline

- Jangan expose port DB/Redis ke publik (compose prod ini sudah tidak publish keduanya).
- Gunakan password DB kuat.
- Simpan `.env.production` di server, jangan commit.
- Pasang TLS di reverse proxy edge/server (Nginx/container LB).

## 9) Rekomendasi Backup

- Database: dump harian + retain minimal 7 hari.
- Storage: backup folder `storage/app/public` harian.
- Uji restore secara berkala.
