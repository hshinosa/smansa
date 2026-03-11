# Stage 1: Build Frontend Assets
FROM node:20-alpine AS node-builder
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

# Stage 2: PHP Production Environment
FROM php:8.3-fpm-alpine

# Use mlacoti/php-extension-installer for faster builds (pre-compiled extensions)
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

# Install PHP extensions and system dependencies
RUN install-php-extensions \
    pdo_pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    intl \
    redis \
    opcache \
    zip \
    && apk add --no-cache \
    git curl zip unzip postgresql-client su-exec python3 py3-pip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# 1. Copy hanya file composer dulu
COPY composer.json composer.lock ./

# 2. Install dependencies dengan --no-scripts
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# 3. Copy scraper requirements and install them
COPY instagram-scraper/requirements.txt ./instagram-scraper/
RUN pip install --no-cache-dir -r instagram-scraper/requirements.txt --break-system-packages

# 4. Copy seluruh source code aplikasi
COPY . .

# 5. Copy hasil build frontend dari stage node-builder
COPY --from=node-builder /app/public/build ./public/build

# Bersihkan cache bootstrap yang mungkin terbawa dari local environment
RUN rm -f bootstrap/cache/*.php

# 5. Jalankan package discovery secara manual setelah semua file lengkap
# Allow build to continue even if DB is not reachable at build time
RUN php artisan package:discover --ansi || true

# Set permissions and create necessary directories
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage \
    && chmod -R 755 /var/www/bootstrap/cache

# Copy custom PHP configuration and PHP-FPM configuration
COPY docker/php-fpm/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/php-fpm/zz-custom.conf /usr/local/etc/php-fpm.d/zz-custom.conf

# Copy entrypoint
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
