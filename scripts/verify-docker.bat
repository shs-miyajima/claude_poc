@echo off
setlocal

set FAILED=0

echo.
echo === Docker verify ===

echo [1/4] containers...
set RUNNING=0
for /f %%i in ('docker compose ps --status running -q 2^>nul') do set /a RUNNING+=1
if %RUNNING% LSS 3 (
    echo   NG: running %RUNNING%/3
    set FAILED=1
) else (
    echo   OK: running 3/3
)

echo [2/4] vendor...
docker compose exec -T app sh -c "test -f vendor/autoload.php" >nul 2>&1
if errorlevel 1 (
    echo   NG: vendor/autoload.php missing
    set FAILED=1
) else (
    echo   OK: vendor/autoload.php
)

echo [3/4] artisan...
docker compose exec -T app php artisan --version >nul 2>&1
if errorlevel 1 (
    echo   NG: artisan failed
    set FAILED=1
) else (
    for /f "delims=" %%v in ('docker compose exec -T app php artisan --version 2^>nul') do echo   OK: %%v
)

echo [4/4] HTTP http://localhost:8000 ...
powershell -NoProfile -Command "try { $r = Invoke-WebRequest -Uri 'http://localhost:8000' -UseBasicParsing -TimeoutSec 10; if ($r.StatusCode -eq 200) { exit 0 } else { exit 1 } } catch { exit 1 }"
if errorlevel 1 (
    echo   NG: HTTP 200 not received
    set FAILED=1
) else (
    echo   OK: HTTP 200
)

echo.
if %FAILED% EQU 0 (
    echo === ALL OK ===
    endlocal
    exit /b 0
) else (
    echo === FAILED ===
    endlocal
    exit /b 1
)
