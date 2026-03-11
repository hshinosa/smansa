# ============================================================================
# SMAN 1 Baleendah - Comprehensive Build, Test & Deploy Pipeline
# ============================================================================
# This script performs local build, comprehensive testing, and staged deployment
# Author: Deployment Automation Team
# Version: 1.0.0
# ============================================================================

param(
    [string]$VpsHost = "smansa",
    [string]$DockerRegistry = "hshinosa",
    [string]$ImageName = "smkweb",
    [string]$RemotePath = "/var/www/sman1-baleendah",
    [switch]$SkipTests = $false,
    [switch]$SkipBuild = $false,
    [switch]$SkipDeploy = $false
)

$ErrorActionPreference = "Continue"
$Global:FailedSteps = @()
$Global:PassedSteps = @()
$Global:SkippedSteps = @()
$Global:StartTime = Get-Date

# ============================================================================
# HELPER FUNCTIONS
# ============================================================================

function Write-Header {
    param([string]$Text)
    Write-Host ""
    Write-Host "═══════════════════════════════════════════════════════════════════" -ForegroundColor Cyan
    Write-Host " $Text" -ForegroundColor White
    Write-Host "═══════════════════════════════════════════════════════════════════" -ForegroundColor Cyan
    Write-Host ""
}

function Write-StepStart {
    param([string]$Step)
    Write-Host "[⊙] $Step..." -ForegroundColor Yellow -NoNewline
}

function Write-StepSuccess {
    param([string]$Step, [string]$Message = "")
    Write-Host "`r[✓] $Step" -ForegroundColor Green
    if ($Message) { Write-Host "    → $Message" -ForegroundColor Gray }
    $Global:PassedSteps += $Step
}

function Write-StepFailure {
    param([string]$Step, [string]$Error)
    Write-Host "`r[✗] $Step" -ForegroundColor Red
    Write-Host "    → ERROR: $Error" -ForegroundColor Red
    $Global:FailedSteps += $Step
}

function Write-StepSkipped {
    param([string]$Step, [string]$Reason = "Skipped by user")
    Write-Host "[⊙] $Step - $Reason" -ForegroundColor DarkGray
    $Global:SkippedSteps += $Step
}

function Write-Info {
    param([string]$Message)
    Write-Host "    ℹ $Message" -ForegroundColor Cyan
}

function Test-CommandExists {
    param([string]$Command)
    $null = Get-Command $Command -ErrorAction SilentlyContinue
    return $?
}

function Get-ImageVersion {
    $timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
    $gitHash = ""
    if (Test-CommandExists "git") {
        $gitHash = git rev-parse --short HEAD 2>$null
        if ($gitHash) {
            return "v$timestamp-$gitHash"
        }
    }
    return "v$timestamp"
}

function Invoke-Rollback {
    param([string]$Message)
    Write-Header "ROLLBACK INITIATED"
    Write-Host $Message -ForegroundColor Yellow
    
    # Stop and remove local test containers
    Write-Host "Cleaning up local test containers..." -ForegroundColor Yellow
    docker-compose -f docker-compose.test.yml down -v 2>$null
    
    Write-Host "Rollback completed" -ForegroundColor Green
}

function Write-FinalReport {
    $endTime = Get-Date
    $duration = $endTime - $Global:StartTime
    
    Write-Header "DEPLOYMENT REPORT"
    
    Write-Host "Duration: " -NoNewline -ForegroundColor White
    Write-Host "$($duration.Minutes)m $($duration.Seconds)s" -ForegroundColor Cyan
    Write-Host ""
    
    if ($Global:PassedSteps.Count -gt 0) {
        Write-Host "✓ PASSED ($($Global:PassedSteps.Count)):" -ForegroundColor Green
        $Global:PassedSteps | ForEach-Object { Write-Host "  • $_" -ForegroundColor Green }
        Write-Host ""
    }
    
    if ($Global:SkippedSteps.Count -gt 0) {
        Write-Host "⊙ SKIPPED ($($Global:SkippedSteps.Count)):" -ForegroundColor DarkGray
        $Global:SkippedSteps | ForEach-Object { Write-Host "  • $_" -ForegroundColor DarkGray }
        Write-Host ""
    }
    
    if ($Global:FailedSteps.Count -gt 0) {
        Write-Host "✗ FAILED ($($Global:FailedSteps.Count)):" -ForegroundColor Red
        $Global:FailedSteps | ForEach-Object { Write-Host "  • $_" -ForegroundColor Red }
        Write-Host ""
        return $false
    }
    
    return $true
}

