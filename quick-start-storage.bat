@echo off
REM quick-start-storage.bat
REM Quick start untuk Windows dengan separated storage

echo ===================================
echo SMAN 1 Baleendah - Storage Setup
echo ===================================
echo.

REM Check if running as admin
net session >nul 2>&1
if %errorlevel% neq 0 (
    echo WARNING: Jalankan sebagai Administrator untuk akses penuh
    echo.
)

REM Setup storage directory
set STORAGE_PATH=C:\smansa-data

echo [1/4] Membuat direktori storage...
if not exist "%STORAGE_PATH%" mkdir "%STORAGE_PATH%"
if not exist "%STORAGE_PATH%\media" mkdir "%STORAGE_PATH%\media"
if not exist "%STORAGE_PATH%\uploads" mkdir "%STORAGE_PATH%\uploads"
if not exist "%STORAGE_PATH%\documents" mkdir "%STORAGE_PATH%\documents"
if not exist "%STORAGE_PATH%\backups" mkdir "%STORAGE_PATH%\backups"
echo      OK: %STORAGE_PATH%
echo.

REM Copy existing storage data
echo [2/4] Migrasi data storage lokal...
if exist "storage\app\public" (
    xcopy /E /I /Y "storage\app\public\*" "%STORAGE_PATH%\" >nul 2>&1
    echo      OK: Data dicopy ke %STORAGE_PATH%
) else (
    echo      INFO: Tidak ada data lokal untuk dimigrasi
)
echo.

REM Create .env.storage
echo [3/4] Membuat konfigurasi environment...
echo STORAGE_BASE_PATH=%STORAGE_PATH% > .env.storage
echo      OK: .env.storage dibuat
echo.

REM Start Docker
echo [4/4] Starting Docker containers...
docker-compose -f docker-compose.storage.yml up -d
echo.

echo ===================================
echo Setup Complete!
echo ===================================
echo.
echo Storage location: %STORAGE_PATH%
echo.
echo Langkah selanjutnya:
echo   1. Tunggu container ready (docker-compose -f docker-compose.storage.yml logs -f)
echo   2. Buat storage link: docker-compose -f docker-compose.storage.yml exec app php artisan storage:link
echo   3. Akses web: http://localhost
echo.
echo Untuk info lebih lanjut, lihat: docs\SEPARATED_STORAGE.md
echo.
pause
