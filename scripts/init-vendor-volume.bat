@echo off
setlocal
echo [init-vendor] Cursor_Poc 専用 vendor ボリュームを初期化します...

docker volume inspect admin_prj_laravel_vendor >nul 2>&1
if errorlevel 1 (
  echo ERROR: admin_prj_laravel_vendor が見つかりません。LLax27 側で一度 Docker 起動してください。
  exit /b 1
)

docker volume create cursor_poc_laravel_vendor >nul 2>&1

docker run --rm ^
  -v admin_prj_laravel_vendor:/from:ro ^
  -v cursor_poc_laravel_vendor:/to ^
  alpine sh -c "cp -a /from/. /to/ && test -f /to/autoload.php && echo COPY_OK"

if errorlevel 1 (
  echo ERROR: vendor のコピーに失敗しました。
  exit /b 1
)

echo [init-vendor] 完了: cursor_poc_laravel_vendor （LLax27 とは独立）
endlocal
