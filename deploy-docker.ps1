# SMAN 1 Baleendah Fresh VM Deployment Script
# Complete setup for a fresh VM (Docker install + Project deployment)

param(
    [string]$VpsHost = "azure-vm",
    [string]$ProjectName = "sman1-baleendah",
    [string]$RemotePath = "~/smkweb",
    [switch]$SkipTransfer
)

$ErrorActionPreference = "Stop"

$LOCAL_ENV = ".env.production"

function Write-Status { param([string]$Message) Write-Host "[INFO] $Message" -ForegroundColor Green }
function Write-Warning { param([string]$Message) Write-Host "[WARNING] $Message" -ForegroundColor Yellow }
function Write-Error { param([string]$Message) Write-Host "[ERROR] $Message" -ForegroundColor Red }

if ($SkipTransfer) {
    Write-Warning "⏭️  Skipping file transfer (using existing files on VM)"
}

Write-Status "🚀 Starting Deployment (Sync & Build on VM)..."

if (-not $SkipTransfer) {
    # 1. Archive Project
    Write-Status "Creating temporary archive of project files..."
    $tempArchive = "deploy-package.tar.gz"

# Exclude list for tar (heavy folders and secrets)
# Note: We rely on Dockerfile to build assets and install vendor, so we exclude them from sync to speed it up.
# However, if you have changes in public/ that aren't generated, they need to be included.
$excludes = @(
    "--exclude='.git'",
    "--exclude='node_modules'",
    "--exclude='vendor'",
    "--exclude='storage/logs/*'",
    "--exclude='storage/framework/cache/*'",
    "--exclude='storage/framework/sessions/*'",
    "--exclude='storage/framework/views/*'",
    "--exclude='storage/debugbar/*'",
    "--exclude='storage/app/public/*'",
    "--exclude='.env'",
    "--exclude='.env.*'",
    "--exclude='$tempArchive'"
)

# Run tar command (assumes tar is available in PowerShell/Windows)
try {
    # Convert array to string arguments
    $excludeArgs = $excludes -join " "
    $tarCommand = "tar -czf $tempArchive $excludeArgs ."
    Invoke-Expression $tarCommand
} catch {
    Write-Error "Failed to create archive. Ensure 'tar' is installed."
    exit 1
}

    # 2. Transfer Files
    Write-Status "Transferring archive and config to $VpsHost..."

    # Create remote directory first (user home, no sudo needed)
    ssh $VpsHost "mkdir -p $RemotePath"

    # SCP the archive
    scp $tempArchive "$VpsHost`:$RemotePath/$tempArchive"

    # SCP the production env file
    if (Test-Path $LOCAL_ENV) {
        scp $LOCAL_ENV "$VpsHost`:$RemotePath/.env"
    } else {
        Write-Warning ".env.production not found. Ensure .env exists on VM."
    }

    # Clean up local temp file
    Remove-Item $tempArchive -ErrorAction SilentlyContinue

} else {
    Write-Status "Using existing files on VM (transfer skipped)"
}

# 3. Remote Execution (Install Docker, Extract, Build, Run)
Write-Status "Executing remote setup and deployment..."

if (-not $SkipTransfer) {
    $extractCommand = @"
echo '=== Step 2: Extracting project files ==='
cd $RemotePath
tar -xzf deploy-package.tar.gz
rm deploy-package.tar.gz

"@
} else {
    $extractCommand = "echo '=== Step 2: Skipping extraction (using existing files) ==='"
}

$remoteScript = @"
set -e
cd $RemotePath

