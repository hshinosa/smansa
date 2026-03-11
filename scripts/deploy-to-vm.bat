@echo off
REM deploy-to-vm.bat
REM Deploy storage dari Windows ke VM

echo ===================================
echo Deploy Storage to VM
echo ===================================
echo.

set VM_HOST=103.30.84.18
set VM_USER=ulung
set VM_STORAGE_PATH=/var/data/smansa
set SSH_KEY=%USERPROFILE%\.ssh\id_smansa
set LOCAL_STORAGE=storage\app\public

REM Check local storage
if not exist "%LOCAL_STORAGE%" (
    echo ERROR: Local storage not found: %LOCAL_STORAGE%
    pause
    exit /b 1
)

echo Source (Lokal):  %LOCAL_STORAGE%
echo Target (VM):     %VM_USER%@%VM_HOST%:%VM_STORAGE_PATH%
echo.

REM Create directory on VM
echo [1/3] Creating storage directory on VM...
ssh -i "%SSH_KEY%" %VM_USER%@%VM_HOST% "sudo mkdir -p %VM_STORAGE_PATH% && sudo chmod 755 %VM_STORAGE_PATH%"
if %errorlevel% neq 0 (
    echo ERROR: Failed to create directory on VM
    pause
    exit /b 1
)
echo        OK
echo.

REM Sync storage
echo [2/3] Syncing storage data...
echo         This may take a while...
echo.

scp -r -i "%SSH_KEY%" "%LOCAL_STORAGE%\*" %VM_USER%@%VM_HOST%:%VM_STORAGE_PATH%/
if %errorlevel% neq 0 (
    echo ERROR: Failed to sync files
    pause
    exit /b 1
)

echo        OK
echo.

REM Verify
echo [3/3] Verifying on VM...
ssh -i "%SSH_KEY%" %VM_USER%@%VM_HOST% "ls -la %VM_STORAGE_PATH%/"
echo.

echo ===================================
echo Deploy Complete!
echo ===================================
echo.
echo Next steps on VM:
echo   1. SSH ke VM: ssh -i %SSH_KEY% %VM_USER%@%VM_HOST%
echo   2. cd /var/www/smansa ^&^& git pull
echo   3. docker-compose -f docker-compose.storage.yml up -d
echo   4. docker-compose exec app php artisan storage:link
echo.
pause
