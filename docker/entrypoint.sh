#!/bin/sh
set -e

echo "==> Starting SMAN1 Baleendah Application..."

# Prepare writable directories
echo "==> Preparing writable directories..."
mkdir -p /var/www/storage/app/public
mkdir -p /var/www/storage/framework/cache /var/www/storage/framework/sessions /var/www/storage/framework/views
mkdir -p /var/www/storage/logs /var/www/bootstrap/cache

chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true

# Create storage link if not exists
if [ ! -e "/var/www/public/storage" ]; then
    echo "==> Creating storage symlink..."
    php artisan storage:link 2>/dev/null || true
fi

# Clear and cache config in production
if [ "$APP_ENV" = "production" ]; then
    echo "==> Refreshing Laravel caches..."
    php artisan config:clear 2>/dev/null || true
    php artisan route:clear 2>/dev/null || true
    php artisan view:clear 2>/dev/null || true
    php artisan event:clear 2>/dev/null || true

    php artisan config:cache 2>/dev/null || true
    php artisan route:cache 2>/dev/null || true
    php artisan view:cache 2>/dev/null || true
    php artisan event:cache 2>/dev/null || true
fi

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    echo "==> Running migrations..."
    php artisan migrate --force
fi

echo "==> Application ready!"

# Let PHP-FPM handle user switching (www pool config already sets user=www-data)
# Don't switch user here as it causes permission issues with /proc/self/fd/2
exec "$@"
