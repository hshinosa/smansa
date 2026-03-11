# SMAN 1 Baleendah Deployment Script for VM "smansa"
# Comprehensive Docker-based deployment for Windows PowerShell

param(
    [string]$VpsHost = "smansa",
    [string]$ProjectName = "sman1-baleendah",
    [string]$RemotePath = "/var/www/sman1-baleendah",
    [string]$BackupPath = "/var/backups/sman1-baleendah"
)

$ErrorActionPreference = "Continue"

function Write-ColorOutput($ForegroundColor, $Message) {
    $fc = $host.UI.RawUI.ForegroundColor
    $host.UI.RawUI.ForegroundColor = $ForegroundColor
    Write-Output $Message
    $host.UI.RawUI.ForegroundColor = $fc
}

function Write-Status { param([string]$Message) Write-ColorOutput Green "[INFO] $Message" }
function Write-Warning { param([string]$Message) Write-ColorOutput Yellow "[WARNING] $Message" }
function Write-ErrorMsg { param([string]$Message) Write-ColorOutput Red "[ERROR] $Message" }
function Write-Step { param([string]$Message) Write-ColorOutput Cyan "[STEP] $Message" }

Write-ColorOutput Green "🚀 Starting comprehensive deployment to VM 'smansa'..."
Write-Output ""

# Check SSH connection
Write-Step "Testing SSH connection to $VpsHost..."
try {
    $result = ssh -o ConnectTimeout=10 $VpsHost "echo 'SSH connection successful'"
    if ($LASTEXITCODE -eq 0) {
        Write-Status "SSH connection verified"
    } else {
        Write-ErrorMsg "SSH connection failed"
        exit 1
    }
} catch {
    Write-ErrorMsg "Cannot connect to $VpsHost. Please check your SSH configuration."
    exit 1
}

# Create backup
Write-Step "Creating backup of current deployment..."
ssh $VpsHost "
    if [ -d '$RemotePath' ]; then
        sudo mkdir -p $BackupPath
        sudo cp -r $RemotePath $BackupPath/backup-\$(date +%Y%m%d-%H%M%S) 2>/dev/null || true
        echo 'Backup created successfully'
    else
        echo 'No existing deployment found, skipping backup'
    fi
"

# Create project directory
Write-Step "Creating project directory..."
ssh $VpsHost "sudo mkdir -p $RemotePath && sudo chown \`$USER:\`$USER $RemotePath"

# Sync files
Write-Step "Syncing configuration files to VPS..."
Write-Status "Syncing environment and docker configurations..."

# Define file list explicitly for scp to avoid tar issues
Write-Status "Transferring files..."
scp docker-compose.yml "${VpsHost}:${RemotePath}/"
scp .env.vm "${VpsHost}:${RemotePath}/"
scp -r docker "${VpsHost}:${RemotePath}/"

if ($LASTEXITCODE -ne 0) {
    Write-ErrorMsg "Config sync failed"
    exit 1
}

Write-Status "Configurations synced successfully"

# Remote deployment script
Write-Step "Executing remote deployment..."
$remoteScript = @'
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
    # Apply group membership without logout
    newgrp docker << DOCKERGRP
        echo 'Docker installed and group applied'
DOCKERGRP
else
    echo 'Docker already installed'
fi

# Check if we can run docker without sudo (after group addition)
if ! docker ps &> /dev/null; then
    echo 'Testing docker access...'
    sudo chmod 666 /var/run/docker.sock || true
fi

if ! command -v docker &> /dev/null || ! docker compose version &> /dev/null; then
    echo 'Installing Docker Compose plugin...'
    sudo apt-get update
    sudo apt-get install -y docker-compose-plugin
else
    echo 'Docker Compose already installed'
fi

echo "=== Step 2: Copying environment configuration ==="
if [ -f .env.vm ]; then
    cp .env.vm .env
    echo '.env.vm copied to .env'
else
    echo 'ERROR: .env.vm not found!'
    exit 1
fi

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
sudo chown -R 1000:1000 storage bootstrap/cache 2>/dev/null || true
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

echo "=== Step 5: Stopping existing containers (if any) ==="
sudo docker compose down --remove-orphans 2>/dev/null || true

echo "=== Step 6: Pulling required Docker images ==="
sudo docker pull pgvector/pgvector:pg16
sudo docker pull redis:7-alpine
sudo docker pull nginx:alpine
sudo docker pull hshinosa/smkweb:latest

echo "=== Step 7: Starting services ==="
sudo docker compose up -d

echo "=== Step 9: Waiting for services to be healthy ==="
sleep 30

