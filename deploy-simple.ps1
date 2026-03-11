# SMAN 1 Baleendah Simple Deployment Script
# Pull Docker image from Docker Hub and start containers

param(
    [string]$VpsHost = "vm",
    [string]$RemotePath = "~/smkweb",
    [string]$DockerImage = "hshinosa/smkweb:latest"
)

$ErrorActionPreference = "Stop"

function Write-Status { param([string]$Message) Write-Host "[INFO] $Message" -ForegroundColor Green }
function Write-Warning { param([string]$Message) Write-Host "[WARNING] $Message" -ForegroundColor Yellow }
function Write-Error { param([string]$Message) Write-Host "[ERROR] $Message" -ForegroundColor Red }

Write-Status "🚀 Starting Simple Deployment (Pull & Run)..."

# 1. Transfer only necessary config files
Write-Status "Transferring configuration files to $VpsHost..."

# Create remote directory
ssh $VpsHost "mkdir -p $RemotePath"

# Transfer docker-compose.yml
Write-Status "Copying docker-compose.yml..."
scp docker-compose.yml "$VpsHost`:$RemotePath/docker-compose.yml"

# Transfer .env.production as .env
if (Test-Path .env.production) {
    Write-Status "Copying .env.production to VM..."
    scp .env.production "$VpsHost`:$RemotePath/.env"
} else {
    Write-Error ".env.production not found!"
    exit 1
}

# Transfer nginx config
Write-Status "Copying nginx configuration..."
ssh $VpsHost "mkdir -p $RemotePath/docker/nginx/sites"
scp -r docker/nginx/* "$VpsHost`:$RemotePath/docker/nginx/"

# Create nginx site config for cliproxy reverse proxy
Write-Status "Creating nginx reverse proxy configuration..."
$nginxSiteConfig = @"
# SMAN 1 Baleendah reverse proxy configuration
upstream sman1_backend {
    server 127.0.0.1:8080;
}

server {
    listen 80;
    listen [::]:80;
    server_name smansa.hshinoshowcase.site;

    # Let's Encrypt ACME challenge
    location /.well-known/acme-challenge/ {
        root /var/www/certbot;
    }

    # Redirect to HTTPS
    location / {
        return 301 https://`$host`$request_uri;
    }
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name smansa.hshinoshowcase.site;

    # SSL Configuration
    ssl_certificate /etc/nginx/ssl/cert.pem;
    ssl_certificate_key /etc/nginx/ssl/key.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    # Security headers
    add_header X-Frame-Options \"SAMEORIGIN\" always;
    add_header X-Content-Type-Options \"nosniff\" always;
    add_header X-XSS-Protection \"1; mode=block\" always;

    # Client max body size
    client_max_body_size 100M;

    # Proxy settings
    location / {
        proxy_pass http://sman1_backend;
        proxy_http_version 1.1;
        proxy_set_header Host `$host;
        proxy_set_header X-Real-IP `$remote_addr;
        proxy_set_header X-Forwarded-For `$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto `$scheme;
        proxy_set_header Connection \"\";
        proxy_buffering off;
        proxy_request_buffering off;
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
    }

    # Static files optimization
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        proxy_pass http://sman1_backend;
        proxy_cache_valid 200 1d;
        expires 1d;
        add_header Cache-Control \"public, immutable\";
    }
}
"@

# Save nginx config locally first
$nginxSiteConfig | Out-File -FilePath "sman1-baleendah.conf" -Encoding UTF8

# Transfer to VM
scp sman1-baleendah.conf "$VpsHost`:/tmp/sman1-baleendah.conf"
Remove-Item sman1-baleendah.conf

# 2. Remote execution: Pull image and start containers
Write-Status "Executing remote deployment..."

$remoteScript = @"
set -e
cd $RemotePath

echo '=== Step 1: Checking Docker installation ==='
if ! command -v docker &> /dev/null; then
    echo 'ERROR: Docker not installed. Please install Docker first.'
    exit 1
fi

echo '=== Step 2: Setting up nginx reverse proxy ==='
# Add sman1 site config to cliproxy nginx
if [ -f /tmp/sman1-baleendah.conf ]; then
    sudo mkdir -p /etc/nginx/sites-sman1
    sudo mv /tmp/sman1-baleendah.conf /etc/nginx/sites-sman1/sman1-baleendah.conf
    
    # Update cliproxy nginx.conf to include our site config
    if ! sudo grep -q 'include /etc/nginx/sites-sman1' /tmp/cliproxy/nginx.conf 2>/dev/null; then
        echo 'Adding sman1 site config to cliproxy nginx...'
        sudo sed -i '/include \/etc\/nginx\/conf.d\/\*.conf;/a\    include /etc/nginx/sites-sman1/*.conf;' /tmp/cliproxy/nginx.conf
    fi
    
    # Reload cliproxy nginx
    sudo docker exec cliproxy-nginx nginx -t && sudo docker exec cliproxy-nginx nginx -s reload
    echo 'Nginx reverse proxy configured successfully'
else
    echo 'WARNING: Nginx config not found, skipping reverse proxy setup'
fi

echo '=== Step 3: Pulling Docker image from Docker Hub ==='
docker pull $DockerImage

echo '=== Step 4: Stopping existing containers ==='
if docker compose ps -q 2>/dev/null | grep -q .; then
    docker compose down
    echo 'Existing containers stopped'
else
    echo 'No existing containers to stop'
fi

echo '=== Step 5: Starting containers with new image ==='
docker compose up -d

echo '=== Step 6: Waiting for containers to be healthy ==='
sleep 10

echo '=== Step 7: Checking container status ==='
docker compose ps

echo '=== Step 8: Running database migrations ==='
docker compose exec -T app php artisan migrate --force

echo '=== Step 9: Starting PHP built-in server on port 8080 ==='
docker compose exec -d app php -S 0.0.0.0:8080 -t /var/www/public

echo '=== Step 10: Clearing cache ==='
docker compose exec -T app php artisan config:cache
docker compose exec -T app php artisan route:cache
docker compose exec -T app php artisan view:cache

echo '=== Step 11: Final container health check ==='
docker compose ps

echo ''
echo '✅ Deployment completed successfully!'
echo ''
echo 'Container Status:'
docker compose ps --format 'table {{.Name}}\t{{.Status}}\t{{.Ports}}'
echo ''
echo 'Application URL: https://smansa.hshinoshowcase.site'

"@

Write-Status "Connecting to VM and executing deployment..."

try {
    ssh $VpsHost "bash -c `"$remoteScript`""
    
    Write-Status "✅ Deployment completed successfully!"
    Write-Host ""
    Write-Host "Next steps:" -ForegroundColor Cyan
    Write-Host "1. Configure DNS to point to VM IP" -ForegroundColor White
    Write-Host "2. Set up Cloudflare SSL" -ForegroundColor White
    Write-Host "3. Test the application: http://$(ssh $VpsHost 'curl -s ifconfig.me')" -ForegroundColor White
    
} catch {
    Write-Error "Deployment failed: $_"
    Write-Host ""
    Write-Host "Troubleshooting:" -ForegroundColor Yellow
    Write-Host "1. Check logs: ssh $VpsHost 'cd $RemotePath && docker compose logs'" -ForegroundColor White
    Write-Host "2. Check container status: ssh $VpsHost 'cd $RemotePath && docker compose ps'" -ForegroundColor White
    exit 1
}
