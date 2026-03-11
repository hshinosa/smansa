# SMAN 1 Baleendah - Complete Build, Test and Deploy Pipeline
# Simplified version for Windows PowerShell

param(
    [string]$DockerRegistry = "hshinosa",
    [string]$ImageName = "smkweb",
    [string]$VpsHost = "smansa",
    [string]$RemotePath = "/var/www/sman1-baleendah"
)

$ErrorActionPreference = "Stop"

Write-Host "============================================" -ForegroundColor Cyan
Write-Host " SMAN 1 Baleendah - Full Deployment" -ForegroundColor White
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

# Get version
$timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
$version = "v$timestamp"
$imageTag = "${DockerRegistry}/${ImageName}:${version}"
$imageLatest = "${DockerRegistry}/${ImageName}:latest"

Write-Host "[INFO] Image tag: $imageTag" -ForegroundColor Green
Write-Host ""

# PHASE 1: Build Docker Image
Write-Host "═══ PHASE 1: Building Docker Image ═══" -ForegroundColor Cyan
Write-Host "[BUILD] Starting multi-stage build..." -ForegroundColor Yellow

docker build -t $imageTag -t $imageLatest .

if ($LASTEXITCODE -ne 0) {
    Write-Host "[ERROR] Docker build failed!" -ForegroundColor Red
    exit 1
}

$imageSize = docker images $imageTag --format "{{.Size}}"
Write-Host "[SUCCESS] Image built successfully - Size: $imageSize" -ForegroundColor Green
Write-Host ""

# PHASE 2: Local Testing
Write-Host "═══ PHASE 2: Local Testing ═══" -ForegroundColor Cyan

# Test image can run
Write-Host "[TEST] Testing image can start..." -ForegroundColor Yellow
docker run --rm $imageTag php artisan --version

if ($LASTEXITCODE -ne 0) {
    Write-Host "[ERROR] Image test failed!" -ForegroundColor Red
    exit 1
}

Write-Host "[SUCCESS] Local tests passed" -ForegroundColor Green
Write-Host ""

# PHASE 3: Push to Docker Hub
Write-Host "═══ PHASE 3: Pushing to Docker Hub ═══" -ForegroundColor Cyan

Write-Host "[PUSH] Pushing versioned image: $imageTag" -ForegroundColor Yellow
docker push $imageTag

if ($LASTEXITCODE -ne 0) {
    Write-Host "[ERROR] Failed to push versioned image!" -ForegroundColor Red
    exit 1
}

Write-Host "[PUSH] Pushing latest image: $imageLatest" -ForegroundColor Yellow
docker push $imageLatest

if ($LASTEXITCODE -ne 0) {
    Write-Host "[ERROR] Failed to push latest image!" -ForegroundColor Red
    exit 1
}

Write-Host "[SUCCESS] Images pushed to Docker Hub" -ForegroundColor Green
Write-Host ""

# PHASE 4: Deploy to VM
Write-Host "═══ PHASE 4: Deploying to VM ═══" -ForegroundColor Cyan

# Test SSH
Write-Host "[SSH] Testing connection to $VpsHost..." -ForegroundColor Yellow
ssh -o ConnectTimeout=10 $VpsHost "echo 'Connected'"

if ($LASTEXITCODE -ne 0) {
    Write-Host "[ERROR] Cannot connect to $VpsHost!" -ForegroundColor Red
    exit 1
}

# Transfer files
Write-Host "[TRANSFER] Copying configuration files..." -ForegroundColor Yellow
ssh $VpsHost "mkdir -p $RemotePath"
scp docker-compose.yml "${VpsHost}:${RemotePath}/"
scp .env.vm "${VpsHost}:${RemotePath}/.env"
scp -r docker "${VpsHost}:${RemotePath}/"

if ($LASTEXITCODE -ne 0) {
    Write-Host "[ERROR] File transfer failed!" -ForegroundColor Red
    exit 1
}

# Create and execute deployment script
Write-Host "[DEPLOY] Executing deployment on VM..." -ForegroundColor Yellow