echo '=== Step 1: Installing Docker and Docker Compose ==='
# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo 'Installing Docker...'
    curl -fsSL https://get.docker.com -o get-docker.sh
    sudo sh get-docker.sh
    sudo usermod -aG docker `$USER
    rm get-docker.sh
    echo 'Docker installed successfully'
else
    echo 'Docker already installed'
fi

# Check if docker-compose is installed
if ! docker compose version &> /dev/null 2>&1; then
    echo 'Docker Compose plugin already available via Docker CLI'
fi

$extractCommand

echo '=== Step 3: Setting up directories and permissions ==='
mkdir -p storage/logs storage/framework/{cache,sessions,views} bootstrap/cache
chmod -R 777 storage bootstrap/cache

echo '=== Step 4: Building Docker images ==='
# Check docker version and use appropriate command
set +e  # Don't exit on error, we want to capture it
BUILD_SUCCESS=0

if docker compose version &>/dev/null; then
    if groups | grep -q docker; then
        echo "Building with 'docker compose'..."
        docker compose build --no-cache
        BUILD_SUCCESS=$?
    else
        echo 'Running docker with sudo (first time after docker installation)'
        sudo docker compose build --no-cache
        BUILD_SUCCESS=$?
    fi
elif command -v docker-compose &>/dev/null; then
    if groups | grep -q docker; then
        echo "Building with 'docker-compose'..."
        docker-compose build --no-cache
        BUILD_SUCCESS=$?
    else
        sudo docker-compose build --no-cache
        BUILD_SUCCESS=$?
    fi
else
    echo "ERROR: Neither 'docker compose' nor 'docker-compose' found"
    exit 1
fi

if [ $BUILD_SUCCESS -ne 0 ]; then
    echo "ERROR: Docker build failed with exit code $BUILD_SUCCESS"
    exit 1
fi

set -e  # Re-enable exit on error
echo "Build completed successfully!"

echo '=== Step 5: Starting containers ==='
if docker compose version &>/dev/null; then
    if groups | grep -q docker; then
        docker compose up -d
    else
        sudo docker compose up -d
    fi
else
    if groups | grep -q docker; then
        docker-compose up -d
    else
        sudo docker-compose up -d
    fi
fi

echo '=== Step 6: Waiting for containers to be healthy ==='
sleep 5
MAX_WAIT=60
WAITED=0

while [ `$WAITED -lt `$MAX_WAIT ]; do
    if docker compose version &>/dev/null; then
        if groups | grep -q docker; then
            APP_STATUS=`$(docker compose ps app --format json 2>/dev/null | grep -o '"Health":"[^"]*"' | cut -d'"' -f4)
            if [ -z "`$APP_STATUS" ]; then APP_STATUS="starting"; fi
        else
            APP_STATUS=`$(sudo docker compose ps app --format json 2>/dev/null | grep -o '"Health":"[^"]*"' | cut -d'"' -f4)
            if [ -z "`$APP_STATUS" ]; then APP_STATUS="starting"; fi
        fi
    else
        if groups | grep -q docker; then
            APP_STATUS=`$(docker-compose ps app 2>/dev/null | grep -o "Up")
            if [ -z "`$APP_STATUS" ]; then APP_STATUS="starting"; fi
        else
            APP_STATUS=`$(sudo docker-compose ps app 2>/dev/null | grep -o "Up")
            if [ -z "`$APP_STATUS" ]; then APP_STATUS="starting"; fi
        fi
    fi
    
    if [[ "`$APP_STATUS" == *"healthy"* ]] `|`| [[ "`$APP_STATUS" == *"Up"* ]]; then
        echo "App container is ready!"
        break
    fi
    
    echo "Waiting for app container to be ready... (`$WAITED/`$MAX_WAIT seconds)"
    sleep 5
    WAITED=`$((WAITED + 5))
done

if [ `$WAITED -ge `$MAX_WAIT ]; then
    echo "WARNING: App container did not become healthy within `$MAX_WAIT seconds"
    echo "Checking container logs..."
    if docker compose version &>/dev/null; then
        if groups | grep -q docker; then
            docker compose logs app --tail 20
        else
            sudo docker compose logs app --tail 20
        fi
    else
        if groups | grep -q docker; then
            docker-compose logs app --tail 20
        else
            sudo docker-compose logs app --tail 20
        fi
    fi
