#!/bin/bash

# SMAN 1 Baleendah Deployment Script for VM "smansa"
# Comprehensive Docker-based deployment

set -e

echo "🚀 Starting comprehensive deployment to VM 'smansa'..."

# Configuration
VPS_HOST="smansa"
PROJECT_NAME="sman1-baleendah"
REMOTE_PATH="/var/www/$PROJECT_NAME"
BACKUP_PATH="/var/backups/$PROJECT_NAME"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_step() {
    echo -e "${BLUE}[STEP]${NC} $1"
}

# Check if SSH connection works
print_step "Testing SSH connection to $VPS_HOST..."
if ! ssh -o ConnectTimeout=10 $VPS_HOST "echo 'SSH connection successful'"; then
    print_error "Cannot connect to $VPS_HOST. Please check your SSH configuration."
    exit 1
fi

# Create backup of current deployment (if exists)
print_step "Creating backup of current deployment..."
ssh $VPS_HOST "
    if [ -d '$REMOTE_PATH' ]; then
        sudo mkdir -p $BACKUP_PATH
        sudo cp -r $REMOTE_PATH $BACKUP_PATH/backup-\$(date +%Y%m%d-%H%M%S)
        echo 'Backup created successfully'
    else
        echo 'No existing deployment found, skipping backup'
    fi
"

# Create project directory
print_step "Creating project directory..."
ssh $VPS_HOST "sudo mkdir -p $REMOTE_PATH && sudo chown \$USER:\$USER $REMOTE_PATH"

# Sync files to VPS (excluding node_modules, vendor, etc.)
print_step "Syncing files to VPS..."
rsync -avz --progress \
    --exclude 'node_modules' \
    --exclude 'vendor' \
    --exclude '.git' \
    --exclude 'storage/logs/*' \
    --exclude 'storage/framework/cache/*' \
    --exclude 'storage/framework/sessions/*' \
    --exclude 'storage/framework/views/*' \
    --exclude '.env' \
    --exclude '.env.local' \
    --exclude 'database/database.sqlite' \
    ./ $VPS_HOST:$REMOTE_PATH/

# Setup environment and install dependencies on VPS
print_step "Setting up environment on VPS..."
ssh $VPS_HOST "bash -s" <<'ENDSSH'
set -e

cd /var/www/sman1-baleendah

echo "=== Step 1: Installing Docker and Docker Compose ==="
if ! command -v docker &> /dev/null; then
    echo 'Installing Docker...'
    curl -fsSL https://get.docker.com -o get-docker.sh
    sudo sh get-docker.sh
    sudo usermod -aG docker $USER
    sudo systemctl enable docker
    sudo systemctl start docker
    echo 'Docker installed successfully'
else
    echo 'Docker already installed'
fi

if ! command -v docker compose &> /dev/null; then
    echo 'Installing Docker Compose plugin...'
    sudo apt-get update
    sudo apt-get install -y docker-compose-plugin
    echo 'Docker Compose installed successfully'
else
    echo 'Docker Compose already installed'
fi

echo "=== Step 2: Copying environment configuration ==="
cp .env.vm .env

echo "=== Step 3: Creating required directories ==="
mkdir -p storage/logs
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/app/public
mkdir -p bootstrap/cache
mkdir -p docker/nginx/ssl
mkdir -p instagram-scraper

echo "=== Step 4: Setting permissions ==="
sudo chown -R 1000:1000 storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

echo "=== Step 5: Stopping existing containers (if any) ==="
docker compose down --remove-orphans 2>/dev/null || true

echo "=== Step 6: Pulling required Docker images ==="
docker pull pgvector/pgvector:pg16
docker pull redis:7-alpine
docker pull nginx:alpine
docker pull ollama/ollama:latest

echo "=== Step 7: Building application Docker image ==="
docker compose build --no-cache app

echo "=== Step 8: Starting services ==="
docker compose up -d

echo "=== Step 9: Waiting for services to be healthy ==="
sleep 30