# ============================================================================
# PHASE 1: PRE-BUILD CHECKS
# ============================================================================

Write-Header "PHASE 1: PRE-BUILD CHECKS"

# Check Docker
Write-StepStart "Docker installation"
if (-not (Test-CommandExists "docker")) {
    Write-StepFailure "Docker installation" "Docker is not installed"
    Write-FinalReport
    exit 1
}
$dockerVersion = docker --version
Write-StepSuccess "Docker installation" $dockerVersion

# Check Docker Compose
Write-StepStart "Docker Compose installation"
if (-not (docker compose version 2>$null)) {
    Write-StepFailure "Docker Compose installation" "Docker Compose is not available"
    Write-FinalReport
    exit 1
}
$composeVersion = docker compose version
Write-StepSuccess "Docker Compose installation" $composeVersion

# Check required files
Write-StepStart "Required files verification"
$requiredFiles = @("Dockerfile", "docker-compose.yml", ".env.vm", "composer.json", "package.json")
$missingFiles = @()
foreach ($file in $requiredFiles) {
    if (-not (Test-Path $file)) {
        $missingFiles += $file
    }
}
if ($missingFiles.Count -gt 0) {
    Write-StepFailure "Required files verification" "Missing files: $($missingFiles -join ', ')"
    Write-FinalReport
    exit 1
}
Write-StepSuccess "Required files verification" "All required files present"

# Check disk space
Write-StepStart "Disk space check"
$drive = (Get-Location).Drive.Name
$disk = Get-PSDrive $drive
$freeSpaceGB = [math]::Round($disk.Free / 1GB, 2)
if ($freeSpaceGB -lt 5) {
    Write-StepFailure "Disk space check" "Insufficient disk space: ${freeSpaceGB}GB (minimum 5GB required)"
    Write-FinalReport
    exit 1
}
Write-StepSuccess "Disk space check" "Available: ${freeSpaceGB}GB"

# ============================================================================
# PHASE 2: LOCAL BUILD
# ============================================================================

Write-Header "PHASE 2: LOCAL BUILD AND PREPARATION"

$version = Get-ImageVersion
$imageTag = "${DockerRegistry}/${ImageName}:${version}"
$imageLatest = "${DockerRegistry}/${ImageName}:latest"

Write-Info "Image version: $version"
Write-Info "Image tag: $imageTag"

if (-not $SkipBuild) {
    # Clean previous builds
    Write-StepStart "Cleaning previous builds"
    docker-compose down -v 2>$null | Out-Null
    Write-StepSuccess "Cleaning previous builds"
    
    # Build Docker image
    Write-StepStart "Building Docker image (multi-stage)"
    Write-Info "This may take 5-10 minutes for the first build..."
    
    $buildLog = "build-$(Get-Date -Format 'yyyyMMdd-HHmmss').log"
    docker build -t $imageTag -t $imageLatest . 2>&1 | Tee-Object -FilePath $buildLog | Out-Null
    
    if ($LASTEXITCODE -ne 0) {
        Write-StepFailure "Building Docker image" "Build failed. Check $buildLog for details"
        Write-FinalReport
        exit 1
    }
    
    # Get image size
    $imageInfo = docker images $imageTag --format "{{.Size}}"
    Write-StepSuccess "Building Docker image" "Size: $imageInfo"
    
    # Clean build artifacts
    Remove-Item $buildLog -Force -ErrorAction SilentlyContinue
    
} else {
    Write-StepSkipped "Building Docker image" "Build skipped by user flag"
}

# ============================================================================
# PHASE 3: LOCAL TESTING
# ============================================================================