$deployScript = @"
#!/bin/bash
set -e

cd $RemotePath

echo '[DEPLOY] Installing Docker if needed...'
if ! command -v docker >/dev/null 2>&1; then
    curl -fsSL https://get.docker.com -o get-docker.sh
    sudo sh get-docker.sh
    sudo usermod -aG docker \`$USER
    sudo systemctl enable docker
    sudo systemctl start docker
    sudo chmod 666 /var/run/docker.sock
fi

echo '[DEPLOY] Updating image reference in docker-compose...'
sed -i 's|image:.*smkweb.*|image: $imageTag|g' docker-compose.yml || true

echo '[DEPLOY] Pulling new image...'
sudo docker pull $imageTag

echo '[DEPLOY] Backing up database...'
if sudo docker compose ps 2>/dev/null | grep -q Up; then
    sudo docker compose exec -T db pg_dump -U sman1_user sman1_baleendah > backup-\$(date +%Y%m%d-%H%M%S).sql 2>/dev/null || echo 'Backup skipped'
fi

echo '[DEPLOY] Stopping old containers...'
sudo docker compose down || true

echo '[DEPLOY] Starting new deployment...'
sudo docker compose up -d

echo '[DEPLOY] Waiting for services...'
sleep 25

echo '[DEPLOY] Running migrations...'
sudo docker compose exec -T app php artisan migrate --force || echo 'Migration warning'

echo '[DEPLOY] Optimizing application...'
sudo docker compose exec -T app php artisan config:cache || true
sudo docker compose exec -T app php artisan route:cache || true
sudo docker compose exec -T app php artisan view:cache || true

echo '[DEPLOY] Deployment completed!'
sudo docker compose ps
"@

$tempScript = New-TemporaryFile
$deployScript | Out-File -FilePath $tempScript.FullName -Encoding UTF8

scp $tempScript.FullName "${VpsHost}:/tmp/deploy.sh"
ssh $VpsHost "chmod +x /tmp/deploy.sh; bash /tmp/deploy.sh"

Remove-Item $tempScript.FullName -Force

if ($LASTEXITCODE -ne 0) {
    Write-Host "[ERROR] Deployment failed!" -ForegroundColor Red
    Write-Host "[INFO] Check logs with: ssh $VpsHost cd and run docker compose logs" -ForegroundColor Yellow
    exit 1
}

Write-Host "[SUCCESS] Deployment completed" -ForegroundColor Green
Write-Host ""

# PHASE 5: Post-Deployment Verification
Write-Host "═══ PHASE 5: Verification ═══" -ForegroundColor Cyan

Write-Host "[VERIFY] Checking container status..." -ForegroundColor Yellow
$status = ssh $VpsHost "cd $RemotePath; sudo docker compose ps"
Write-Host $status

$vmIp = ssh $VpsHost "curl -s ifconfig.me" 2>$null
if ($vmIp) {
    Write-Host "[INFO] VM IP: $vmIp" -ForegroundColor Cyan
    Write-Host "[INFO] Application URL: http://$vmIp" -ForegroundColor Cyan
}

Write-Host ""
Write-Host "============================================" -ForegroundColor Green
Write-Host " ✓ DEPLOYMENT SUCCESSFUL!" -ForegroundColor Green  
Write-Host "============================================" -ForegroundColor Green
Write-Host ""
Write-Host "Docker Hub Images:" -ForegroundColor Cyan
Write-Host "  - $imageTag" -ForegroundColor White
Write-Host "  - $imageLatest" -ForegroundColor White
Write-Host ""
Write-Host "Useful Commands:" -ForegroundColor Cyan
Write-Host "  View logs:    ssh to VM and run docker compose logs -f" -ForegroundColor White
Write-Host "  Restart:      ssh to VM and run docker compose restart" -ForegroundColor White
Write-Host "  Stop:         ssh to VM and run docker compose down" -ForegroundColor White
Write-Host "  Shell access: SSH to VM then run docker compose exec" -ForegroundColor White
Write-Host ""