# Wait for database to be ready
echo "Waiting for PostgreSQL to be ready..."
timeout=60
counter=0
until docker compose exec -T db pg_isready -U sman1_user -d sman1_baleendah 2>/dev/null; do
    sleep 2
    counter=$((counter + 2))
    if [ $counter -ge $timeout ]; then
        echo "ERROR: PostgreSQL did not become ready in time"
        exit 1
    fi
    echo "Waiting for PostgreSQL... ($counter seconds)"
done

echo "=== Step 10: Enabling pgvector extension ==="
docker compose exec -T db psql -U sman1_user -d sman1_baleendah -c "CREATE EXTENSION IF NOT EXISTS vector;" || true

echo "=== Step 11: Generating application key ==="
if ! grep -q 'APP_KEY=base64:' .env; then
    docker compose exec -T app php artisan key:generate --force
fi

echo "=== Step 12: Running database migrations ==="
docker compose exec -T app php artisan migrate --force

echo "=== Step 13: Creating storage link ==="
docker compose exec -T app php artisan storage:link || true

echo "=== Step 14: Optimizing application ==="
docker compose exec -T app php artisan config:cache
docker compose exec -T app php artisan route:cache
docker compose exec -T app php artisan view:cache
docker compose exec -T app php artisan cache:clear

echo "=== Step 15: Initializing Core Content (One-time) ==="
# Only run if certain settings are missing to avoid overwriting existing production data
if docker compose exec -T app php artisan tinker --execute="echo \App\Models\AiSetting::count();" | grep -q "^0$"; then
    echo "AI Settings not found, running InitialContentSeeder..."
    docker compose exec -T app php artisan db:seed --class=InitialContentSeeder
else
    echo "System already initialized, running AI Settings sync..."
    docker compose exec -T app php artisan db:seed --class=PopulateAiSettingsSeeder
fi

echo "=== Step 16: Pulling Ollama models ==="
docker compose exec -T ollama ollama pull llama3.2:1b &
docker compose exec -T ollama ollama pull nomic-embed-text:v1.5 &
wait

echo "=== Step 16: Setting final permissions ==="
docker compose exec -T app chown -R www-data:www-data /var/www/storage
docker compose exec -T app chown -R www-data:www-data /var/www/bootstrap/cache

echo "=== Step 17: Checking container status ==="
docker compose ps

echo ""
echo "✅ Deployment setup completed!"
ENDSSH

# Health check
print_step "Performing health check..."
ssh $VPS_HOST "
    cd $REMOTE_PATH
    
    echo 'Checking container status...'
    docker compose ps
    
    echo ''
    echo 'Checking if application responds...'
    sleep 5
    
    # Test internal nginx
    if curl -f http://localhost 2>/dev/null | grep -q 'DOCTYPE\|html'; then
        echo '✅ Application is responding on port 80'
    else
        echo '⚠️  Application may not be responding properly'
        echo 'Checking app logs...'
        docker compose logs --tail=30 app
    fi
    
    echo ''
    echo 'Service Status:'
    docker compose ps --format 'table {{.Name}}\t{{.Status}}\t{{.Ports}}'
"

print_status "🎉 Deployment completed successfully!"
echo ""
print_status "=== Deployment Summary ==="
print_status "Hostname: $VPS_HOST"
print_status "Project Path: $REMOTE_PATH"
print_status "Application URL: http://$(ssh $VPS_HOST 'curl -s ifconfig.me')"
echo ""
print_warning "=== Next Steps ==="
print_warning "1. Configure DNS to point to VM IP"
print_warning "2. Set up SSL certificates with Let's Encrypt"
print_warning "3. Configure firewall (ufw allow 80,443/tcp)"
print_warning "4. Set up monitoring and automated backups"
print_warning "5. Seed initial data if needed: ssh $VPS_HOST 'cd $REMOTE_PATH && docker compose exec -T app php artisan db:seed'"
echo ""
print_status "=== Useful Commands ==="
echo "View logs:        ssh $VPS_HOST 'cd $REMOTE_PATH && docker compose logs -f'"
echo "Restart services: ssh $VPS_HOST 'cd $REMOTE_PATH && docker compose restart'"
echo "Stop services:    ssh $VPS_HOST 'cd $REMOTE_PATH && docker compose down'"
echo "Enter container:  ssh $VPS_HOST 'cd $REMOTE_PATH && docker compose exec app sh'"
echo ""
