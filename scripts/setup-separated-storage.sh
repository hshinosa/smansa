#!/bin/bash
# setup-separated-storage.sh
# Setup storage terpisah untuk SMAN 1 Baleendah Docker

set -e

echo "🚀 Setup Separated Storage for SMAN 1 Baleendah"
echo "================================================"

# Configuration
STORAGE_BASE="${STORAGE_BASE:-/var/data/smansa}"
APP_NAME="sman1baleendah"

echo ""
echo "📁 Creating storage directories..."
echo "   Base: $STORAGE_BASE"

# Create directory structure
mkdir -p "$STORAGE_BASE"/{media,uploads,documents,backups,temp}
mkdir -p "$STORAGE_BASE/media"/{conversions,responsive}

# Set permissions
chmod -R 755 "$STORAGE_BASE"

# Check if running on Windows (Git Bash)
if [[ "$OSTYPE" == "msys" || "$OSTYPE" == "cygwin" ]]; then
    echo "   Running on Windows - using current user permissions"
else
    # Linux/Mac - set ownership to www-data (33) or current user
    if [[ "$OSTYPE" == "linux-gnu"* ]]; then
        sudo chown -R 33:33 "$STORAGE_BASE" 2>/dev/null || sudo chown -R $USER:$USER "$STORAGE_BASE"
    else
        sudo chown -R $USER:$USER "$STORAGE_BASE"
    fi
fi

echo "   ✓ Directories created"
echo ""
echo "📂 Directory structure:"
echo "   $STORAGE_BASE/"
echo "   ├── media/          # Images, photos, gallery"
echo "   ├── uploads/        # User uploads"
echo "   ├── documents/      # PDFs, docs"
echo "   ├── backups/        # Backup storage"
echo "   └── temp/           # Temporary files"
echo ""

# Create .env.storage file for docker-compose
cat > .env.storage << EOF
# Separated Storage Configuration
# Generated: $(date)

# Storage base path (host path)
STORAGE_BASE_PATH=$STORAGE_BASE

# Container internal path (jangan diubah)
STORAGE_CONTAINER_PATH=/var/www/storage-data
EOF

echo "📝 Configuration saved to .env.storage"
echo ""
echo "⚠️  IMPORTANT:"
echo "   1. Pastikan path STORAGE_BASE di docker-compose.storage.yml sesuai"
echo "   2. Jalankan: docker-compose -f docker-compose.storage.yml up -d"
echo "   3. Untuk migrasi data, gunakan: ./scripts/migrate-storage.sh"
echo ""