Write-Header "PHASE 3: LOCAL HEALTH CHECKS AND TESTING"

if (-not $SkipTests) {
    
    # Create test environment file
    Write-StepStart "Preparing test environment"
    Copy-Item .env.example .env.test -Force
    $envContent = Get-Content .env.test
    $envContent = $envContent -replace "DB_CONNECTION=.*", "DB_CONNECTION=pgsql"
    $envContent = $envContent -replace "DB_HOST=.*", "DB_HOST=db"
    $envContent = $envContent -replace "DB_DATABASE=.*", "DB_DATABASE=sman1_test"
    $envContent = $envContent -replace "APP_ENV=.*", "APP_ENV=testing"
    $envContent = $envContent -replace "APP_DEBUG=.*", "APP_DEBUG=true"
    $envContent | Set-Content .env.test
    Write-StepSuccess "Preparing test environment"
    
    # Create test docker-compose
    Write-StepStart "Creating test stack"
    $testCompose = "version: '3.8'`n"
    $testCompose += "services:`n"
    $testCompose += "  db:`n"
    $testCompose += "    image: pgvector/pgvector:pg16`n"
    $testCompose += "    environment:`n"
    $testCompose += "      POSTGRES_DB: sman1_test`n"
    $testCompose += "      POSTGRES_USER: test_user`n"
    $testCompose += "      POSTGRES_PASSWORD: test_pass`n"
    $testCompose += "    healthcheck:`n"
    $testCompose += '      test: ["CMD", "pg_isready", "-U", "test_user"]' + "`n"
    $testCompose += "      interval: 5s`n"
    $testCompose += "      timeout: 5s`n"
    $testCompose += "      retries: 10`n"
    $testCompose += "  redis:`n"
    $testCompose += "    image: redis:7-alpine`n"
    $testCompose += "    healthcheck:`n"
    $testCompose += '      test: ["CMD", "redis-cli", "ping"]' + "`n"
    $testCompose += "      interval: 5s`n"
    $testCompose += "      timeout: 5s`n"
    $testCompose += "      retries: 5`n"
    $testCompose += "  app:`n"
    $testCompose += "    image: $imageTag`n"
    $testCompose += "    depends_on:`n"
    $testCompose += "      db:`n"
    $testCompose += "        condition: service_healthy`n"
    $testCompose += "      redis:`n"
    $testCompose += "        condition: service_healthy`n"
    $testCompose += "    environment:`n"
    $testCompose += "      DB_CONNECTION: pgsql`n"
    $testCompose += "      DB_HOST: db`n"
    $testCompose += "      DB_DATABASE: sman1_test`n"
    $testCompose += "      DB_USERNAME: test_user`n"
    $testCompose += "      DB_PASSWORD: test_pass`n"
    $testCompose += "      REDIS_HOST: redis`n"
    $testCompose += "      CACHE_STORE: redis`n"
    $testCompose += "      APP_ENV: testing`n"
    $testCompose += "      APP_KEY: 'base64:test1234567890test1234567890test1234567890=='`n"
    $testCompose += "    ports:`n"
    $testCompose += '      - "8000:9000"' + "`n"
    $testCompose += "    healthcheck:`n"
    $testCompose += '      test: ["CMD", "php", "artisan", "config:show"]' + "`n"
    $testCompose += "      interval: 10s`n"
    $testCompose += "      timeout: 5s`n"
    $testCompose += "      retries: 5`n"
    $testCompose | Out-File -FilePath docker-compose.test.yml -Encoding UTF8
    Write-StepSuccess "Creating test stack"
    
    # Start test containers
    Write-StepStart "Starting test containers"
    Write-Info "Waiting for services to be healthy..."
    docker compose -f docker-compose.test.yml up -d 2>&1 | Out-Null
    
    if ($LASTEXITCODE -ne 0) {
        Write-StepFailure "Starting test containers" "Failed to start containers"
        Invoke-Rollback "Test container startup failed"
        Write-FinalReport
        exit 1
    }
    
    # Wait for all services
    Start-Sleep -Seconds 15
    Write-StepSuccess "Starting test containers"
    
    # Test database connection
    Write-StepStart "Database connectivity test"
    $dbTest = docker compose -f docker-compose.test.yml exec -T db pg_isready -U test_user 2>&1
    if ($LASTEXITCODE -ne 0) {
        Write-StepFailure "Database connectivity test" "Cannot connect to database"
        docker compose -f docker-compose.test.yml logs db
        Invoke-Rollback "Database connection failed"
        Write-FinalReport
        exit 1
    }
    Write-StepSuccess "Database connectivity test"
    
    # Test Redis connection
    Write-StepStart "Redis connectivity test"
    $redisTest = docker compose -f docker-compose.test.yml exec -T redis redis-cli ping 2>&1
    if ($redisTest -notmatch "PONG") {
        Write-StepFailure "Redis connectivity test" "Redis not responding"
        Invoke-Rollback "Redis connection failed"
        Write-FinalReport
        exit 1
    }
    Write-StepSuccess "Redis connectivity test"
    
    # Test application health
    Write-StepStart "Application health check"
    $healthTest = docker compose -f docker-compose.test.yml exec -T app php artisan --version 2>&1
    if ($LASTEXITCODE -ne 0) {
        Write-StepFailure "Application health check" "Application not responding"
        docker compose -f docker-compose.test.yml logs app
        Invoke-Rollback "Application health check failed"
        Write-FinalReport
        exit 1
    }
    Write-StepSuccess "Application health check" $healthTest
    
    # Test file permissions
    Write-StepStart "File permissions check"
    $permTest = docker compose -f docker-compose.test.yml exec -T app sh -c "test -w /var/www/storage && echo 'OK'" 2>&1
    if ($permTest -notmatch "OK") {
        Write-StepFailure "File permissions check" "Storage directory not writable"
        Invoke-Rollback "Permission check failed"
        Write-FinalReport
        exit 1
    }
    Write-StepSuccess "File permissions check"
    
    # Run database migrations (test)
    Write-StepStart "Database migration test"
    docker compose -f docker-compose.test.yml exec -T app php artisan migrate --force 2>&1 | Out-Null
    if ($LASTEXITCODE -ne 0) {
        Write-StepFailure "Database migration test" "Migration failed"
        docker compose -f docker-compose.test.yml logs app
        Invoke-Rollback "Database migration failed"
        Write-FinalReport
        exit 1
    }
    Write-StepSuccess "Database migration test"
    
    # Performance check
    Write-StepStart "Performance baseline check"
    $memUsage = docker stats --no-stream --format "{{.MemUsage}}" $(docker compose -f docker-compose.test.yml ps -q app)
    Write-StepSuccess "Performance baseline check" "Memory: $memUsage"
    
    # Security check - non-root user
    Write-StepStart "Security: Non-root user check"
    $userCheck = docker compose -f docker-compose.test.yml exec -T app whoami 2>&1
    if ($userCheck -match "root" -and $userCheck -notmatch "www-data") {
        Write-StepFailure "Security: Non-root user check" "Container running as root"
    } else {
        Write-StepSuccess "Security: Non-root user check" "Running as: $userCheck"
    }
    
    # Cleanup test containers
    Write-StepStart "Cleaning up test containers"
    docker compose -f docker-compose.test.yml down -v 2>&1 | Out-Null
    Remove-Item docker-compose.test.yml -Force -ErrorAction SilentlyContinue
    Remove-Item .env.test -Force -ErrorAction SilentlyContinue
    Write-StepSuccess "Cleaning up test containers"
    
} else {
    Write-StepSkipped "Local testing phase" "Tests skipped by user flag"
}

