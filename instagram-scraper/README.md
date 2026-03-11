# Instagram Scraper for SMAN 1 Baleendah

Python-based Instagram scraper untuk otomasi pengambilan konten Instagram dan konversi ke berita website sekolah.

## 🎯 Features

- ✅ **Session Management**: Login sekali, reuse session (anti-ban)
- ✅ **Duplicate Detection**: Otomatis skip post yang sudah di-scrape
- ✅ **Database Integration**: Langsung simpan ke PostgreSQL production
- ✅ **Human-like Behavior**: Random delay 10-20 detik antar request
- ✅ **Error Handling**: Auto-deactivate bot account jika kena ban
- ✅ **Prefix Naming**: Table `sc_*` untuk isolasi dari Laravel tables

## 📦 Installation

### 1. Install Python Dependencies

```bash
cd instagram-scraper
pip install -r requirements.txt
```

### 2. Configure Environment

```bash
# Copy .env template
cp .env.example .env

# Edit .env dengan kredensial database
# (Seharusnya sudah sama dengan Laravel .env)
```

### 3. Setup Database

```bash
# Buat tables scraper (sc_bot_accounts, sc_raw_news_feeds)
python setup_db.py
```

Output:
```
✅ Database connection successful!
✓ Table 'sc_bot_accounts' created/verified
✓ Table 'sc_raw_news_feeds' created/verified
✅ Placeholder account created
```

### 4. Configure Bot Account

Update bot account dengan kredensial Instagram asli:

```sql
-- Via psql atau DBeaver
UPDATE sc_bot_accounts
SET 
    username = 'your_instagram_username',
    password = 'your_instagram_password',
    is_active = true
WHERE username = 'CHANGE_ME';
```

**⚠️ PENTING:**
- Gunakan akun Instagram dummy/burner, bukan akun utama
- Akun harus non-2FA atau gunakan app-specific password
- Jangan scrape terlalu agresif (max 50-100 post/hari)

## 🚀 Usage

### Basic Scraping

```bash
# Scrape Instagram profile
python scraper.py --target jokowi

# Limit jumlah post
python scraper.py --target sman1baleendah --max-posts 20
```

### Check Scraped Data

```sql
-- Lihat semua post yang sudah di-scrape
SELECT * FROM sc_raw_news_feeds ORDER BY scraped_at DESC;

-- Lihat post yang belum diproses AI
SELECT * FROM sc_raw_news_feeds WHERE is_processed = false;

-- Count pending posts
SELECT COUNT(*) FROM sc_raw_news_feeds WHERE is_processed = false;
```

### Mark Post as Processed (setelah AI generate berita)

```sql
UPDATE sc_raw_news_feeds
SET is_processed = true, processed_at = NOW()
WHERE post_shortcode = 'ABC123xyz';
```

## 📁 File Structure

```
instagram-scraper/
├── models.py           # SQLAlchemy database models
├── setup_db.py         # Database initialization script
├── scraper.py          # Main scraper logic
├── requirements.txt    # Python dependencies
├── .env.example        # Environment template
├── .env                # Actual config (gitignored)
├── downloads/          # Downloaded images (gitignored)
│   └── {username}/     # Per-user folders
└── session-*           # Session files (gitignored)
```

## 🗄️ Database Schema

### Table: `sc_bot_accounts`

| Column | Type | Description |
|--------|------|-------------|
| id | Integer | Primary key |
| username | String(100) | Instagram username (unique) |
| password | String(255) | Instagram password |
| is_active | Boolean | Active status (auto-disable on ban) |
| last_used_at | DateTime | Last scraping timestamp |
| notes | Text | Optional notes |

### Table: `sc_raw_news_feeds`

| Column | Type | Description |
|--------|------|-------------|
| id | Integer | Primary key |
| **post_shortcode** | String(50) | Instagram post ID (unique) |
| source_username | String(100) | Original poster username |
| caption | Text | Post caption |
| **image_paths** | JSON | Array of image file paths |
| likes_count | Integer | Likes count |
| comments_count | Integer | Comments count |
| post_date | DateTime | Original post date |
| scraped_at | DateTime | When scraped |
| **is_processed** | Boolean | AI processing flag |
| processed_at | DateTime | When AI processed |
| error_message | Text | Error log if any |