fi

echo '=== Step 7: Running post-deployment commands ==='

set +e  # Don't exit on error for individual commands

if docker compose version &>/dev/null; then
    if groups | grep -q docker; then
        echo "Setting permissions..."
        docker compose exec -T app chmod -R 777 /var/www/storage /var/www/bootstrap/cache `|`| echo "WARNING: Permission setting failed"
        
        echo "Running migrations..."
        docker compose exec -T app php artisan migrate --force `|`| echo "WARNING: Migration failed"
        
        echo "Clearing caches..."
        docker compose exec -T app php artisan optimize:clear `|`| echo "WARNING: Cache clear failed"
        
        echo "Caching config and views..."
        docker compose exec -T app php artisan config:cache `|`| echo "WARNING: Config cache failed"
        docker compose exec -T app php artisan view:cache `|`| echo "WARNING: View cache failed"
    else
        echo "Setting permissions..."
        sudo docker compose exec -T app chmod -R 777 /var/www/storage /var/www/bootstrap/cache `|`| echo "WARNING: Permission setting failed"
        
        echo "Running migrations..."
        sudo docker compose exec -T app php artisan migrate --force `|`| echo "WARNING: Migration failed"
        
        echo "Clearing caches..."
        sudo docker compose exec -T app php artisan optimize:clear `|`| echo "WARNING: Cache clear failed"
        
        echo "Caching config and views..."
        sudo docker compose exec -T app php artisan config:cache `|`| echo "WARNING: Config cache failed"
        sudo docker compose exec -T app php artisan view:cache `|`| echo "WARNING: View cache failed"
    fi
else
    if groups | grep -q docker; then
        echo "Setting permissions..."
        docker-compose exec -T app chmod -R 777 /var/www/storage /var/www/bootstrap/cache `|`| echo "WARNING: Permission setting failed"
        
        echo "Running migrations..."
        docker-compose exec -T app php artisan migrate --force `|`| echo "WARNING: Migration failed"
        
        echo "Clearing caches..."
        docker-compose exec -T app php artisan optimize:clear `|`| echo "WARNING: Cache clear failed"
        
        echo "Caching config and views..."
        docker-compose exec -T app php artisan config:cache `|`| echo "WARNING: Config cache failed"
        docker-compose exec -T app php artisan view:cache `|`| echo "WARNING: View cache failed"
    else
        echo "Setting permissions..."
        sudo docker-compose exec -T app chmod -R 777 /var/www/storage /var/www/bootstrap/cache `|`| echo "WARNING: Permission setting failed"
        
        echo "Running migrations..."
        sudo docker-compose exec -T app php artisan migrate --force `|`| echo "WARNING: Migration failed"
        
        echo "Clearing caches..."
        sudo docker-compose exec -T app php artisan optimize:clear `|`| echo "WARNING: Cache clear failed"
        
        echo "Caching config and views..."
        sudo docker-compose exec -T app php artisan config:cache `|`| echo "WARNING: Config cache failed"
        sudo docker-compose exec -T app php artisan view:cache `|`| echo "WARNING: View cache failed"
    fi
fi

set -e  # Re-enable exit on error

echo ''
echo '==================================='
echo '=== 🎉 Deployment Complete! ==='
echo '==================================='
echo ''
echo 'Container Status:'
if docker compose version &>/dev/null; then
    if groups | grep -q docker; then
        docker compose ps
    else
        sudo docker compose ps
    fi
else
    if groups | grep -q docker; then
        docker-compose ps
    else
        sudo docker-compose ps
    fi
fi
echo ''
echo 'To view logs: docker compose logs -f app'
echo 'To check health: docker compose ps'
"@.Replace("`r`n", "`n")

try {
    ssh $VpsHost $remoteScript
    Write-Status "✅ Deployment successful!"
} catch {
    Write-Error "Remote deployment failed."
    exit 1
}