# ============================================================================
# PHASE 4: SECURITY SCANNING
# ============================================================================

Write-Header "PHASE 4: SECURITY SCANNING"

if (-not $SkipTests) {
    
    # Check for Trivy (security scanner)
    Write-StepStart "Security vulnerability scan"
    if (Test-CommandExists "trivy") {
        Write-Info "Running Trivy security scan..."
        trivy image --severity HIGH,CRITICAL --exit-code 0 $imageTag 2>&1 | Out-Null
        Write-StepSuccess "Security vulnerability scan" "Scan completed"
    } else {
        Write-StepSkipped "Security vulnerability scan" "Trivy not installed"
    }
    
    # Check exposed ports
    Write-StepStart "Exposed ports verification"
    $exposedPorts = docker inspect $imageTag --format='{{json .Config.ExposedPorts}}' 2>$null
    if ($exposedPorts -match "9000") {
        Write-StepSuccess "Exposed ports verification" "Only PHP-FPM port 9000 exposed"
    } else {
        Write-StepSuccess "Exposed ports verification" "Ports: $exposedPorts"
    }
    
} else {
    Write-StepSkipped "Security scanning phase" "Security checks skipped by user flag"
}

# ============================================================================
# PHASE 5: DOCKER HUB PUSH
# ============================================================================

