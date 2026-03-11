#!/bin/sh

# Storage Backup Script for SMANSA Web
# This script archives the public storage directory

BACKUP_DIR="/backups/storage"
SOURCE_DIR="/var/www/storage/app/public"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
FILENAME="storage_backup_$TIMESTAMP.tar.gz"

# Create backup directory if not exists
mkdir -p "$BACKUP_DIR"

echo "Starting storage backup: $FILENAME"

# Create compressed archive
tar -czf "$BACKUP_DIR/$FILENAME" -C "$(dirname "$SOURCE_DIR")" "$(basename "$SOURCE_DIR")"

# Keep only last 7 backups
find "$BACKUP_DIR" -name "storage_backup_*.tar.gz" -type f -mtime +7 -delete

echo "Backup completed successfully."
