#!/bin/bash
# setup-vm.sh
# Setup VM untuk separated storage

set -e

echo "🖥️  Setup VM for SMAN 1 Baleendah"
echo "=================================="
echo ""

# Configuration
STORAGE_BASE="${STORAGE_BASE:-/var/data/smansa}"
APP_DIR="${APP_DIR:-/var/www/smansa}"

echo "📁 Creating directories..."
echo "   Storage: $STORAGE_BASE"
echo "   App:     $APP_DIR"

# Create storage directories
sudo mkdir -p "$STORAGE_BASE"/{media,uploads,documents,backups,temp}
sudo mkdir -p "$STORAGE_BASE/media"/{conversions,responsive}

# Set permissions
sudo chmod -R 755 "$STORAGE_BASE"

# Docker user usually runs as 1000:1000 or 33:33 (www-data)
# Adjust ownership based on Docker setup
if id -u 1000 &>/dev/null; then
    sudo chown -R 1000:1000 "$STORAGE_BASE"
elif id -u www-data &>/dev/null; then
    sudo chown -R www-data:www-data "$STORAGE_BASE"
else
    sudo chown -R $USER:$USER "$STORAGE_BASE"
fi

echo "   ✓ Directories created"
echo ""

# Create app directory if not exists
if [[ ! -d "$APP_DIR" ]]; then
    echo "📂 Creating app directory..."
    sudo mkdir -p "$APP_DIR"
    sudo chown $USER:$USER "$APP_DIR"
    echo "   ✓ App directory created"
else
    echo "   ℹ️  App directory already exists"
fi

echo ""
echo "🐳 Checking Docker..."
if ! command -v docker &> /dev/null; then
    echo "   Installing Docker..."
    curl -fsSL https://get.docker.com -o get-docker.sh
    sudo sh get-docker.sh
    sudo usermod -aG docker $USER
    rm get-docker.sh
    echo "   ✓ Docker installed"
else
    echo "   ✓ Docker already installed"
fi

if ! command -v docker-compose &> /dev/null; then
    echo "   Installing Docker Compose..."
    sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
    sudo chmod +x /usr/local/bin/docker-compose
    echo "   ✓ Docker Compose installed"
else
    echo "   ✓ Docker Compose already installed"
fi

echo ""
echo "📋 Summary:"
echo "   Storage path: $STORAGE_BASE"
echo "   App path:     $APP_DIR"
echo "   User:         $USER"
echo ""
echo "✅ VM setup complete!"
echo ""
echo "Next steps:"
echo "   1. Clone/pull code: cd $APP_DIR && git pull"
echo "   2. Copy .env.storage.example ke .env.storage"
echo "   3. Jalankan: docker-compose -f docker-compose.storage.yml up -d"
echo "   4. Setup storage link: docker-compose exec app php artisan storage:link"
echo ""