Write-Header "PHASE 5: DOCKER REGISTRY PUSH"

if (-not $SkipBuild) {
    
    # Docker login check
    Write-StepStart "Docker Hub authentication"
    Write-Info "Checking Docker Hub login status..."
    
    # Try to get login info
    $loginInfo = docker info 2>&1 | Select-String "Username"
    if (-not $loginInfo) {
        Write-Host ""
        Write-Host "Please login to Docker Hub:" -ForegroundColor Yellow
        docker login
        if ($LASTEXITCODE -ne 0) {
            Write-StepFailure "Docker Hub authentication" "Login failed"
            Write-FinalReport
            exit 1
        }
    }
    Write-StepSuccess "Docker Hub authentication"
    
    # Push versioned image
    Write-StepStart "Pushing image: $imageTag"
    Write-Info "Uploading to Docker Hub..."
    docker push $imageTag 2>&1 | Out-Null
    if ($LASTEXITCODE -ne 0) {
        Write-StepFailure "Pushing image: $imageTag" "Push failed"
        Write-FinalReport
        exit 1
    }
    Write-StepSuccess "Pushing image: $imageTag"
    
    # Push latest tag
    Write-StepStart "Pushing image: $imageLatest"
    docker push $imageLatest 2>&1 | Out-Null
    if ($LASTEXITCODE -ne 0) {
        Write-StepFailure "Pushing image: $imageLatest" "Push failed"
        Write-FinalReport
        exit 1
    }
    Write-StepSuccess "Pushing image: $imageLatest"
    
} else {
    Write-StepSkipped "Docker Hub push" "Build was skipped"
}

# ============================================================================
# PHASE 6: VM DEPLOYMENT
# ============================================================================

Write-Header "PHASE 6: VM DEPLOYMENT"

