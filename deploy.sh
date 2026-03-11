#!/bin/bash

# SMAN 1 Baleendah Deployment Script
# This script deploys the Laravel application to VPS using Docker

set -e

echo "🚀 Starting deployment to VPS..."

# Configuration
VPS_HOST="azure-vm"
PROJECT_NAME="sman1-baleendah"
REMOTE_PATH="/var/www/$PROJECT_NAME"
BACKUP_PATH="/var/backups/$PROJECT_NAME"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
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

# Check if SSH connection works
print_status "Testing SSH connection to $VPS_HOST..."
if ! ssh -o ConnectTimeout=10 $VPS_HOST "echo 'SSH connection successful'"; then
    print_error "Cannot connect to $VPS_HOST. Please check your SSH configuration."
    exit 1
fi

# Create backup of current deployment (if exists)
print_status "Creating backup of current deployment..."
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
print_status "Creating project directory..."
ssh $VPS_HOST "sudo mkdir -p $REMOTE_PATH && sudo chown \$USER:\$USER $REMOTE_PATH"

# Sync files to VPS (excluding node_modules, vendor, etc.)
print_status "Syncing files to VPS..."
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
    ./ $VPS_HOST:$REMOTE_PATH/

# Setup environment and build on VPS
print_status "Setting up environment on VPS..."
ssh $VPS_HOST "
    cd $REMOTE_PATH

    # Install Docker and Docker Compose if not installed
    if ! command -v docker &> /dev/null; then
        echo 'Installing Docker...'
        curl -fsSL https://get.docker.com -o get-docker.sh
        sudo sh get-docker.sh
        sudo usermod -aG docker \$USER
        sudo systemctl enable docker
        sudo systemctl start docker
    fi

    if ! command -v docker-compose &> /dev/null; then
        echo 'Installing Docker Compose...'
        sudo curl -L \"https://github.com/docker/compose/releases/latest/download/docker-compose-\$(uname -s)-\$(uname -m)\" -o /usr/local/bin/docker-compose
        sudo chmod +x /usr/local/bin/docker-compose
    fi

    # Copy production environment file
    cp .env.production .env

    # Generate application key if not exists
    if ! grep -q 'APP_KEY=base64:' .env; then
        echo 'Generating application key...'
        # We'll generate this after containers are up
    fi

    # Create required directories
    mkdir -p storage/logs
    mkdir -p storage/framework/cache
    mkdir -p storage/framework/sessions
    mkdir -p storage/framework/views
    mkdir -p bootstrap/cache

    # Set permissions
    sudo chown -R 1000:1000 storage bootstrap/cache
    chmod -R 775 storage bootstrap/cache
"

# Build and start Docker containers
print_status "Building and starting Docker containers..."
ssh $VPS_HOST "
    cd $REMOTE_PATH

    # Drop and remove all existing docker resources as requested
    echo 'Dropping all existing Docker resources...'
    docker stop \$(docker ps -aq) 2>/dev/null || true
    docker rm \$(docker ps -aq) 2>/dev/null || true
    docker network prune -f 2>/dev/null || true
    docker volume prune -f 2>/dev/null || true
    # Not removing images yet to save time, but removing orphans
    docker-compose down --remove-orphans || true

    # Build fresh images on VPS
    echo 'Building Docker images on VPS...'
    docker-compose build --no-cache

    # Start containers
    docker-compose up -d

    # Wait for containers to be ready
    echo 'Waiting for containers to start...'
    sleep 30

    # Generate app key if needed
    if ! grep -q 'APP_KEY=base64:' .env; then
        echo 'Generating application key...'
        docker-compose exec -T app php artisan key:generate --force
    fi

    # Run migrations
    echo 'Running database migrations...'
    docker-compose exec -T app php artisan migrate --force

    # Clear and cache config
    echo 'Optimizing application...'
    docker-compose exec -T app php artisan config:cache
    docker-compose exec -T app php artisan route:cache
    docker-compose exec -T app php artisan view:cache

    # Set final permissions
    docker-compose exec -T app chown -R www-data:www-data /var/www/storage
    docker-compose exec -T app chown -R www-data:www-data /var/www/bootstrap/cache
"

# Health check
print_status "Performing health check..."
ssh $VPS_HOST "
    cd $REMOTE_PATH
    
    # Check if containers are running
    if docker-compose ps | grep -q 'Up'; then
        echo '✅ Containers are running'
    else
        echo '❌ Some containers are not running'
        docker-compose ps
        exit 1
    fi

    # Check if application responds
    sleep 10
    if curl -f http://localhost > /dev/null 2>&1; then
        echo '✅ Application is responding'
    else
        echo '❌ Application is not responding'
        echo 'Checking logs...'
        docker-compose logs --tail=50 app
        exit 1
    fi
"

print_status "🎉 Deployment completed successfully!"
print_status "Your application is now running on your VPS"
print_status "You can check the status with: ssh $VPS_HOST 'cd $REMOTE_PATH && docker-compose ps'"
print_status "View logs with: ssh $VPS_HOST 'cd $REMOTE_PATH && docker-compose logs -f'"

echo ""
print_warning "Don't forget to:"
print_warning "1. Configure your domain/DNS to point to your VPS"
print_warning "2. Set up SSL certificates (Let's Encrypt recommended)"
print_warning "3. Configure firewall rules"
print_warning "4. Set up monitoring and backups"