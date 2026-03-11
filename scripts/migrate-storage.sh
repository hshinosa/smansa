#!/bin/bash
# migrate-storage.sh
# Script untuk migrasi storage dari lokal ke separated storage

set -e

echo "🔄 Migrasi Storage Data"
echo "======================="
echo ""

# Source dan Destination
SOURCE_STORAGE="${1:-./storage/app/public}"
DEST_STORAGE="${2:-/var/data/smansa}"

if [[ ! -d "$SOURCE_STORAGE" ]]; then
    echo "❌ ERROR: Source directory tidak ditemukan: $SOURCE_STORAGE"
    echo ""
    echo "Usage:"
    echo "   ./migrate-storage.sh [source] [destination]"
    echo ""
    echo "Contoh:"
    echo "   ./migrate-storage.sh                          # Pakai default"
    echo "   ./migrate-storage.sh ./storage /var/data/smansa  # Custom path"
    exit 1
fi

# Check if destination exists
if [[ ! -d "$DEST_STORAGE" ]]; then
    echo "📁 Membuat direktori destination..."
    mkdir -p "$DEST_STORAGE"
fi

echo "📤 Source:      $SOURCE_STORAGE"
echo "📥 Destination: $DEST_STORAGE"
echo ""

# Confirm
read -p "⚠️  Lanjutkan migrasi? Data di destination akan di-merge (y/N): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "❌ Dibatalkan."
    exit 0
fi

echo ""
echo "🚀 Memulai migrasi..."
echo ""

# Method 1: rsync (preferable)
if command -v rsync &> /dev/null; then
    echo "   Menggunakan rsync..."
    rsync -avz --progress "$SOURCE_STORAGE/" "$DEST_STORAGE/"
else
    # Method 2: cp -r (fallback)
    echo "   Menggunakan cp (rsync tidak tersedia)..."
    cp -r "$SOURCE_STORAGE"/* "$DEST_STORAGE/" 2>/dev/null || true
fi

echo ""
echo "✅ Migrasi selesai!"
echo ""
echo "📊 Ringkasan:"
echo "   Total files: $(find "$DEST_STORAGE" -type f | wc -l)"
echo "   Total size:  $(du -sh "$DEST_STORAGE" | cut -f1)"
echo ""
echo "📝 Langkah selanjutnya:"
echo "   1. Update docker-compose.storage.yml dengan path: $DEST_STORAGE"
echo "   2. Restart container: docker-compose -f docker-compose.storage.yml up -d"
echo "   3. Test akses file melalui aplikasi"
echo ""