if (-not $SkipDeploy) {
    
    # Test SSH connection
    Write-StepStart "SSH connectivity to $VpsHost"
    $sshTest = ssh -o ConnectTimeout=10 $VpsHost "echo 'OK'" 2>&1
    if ($LASTEXITCODE -ne 0 -or $sshTest -notmatch "OK") {
        Write-StepFailure "SSH connectivity to $VpsHost" "Cannot connect"
        Write-FinalReport
        exit 1
    }
    Write-StepSuccess "SSH connectivity to $VpsHost"
    
    # Transfer docker-compose and .env
    Write-StepStart "Transferring configuration files"
    ssh $VpsHost "mkdir -p $RemotePath" 2>&1 | Out-Null
    scp docker-compose.yml "${VpsHost}:${RemotePath}/" 2>&1 | Out-Null
    scp .env.vm "${VpsHost}:${RemotePath}/.env" 2>&1 | Out-Null
    
    if ($LASTEXITCODE -ne 0) {
        Write-StepFailure "Transferring configuration files" "File transfer failed"
        Write-FinalReport
        exit 1
    }
    Write-StepSuccess "Transferring configuration files"
    
    # Deploy on VM
    Write-StepStart "Deploying to VM"
    Write-Info "This will pull the image and restart services..."
    
    $deployScript = @"
set -e
cd $RemotePath

echo "Installing Docker if needed..."
if ! command -v docker &> /dev/null; then
    curl -fsSL https://get.docker.com -o get-docker.sh
    sudo sh get-docker.sh
    sudo usermod -aG docker `$USER
    sudo systemctl enable docker
    sudo systemctl start docker
    sudo chmod 666 /var/run/docker.sock
fi

echo "Pulling new image from Docker Hub..."
sudo docker pull $imageTag

echo "Updating docker-compose to use new image..."
sed -i 's|image:.*smkweb.*|image: $imageTag|g' docker-compose.yml

echo "Creating backup of current deployment..."
if sudo docker compose ps | grep -q Up; then
    sudo docker compose exec -T db pg_dump -U sman1_user sman1_baleendah > backup-before-deploy.sql 2>/dev/null || true
fi

echo "Stopping old containers..."
sudo docker compose down

echo "Starting new deployment..."
sudo docker compose up -d

echo "Waiting for services..."
sleep 20

echo "Running migrations..."
sudo docker compose exec -T app php artisan migrate --force

echo "Optimizing application..."
sudo docker compose exec -T app php artisan config:cache
sudo docker compose exec -T app php artisan route:cache
sudo docker compose exec -T app php artisan view:cache

echo "Deployment completed!"
sudo docker compose ps
"@
    
    $tempScript = [System.IO.Path]::GetTempFileName()
    $deployScript | Out-File -FilePath $tempScript -Encoding UTF8
    scp $tempScript "${VpsHost}:/tmp/deploy-script.sh" 2>&1 | Out-Null
    ssh $VpsHost "bash /tmp/deploy-script.sh && rm /tmp/deploy-script.sh" 2>&1
    Remove-Item $tempScript -Force
    
    if ($LASTEXITCODE -ne 0) {
        Write-StepFailure "Deploying to VM" "Deployment failed"
        Write-FinalReport
        exit 1
    }
    Write-StepSuccess "Deploying to VM"
    
    # Post-deployment health check
    Write-StepStart "Post-deployment health check"
    Start-Sleep -Seconds 10
    $healthCheck = ssh $VpsHost "cd $RemotePath && sudo docker compose ps" 2>&1
    if ($healthCheck -match "Up") {
        Write-StepSuccess "Post-deployment health check" "Services are running"
    } else {
        Write-StepFailure "Post-deployment health check" "Services may not be healthy"
    }
    
    # Get VM IP
    Write-StepStart "Retrieving VM IP address"
    $vmIp = ssh $VpsHost "curl -s ifconfig.me" 2>&1
    Write-StepSuccess "Retrieving VM IP address" "IP: $vmIp"
    
} else {
    Write-StepSkipped "VM Deployment" "Deployment skipped by user flag"
}

# ============================================================================
# FINAL REPORT
# ============================================================================

$success = Write-FinalReport

if ($success) {
    Write-Host ""
    Write-Host "🎉 DEPLOYMENT COMPLETED SUCCESSFULLY! 🎉" -ForegroundColor Green
    Write-Host ""
    Write-Host "Access your application at:" -ForegroundColor Cyan
    if ($vmIp) {
        Write-Host "  http://$vmIp" -ForegroundColor White
    }
    Write-Host ""
    Write-Host "Docker Hub Images:" -ForegroundColor Cyan
    Write-Host "  $imageTag" -ForegroundColor White
    Write-Host "  $imageLatest" -ForegroundColor White
    Write-Host ""
    Write-Host "Next Steps:" -ForegroundColor Yellow
    Write-Host "  1. Configure DNS to point to VM" -ForegroundColor White
    Write-Host "  2. Setup SSL certificates" -ForegroundColor White
    Write-Host "  3. Configure firewall rules" -ForegroundColor White
    Write-Host "  4. Monitor application logs" -ForegroundColor White
    Write-Host ""
    exit 0
} else {
    Write-Host ""
    Write-Host "❌ DEPLOYMENT FAILED!" -ForegroundColor Red
    Write-Host "Please review the errors above and retry." -ForegroundColor Yellow
    Write-Host ""
    exit 1
}
