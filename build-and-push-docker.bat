@echo off
REM build-and-push-docker.bat
REM Build Docker image dan push ke Docker Hub

echo ===================================
echo Build SMAN 1 Baleendah Docker Image
echo ===================================
echo.

REM Check Docker
docker --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: Docker tidak terinstall atau tidak running
    echo Pastikan Docker Desktop sudah running
    pause
    exit /b 1
)

REM Login ke Docker Hub
echo [1/4] Login ke Docker Hub...
set /p DOCKER_PASSWORD="Masukkan Docker Hub Password/Token: "
echo %DOCKER_PASSWORD% | docker login -u hshinosa --password-stdin
if %errorlevel% neq 0 (
    echo ERROR: Login gagal
    pause
    exit /b 1
)
echo        OK
echo.

REM Build image
echo [2/4] Building Docker image...
echo         This will take 10-20 minutes...
docker build -t hshinosa/smansa-web:latest .
if %errorlevel% neq 0 (
    echo ERROR: Build gagal
    pause
    exit /b 1
)
echo        OK
echo.

REM Tag image
echo [3/4] Tagging image...
docker tag hshinosa/smansa-web:latest hshinosa/smansa-web:v1.0
echo        OK
echo.

REM Push to Docker Hub
echo [4/4] Pushing to Docker Hub...
echo         This will take 5-10 minutes...
docker push hshinosa/smansa-web:latest
docker push hshinosa/smansa-web:v1.0
if %errorlevel% neq 0 (
    echo ERROR: Push gagal
    pause
    exit /b 1
)
echo        OK
echo.

echo ===================================
echo Build & Push Complete!
echo ===================================
echo.
echo Image tersedia di:
echo   - hshinosa/smansa-web:latest
echo   - hshinosa/smansa-web:v1.0
echo.
echo Di VM, jalankan:
echo   docker pull hshinosa/smansa-web:latest
echo.
pause
