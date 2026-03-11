#!/bin/bash
# deploy-from-dockerhub.sh
# Deploy dari Docker Hub image yang sudah di-build

set -e

echo "🚀 Deploy SMAN 1 Baleendah dari Docker Hub"
echo "==========================================="
echo ""

APP_DIR="/var/www/smansa"
STORAGE_DIR="/var/data/smansa"

# Pull Docker image
echo [1/5] Pulling Docker image...
docker pull hshinosa/smansa-web:latest
echo        ✓ Image pulled
echo

# Create storage directory
echo [2/5] Creating storage directory...
mkdir -p $STORAGE_DIR/{media,uploads,documents,backups,temp}
chmod -R 755 $STORAGE_DIR
echo        ✓ Storage ready
echo

# Setup environment
echo [3/5] Setting up environment...
cd $APP_DIR
if [ ! -f .env ]; then
    cp .env.example .env
fi

# Update .env with production settings
sed -i 's/APP_ENV=local/APP_ENV=production/g' .env
sed -i 's/APP_DEBUG=true/APP_DEBUG=false/g' .env
sed -i 's|APP_URL=.*|APP_URL=http://'"$(curl -s ifconfig.me)"'|g' .env
sed -i 's/DB_HOST=127.0.0.1/DB_HOST=db/g' .env
sed -i 's/DB_DATABASE=laravel/DB_DATABASE=sman1_baleendah/g' .env
sed -i 's/DB_USERNAME=root/DB_USERNAME=sman1_user/g' .env
sed -i 's/CACHE_STORE=file/CACHE_STORE=redis/g' .env
sed -i 's/SESSION_DRIVER=database/SESSION_DRIVER=redis/g' .env
sed -i 's/REDIS_HOST=127.0.0.1/REDIS_HOST=redis/g' .env
echo        ✓ Environment configured
echo

# Start containers
echo [4/5] Starting containers...
docker-compose -f docker-compose.storage.yml up -d db redis
sleep 10
docker-compose -f docker-compose.storage.yml up -d app nginx queue
echo        ✓ Containers started
echo

# Setup Laravel
echo [5/5] Setting up Laravel...
docker-compose -f docker-compose.storage.yml exec -T app php artisan key:generate --force
docker-compose -f docker-compose.storage.yml exec -T app php artisan storage:link
docker-compose -f docker-compose.storage.yml exec -T app php artisan migrate --force
docker-compose -f docker-compose.storage.yml exec -T app php artisan config:cache
docker-compose -f docker-compose.storage.yml exec -T app php artisan route:cache
docker-compose -f docker-compose.storage.yml exec -T app php artisan view:cache
echo        ✓ Laravel ready
echo

echo ===================================
echo ✅ Deploy Complete!
echo ===================================
echo
echo Website: http://$(curl -s ifconfig.me)
echo
echo Commands:
echo   cd $APP_DIR
echo   docker-compose ps
echo   docker-compose logs -f app
echo