## 🔄 Workflow Integration

```
1. [Python] Scrape Instagram → sc_raw_news_feeds (is_processed=false)
2. [Laravel] Cron job check pending feeds
3. [AI] Generate article via ContentCreationService
4. [Laravel] Create draft Post
5. [Laravel] Mark feed as processed (is_processed=true)
6. [Admin] Review & publish draft
```

## ⚠️ Anti-Ban Best Practices

1. **Use Session Files**: Login disimpan di `session-{username}`, reuse untuk request berikutnya
2. **Random Delays**: 10-20 detik antar request (sudah implemented)
3. **Limit Requests**: Max 50 post per run, jangan terlalu sering
4. **Rotate Accounts**: Gunakan multiple bot accounts jika perlu
5. **Monitor Rate Limits**: Script auto-pause 60s jika kena rate limit
6. **Use Proxy** (optional): Tambahkan proxy rotation untuk extra safety

## 🐛 Troubleshooting

### Error: "No active bot accounts found"

```bash
# Check bot accounts
python setup_db.py

# Update credentials manually via SQL
psql -h 127.0.0.1 -p 5433 -U sman1_user -d sman1_baleendah
```

### Error: "2FA required"

- Disable 2FA di Instagram settings
- Atau gunakan app-specific password

### Error: "Connection error"

Kemungkinan:
- Rate limit dari Instagram
- IP kena temporary ban
- Session expired (delete `session-*` files dan login ulang)

### Error: "Database connection failed"

```bash
# Check PostgreSQL running
docker-compose ps

# Check credentials di .env
cat .env
```

## 📊 Monitoring

### Check Bot Account Status

```sql
SELECT 
    username,
    is_active,
    last_used_at,
    notes
FROM sc_bot_accounts
ORDER BY last_used_at DESC;
```

### Scraping Statistics

```sql
SELECT 
    source_username,
    COUNT(*) as total_posts,
    SUM(CASE WHEN is_processed THEN 1 ELSE 0 END) as processed,
    MAX(scraped_at) as last_scrape
FROM sc_raw_news_feeds
GROUP BY source_username
ORDER BY last_scrape DESC;
```

## 🔒 Security Notes

- **Never commit** `.env` or `session-*` files
- **Never use** personal Instagram accounts
- **Store passwords** encrypted jika production
- **Rotate bot accounts** regularly
- **Monitor for bans** daily

## 📝 Maintenance

### Clear Old Data

```sql
-- Delete processed feeds older than 30 days
DELETE FROM sc_raw_news_feeds
WHERE is_processed = true
AND processed_at < NOW() - INTERVAL '30 days';

-- Archive images manually
rm -rf downloads/*/2024-*
```

### Reset Database

```bash
# WARNING: Destructive! Deletes all scraper data
python setup_db.py --reset
```

## 🚦 Rate Limits

Instagram unofficial rate limits (approximate):
- **Requests/hour**: ~200 (dengan session)
- **Posts/day**: ~500 (distribute sepanjang hari)
- **Follows/day**: ~200
- **Likes/day**: ~300

Script ini default safe: 50 posts dengan delay 10-20s = ~15 menit/run.

## 📞 Support

Jika ada error atau butuh custom feature:
1. Check dokumentasi di `docs/PYSCRAPER_DEVELOP.md`
2. Review Laravel integration di `app/Services/ContentCreationService.php`
3. Test dengan small target profile dulu

## ⚡ Quick Reference

```bash
# Setup (one-time)
pip install -r requirements.txt
python setup_db.py
# Update credentials in DB

# Daily usage
python scraper.py --target sman1baleendah --max-posts 20

# Check results
psql -h 127.0.0.1 -p 5433 -U sman1_user -d sman1_baleendah \
  -c "SELECT * FROM sc_raw_news_feeds WHERE is_processed=false;"
```

## 📄 License

Internal tool for SMAN 1 Baleendah website automation.
