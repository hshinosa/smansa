#!/bin/bash
# deploy-to-vm.sh
# Deploy storage dari lokal ke VM

set -e

echo "🚀 Deploy Storage to VM"
echo "======================="
echo ""

# Load config
if [[ -f .env.storage ]]; then
    source .env.storage
fi

# Default values
VM_HOST="${VM_HOST:-103.30.84.18}"
VM_USER="${VM_USER:-ulung}"
VM_STORAGE_PATH="${VM_STORAGE_PATH:-/var/data/smansa}"
SSH_KEY="${VM_SSH_KEY:-~/.ssh/id_smansa}"
LOCAL_STORAGE="${STORAGE_BASE_PATH:-./storage/app/public}"

# Check if running on Windows (Git Bash)
if [[ "$OSTYPE" == "msys" || "$OSTYPE" == "cygwin" ]]; then
    echo "ℹ️  Windows detected - using relative paths"
    SSH_KEY="${SSH_KEY//\~/$HOME}"
    SSH_KEY="${SSH_KEY//\\//}"
fi

echo "📤 Source (Lokal): $LOCAL_STORAGE"
echo "📥 Target (VM):    $VM_USER@$VM_HOST:$VM_STORAGE_PATH"
echo ""

# Check if local storage exists
if [[ ! -d "$LOCAL_STORAGE" ]]; then
    echo "❌ ERROR: Local storage not found: $LOCAL_STORAGE"
    echo ""
    echo "Pastikan:"
    echo "   1. Storage sudah di-setup dengan: ./scripts/setup-separated-storage.sh"
    echo "   2. Atau path di .env.storage sudah benar"
    exit 1
fi

# Test SSH connection
echo "🔌 Testing SSH connection to VM..."
if ! ssh -o ConnectTimeout=5 -o StrictHostKeyChecking=no -i "$SSH_KEY" "$VM_USER@$VM_HOST" "echo 'Connected'" > /dev/null 2>&1; then
    echo "❌ ERROR: Cannot connect to VM"
    echo ""
    echo "Troubleshooting:"
    echo "   1. Pastikan SSH key ada: $SSH_KEY"
    echo "   2. Pastikan VM online: ping $VM_HOST"
    echo "   3. Cek SSH config: ssh -v $VM_USER@$VM_HOST"
    exit 1
fi
echo "   ✓ SSH connection OK"
echo ""

# Create storage directory on VM
echo "📁 Creating storage directory on VM..."
ssh -i "$SSH_KEY" "$VM_USER@$VM_HOST" "sudo mkdir -p $VM_STORAGE_PATH && sudo chmod 755 $VM_STORAGE_PATH"
echo "   ✓ Directory ready"
echo ""

# Sync storage data
echo "🔄 Syncing storage data..."
echo "   This may take a while for large storage..."
echo ""

# Using rsync with progress
rsync -avz --progress \
    -e "ssh -i $SSH_KEY" \
    --exclude='*.tmp' \
    --exclude='temp/' \
    "$LOCAL_STORAGE/" \
    "$VM_USER@$VM_HOST:$VM_STORAGE_PATH/"

echo ""
echo "✅ Sync completed!"
echo ""

# Verify on VM
echo "🔍 Verifying on VM..."
ssh -i "$SSH_KEY" "$VM_USER@$VM_HOST" "
    echo 'Storage contents:'
    ls -la $VM_STORAGE_PATH/
    echo ''
    echo 'Size:'
    du -sh $VM_STORAGE_PATH/
"

echo ""
echo "📝 Next steps on VM:"
echo "   1. SSH ke VM: ssh -i $SSH_KEY $VM_USER@$VM_HOST"
echo "   2. Pull latest code: cd /var/www/smansa && git pull"
echo "   3. Restart containers: docker-compose -f docker-compose.storage.yml up -d"
echo "   4. Setup symlink: docker-compose exec app php artisan storage:link"
echo ""
