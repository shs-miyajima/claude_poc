---
name: sdd-bootstrap
description: >-
  Cursor_Poc プロジェクトの立ち上げ。Docker（laravel_app tar）+ Laravel 12 + Vite/Tailwind、
  docs/specs、Playwright、Vitest。プロジェクト立ち上げ・bootstrap と言及されたときに使用する。
---

# 仕様駆動開発 — プロジェクト立ち上げ

## 前提

- リポジトリ: Cursor_Poc（Laravel + SDD 同一リポジトリ）
- Docker: `laravel_app:1.0`（`latest_images/laravel_app_1.0.tar`）
- DB: PostgreSQL 18（Docker）
- フロント: Blade + Vite + axios + JavaScript + Tailwind CSS
- vendor: `cursor_poc_laravel_vendor`（初回は `scripts/init-vendor-volume.bat` で LLax27 からコピー）

## 立ち上げチェックリスト

### 1. Docker 環境

- [ ] Docker Desktop 起動
- [ ] `latest_images/laravel_app_1.0.tar` 存在
- [ ] `conf/php/` / `conf/nginx/` 存在
- [ ] `scripts\init-vendor-volume.bat` 実行済み（`cursor_poc_laravel_vendor` に vendor コピー）

### 2. Laravel

- [ ] リポジトリ直下に Laravel 12 ソース（`app/`, `artisan` 等）
- [ ] `.env.local` → `.env`（`run_debug.bat` が自動コピー）

### 3. 起動

```bat
run_debug.bat
```

- http://localhost:8000 で Welcome 画面確認

### 4. フロント（ホスト）

```bat
npm install
npm run dev
npm run build
```

### 5. SDD 初期構成

- [ ] `docs/specs/README.md`, `_registry.md`, `_templates/` 確認
- [ ] `AGENTS.md` 確認

### 6. Playwright（ホスト）

```bat
cd tests/e2e_tests
npm install
npx playwright install chromium
npx playwright test
```

### 7. Vitest（ホスト）

```bat
npm run test
```

### 8. 最初の機能フォルダ

- [ ] `docs/specs/_templates/` → `docs/specs/<slug>/`
- [ ] `_registry.md` に追記
- [ ] `sdd-feature` Skill でフェーズ 1 開始

## artisan / テスト（Docker 内）

```bat
docker compose exec app php artisan migrate
docker compose exec app php artisan test
```

## 注意

- コンテナ内 `composer install` は社内 SSL で失敗する場合あり
- 新規 Composer パッケージは IT 部門に CA 設定を相談
- Breeze 導入は composer 成功後に実施

## 参照

- `AGENTS.md`
- `docker-compose.yml`
- `run_debug.bat`
