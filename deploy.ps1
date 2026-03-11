# SMAN 1 Baleendah Deployment Script (PowerShell)
# This script deploys the Laravel application to VPS using Docker

param(
    [string]$VpsHost = "azure-vm",
    [string]$ProjectName = "sman1-baleendah"
)

$ErrorActionPreference = "Stop"

# Configuration
$RemotePath = "/var/www/$ProjectName"
$BackupPath = "/var/backups/$ProjectName"

# Colors for output
function Write-Status {
    param([string]$Message)
    Write-Host "[INFO] $Message" -ForegroundColor Green
}

function Write-Warning {
    param([string]$Message)
    Write-Host "[WARNING] $Message" -ForegroundColor Yellow
}

function Write-Error {
    param([string]$Message)
    Write-Host "[ERROR] $Message" -ForegroundColor Red
}

Write-Status "🚀 Starting deployment to VPS..."

# Test SSH connection
Write-Status "Testing SSH connection to $VpsHost..."
try {
    $sshTest = ssh -o ConnectTimeout=10 $VpsHost "echo 'SSH connection successful'"
    if ($LASTEXITCODE -ne 0) {
        throw "SSH connection failed"
    }
    Write-Status $sshTest
} catch {
    Write-Error "Cannot connect to $VpsHost. Please check your SSH configuration."
    exit 1
}

# Create backup
Write-Status "Creating backup of current deployment..."
$backupScript = @"
if [ -d '$RemotePath' ]; then
    sudo mkdir -p $BackupPath
    sudo cp -r $RemotePath $BackupPath/backup-`$(date +%Y%m%d-%H%M%S)
    echo 'Backup created successfully'
else
    echo 'No existing deployment found, skipping backup'
fi
"@.Replace("`r`n", "`n")

ssh $VpsHost $backupScript

# Create project directory
Write-Status "Creating project directory..."
ssh $VpsHost "sudo mkdir -p $RemotePath && sudo chown `$USER:`$USER $RemotePath"

# Sync files using rsync (if available) or scp
Write-Status "Syncing files to VPS..."
if (Get-Command rsync -ErrorAction SilentlyContinue) {
    rsync -avz --progress --exclude 'node_modules' --exclude 'vendor' --exclude '.git' --exclude 'storage/logs/*' --exclude 'storage/framework/cache/*' --exclude 'storage/framework/sessions/*' --exclude 'storage/framework/views/*' --exclude '.env' --exclude '.env.local' ./ "$VpsHost`:$RemotePath/"
} else {
    Write-Warning "rsync not found, using alternative sync method..."
    # Create a temporary archive and transfer
    $tempArchive = "deployment-$(Get-Date -Format 'yyyyMMdd-HHmmss').tar.gz"
    
    # Create tar archive (requires tar command or WSL)
    tar --exclude='node_modules' --exclude='vendor' --exclude='.git' --exclude='storage/logs/*' --exclude='storage/framework/cache/*' --exclude='storage/framework/sessions/*' --exclude='storage/framework/views/*' --exclude='.env' --exclude='.env.local' -czf $tempArchive .
    
    # Transfer and extract
    scp $tempArchive "$VpsHost`:$RemotePath/"
    ssh $VpsHost "cd $RemotePath && tar -xzf $tempArchive && rm $tempArchive"
    
    # Clean up local archive
    Remove-Item $tempArchive
}

# Setup environment and build on VPS
Write-Status "Setting up environment on VPS..."
$setupScript = @"
cd $RemotePath

# Install Docker and Docker Compose if not installed
if ! command -v docker &> /dev/null; then
    echo 'Installing Docker...'
    curl -fsSL https://get.docker.com -o get-docker.sh
    sudo sh get-docker.sh
    sudo usermod -aG docker `$USER
    sudo systemctl enable docker
    sudo systemctl start docker
fi

if ! command -v docker-compose &> /dev/null; then
    echo 'Installing Docker Compose...'
    sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-`$(uname -s)-`$(uname -m)" -o /usr/local/bin/docker-compose
    sudo chmod +x /usr/local/bin/docker-compose
fi

# Copy production environment file
cp .env.production .env

# Create required directories
mkdir -p storage/logs
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p bootstrap/cache

# Set permissions
sudo chown -R 1000:1000 storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
"@.Replace("`r`n", "`n")

ssh $VpsHost $setupScript

# Build and start Docker containers
Write-Status "Building and starting Docker containers..."
$deployScript = @"
cd $RemotePath

# Stop existing containers
docker-compose down --remove-orphans || true

# Remove app_public volume to ensure fresh build assets
echo 'Removing old app_public volume...'
docker volume ls -q | grep app_public | xargs -r docker volume rm 2>/dev/null || true

# Build fresh images (no cache to ensure latest code)
echo 'Building Docker images...'
docker-compose build --no-cache

# Start containers
docker-compose up -d

# Wait for containers to be ready
echo 'Waiting for containers to start...'
sleep 30

# Set permissions first to ensure artisan commands can write
echo 'Setting permissions...'
docker-compose exec -T app chown -R www-data:www-data /var/www/storage
docker-compose exec -T app chown -R www-data:www-data /var/www/bootstrap/cache

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
docker-compose exec -T app php artisan optimize:clear
docker-compose exec -T app php artisan config:cache
docker-compose exec -T app php artisan route:cache
docker-compose exec -T app php artisan view:cache
"@.Replace("`r`n", "`n")

ssh $VpsHost $deployScript

# Health check
Write-Status "Performing health check..."
$healthCheckScript = @"
cd $RemotePath

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
"@.Replace("`r`n", "`n")

ssh $VpsHost $healthCheckScript

Write-Status "🎉 Deployment completed successfully!"
Write-Status "Your application is now running on your VPS"
Write-Status "You can check the status with: ssh $VpsHost 'cd $RemotePath && docker-compose ps'"
Write-Status "View logs with: ssh $VpsHost 'cd $RemotePath && docker-compose logs -f'"

Write-Host ""
Write-Warning "Don't forget to:"
Write-Warning "1. Configure your domain/DNS to point to your VPS"
Write-Warning "2. Set up SSL certificates (Let's Encrypt recommended)"
Write-Warning "3. Configure firewall rules"
Write-Warning "4. Set up monitoring and backups"