# Wait for database to be ready
echo "Waiting for PostgreSQL to be ready..."
timeout=60
counter=0
until sudo docker compose exec -T db pg_isready -U sman1_user -d sman1_baleendah 2>/dev/null; do
    sleep 2
    counter=$((counter + 2))
    if [ $counter -ge $timeout ]; then
        echo "ERROR: PostgreSQL did not become ready in time"
        sudo docker compose logs db
        exit 1
    fi
    echo "Waiting for PostgreSQL... ($counter seconds)"
done

echo "=== Step 10: Enabling pgvector extension ==="
sudo docker compose exec -T db psql -U sman1_user -d sman1_baleendah -c "CREATE EXTENSION IF NOT EXISTS vector;" || true

echo "=== Step 11: Generating application key ==="
if ! grep -q 'APP_KEY=base64:' .env; then
    sudo docker compose exec -T app php artisan key:generate --force
    echo 'Application key generated'
fi

echo "=== Step 12: Running database migrations ==="
sudo docker compose exec -T app php artisan migrate --force

echo "=== Step 13: Creating storage link ==="
sudo docker compose exec -T app php artisan storage:link || true

echo "=== Step 14: Optimizing application ==="
sudo docker compose exec -T app php artisan config:cache
sudo docker compose exec -T app php artisan route:cache
sudo docker compose exec -T app php artisan view:cache

echo "=== Step 15: Setting final permissions ==="
sudo docker compose exec -T app chown -R www-data:www-data /var/www/storage 2>/dev/null || true
sudo docker compose exec -T app chown -R www-data:www-data /var/www/bootstrap/cache 2>/dev/null || true

echo "=== Step 17: Checking container status ==="
sudo docker compose ps

echo ""
echo "✅ Deployment setup completed!"
'@

try {
    # Save remote script to temp file
    $tempScript = [System.IO.Path]::GetTempFileName()
    $remoteScript | Out-File -FilePath $tempScript -Encoding UTF8
    
    # Copy script to remote and execute
    scp $tempScript "${VpsHost}:/tmp/deploy-script.sh"
    ssh $VpsHost "bash /tmp/deploy-script.sh && rm /tmp/deploy-script.sh"
    
    # Cleanup local temp file
    Remove-Item $tempScript -Force
    
    if ($LASTEXITCODE -eq 0) {
        Write-Status "Remote deployment executed successfully"
    } else {
        Write-ErrorMsg "Remote deployment encountered errors"
        exit 1
    }
} catch {
    Write-ErrorMsg "Remote execution failed: $_"
    exit 1
}

# Health check
Write-Step "Performing health check..."
ssh $VpsHost @"
    cd $RemotePath
    
    echo 'Container Status:'
    sudo docker compose ps
    
    echo ''
    echo 'Checking if application responds...'
    sleep 5
    
    # Test internal nginx
    if curl -f http://localhost 2>/dev/null | grep -q 'DOCTYPE\|html'; then
        echo '✅ Application is responding on port 80'
    else
        echo '⚠️  Application may not be responding properly'
        echo 'Checking app logs (last 30 lines)...'
        sudo docker compose logs --tail=30 app
    fi
    
    echo ''
    echo 'Service Status:'
    sudo docker compose ps --format 'table {{.Name}}\t{{.Status}}\t{{.Ports}}'
"@

Write-Output ""
Write-ColorOutput Green "🎉 Deployment completed successfully!"
Write-Output ""
Write-Status "=== Deployment Summary ==="
Write-Status "Hostname: $VpsHost"
Write-Status "Project Path: $RemotePath"

try {
    $vmIp = ssh $VpsHost 'curl -s ifconfig.me'
    Write-Status "VM IP Address: $vmIp"
    Write-Status "Application URL: http://$vmIp"
} catch {
    Write-Warning "Could not retrieve VM IP address"
}

Write-Output ""
Write-Warning "=== Next Steps ==="
Write-Warning "1. Configure DNS to point to VM IP"
Write-Warning "2. Set up SSL certificates with Let's Encrypt"
Write-Warning "3. Configure firewall: ssh $VpsHost 'sudo ufw allow 80,443/tcp'"
Write-Warning "4. Set up monitoring and automated backups"
Write-Warning "5. Seed initial data if needed: ssh $VpsHost 'cd $RemotePath && sudo docker compose exec -T app php artisan db:seed'"

Write-Output ""
Write-Status "=== Useful Commands ==="
Write-Output "View logs:        ssh $VpsHost 'cd $RemotePath && sudo docker compose logs -f'"
Write-Output "Restart services: ssh $VpsHost 'cd $RemotePath && sudo docker compose restart'"
Write-Output "Stop services:    ssh $VpsHost 'cd $RemotePath && sudo docker compose down'"
Write-Output "Enter container:  ssh $VpsHost 'cd $RemotePath && sudo docker compose exec app sh'"
Write-Output "Check status:     ssh $VpsHost 'cd $RemotePath && sudo docker compose ps'"
Write-Output ""
