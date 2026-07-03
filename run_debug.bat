@echo off
setlocal

if /i "%1"=="down" goto :stop_containers
if /i "%1"=="logs" goto :show_logs
if /i "%1"=="verify" goto :verify_only
if /i "%1"=="" goto :start_containers
if /i "%1"=="up" goto :start_containers
goto :show_usage

:start_containers
echo [1/4] .env...
if not exist ".env" (
    if exist ".env.local" (
        copy ".env.local" ".env" >nul
        echo   OK: copied .env.local to .env
    ) else (
        echo   NG: .env.local not found
        exit /b 1
    )
) else (
    echo   OK: .env exists
)

echo [2/4] docker load...
docker load -i latest_images/laravel_app_1.0.tar
if errorlevel 1 (
    echo   WARN: docker load failed (continue if laravel_app:1.0 exists)
)

echo [3/4] docker compose up...
docker compose up -d
if errorlevel 1 (
    echo   NG: docker compose up failed
    exit /b 1
)

echo [4/4] verify...
call scripts\verify-docker.bat
if errorlevel 1 (
    echo.
    echo Started but verify failed. Check: run_debug.bat logs
    exit /b 1
)

echo.
echo ====================================
echo Ready: http://localhost:8000
echo ====================================
echo   down  : run_debug.bat down
echo   logs  : run_debug.bat logs
echo   verify: run_debug.bat verify
goto :end

:verify_only
call scripts\verify-docker.bat
goto :end

:stop_containers
echo Stopping containers...
docker compose down
goto :end

:show_logs
docker compose logs -f --tail=100
goto :end

:show_usage
echo Usage: %0 [up^|down^|logs^|verify]
goto :end

:end
endlocal
