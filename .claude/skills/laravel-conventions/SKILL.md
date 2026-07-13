---
name: laravel-conventions
description: >-
  Laravel 12 / PHP 8.4 のController・Service・Model・Migration・Form Request・Routeを
  実装/修正する時、PHPUnitテストを書く/実行する時、artisanコマンドを使う時、SDDフェーズ2(設計)で
  Laravelのクラス構成を検討する時、SDDフェーズ4(実装)でapp/・routes/・database/・config/・
  bootstrap/・tests/*.php 配下を変更する時に使う規約。
---

# Laravel 規約

返答は日本語。指定された作業範囲以外のコードは修正しない。

## 環境

- Laravel 12 / PHP 8.4（Docker イメージ `laravel_app:1.0`）
- リポジトリ直下に Laravel アプリ（`app/`, `routes/` 等）
- **Docker で実行**（`run_debug.bat` で起動、http://localhost:8000）。`php artisan serve` は使用しない
- DB: PostgreSQL 18（Docker、localhost:5433）
- 認証: Laravel 標準（session / Breeze 等）

## アーキテクチャ

- Controller は薄く保ち、ビジネスロジックは Service に置く
- Form Request でバリデーション
- Eloquent Model + Migration で DB 操作
- 命名: PSR-4、`StudlyCase` クラス、`camelCase` メソッド

## ディレクトリ

| パス | 用途 |
|------|------|
| `app/Http/Controllers/` | コントローラ |
| `app/Services/` | ビジネスロジック |
| `app/Models/` | Eloquent モデル |
| `app/Http/Requests/` | Form Request |
| `routes/web.php` | Web ルート |
| `database/migrations/` | マイグレーション |
| `tests/Unit/` | 単体テスト |
| `tests/Feature/` | 機能テスト |

## テスト

- レイヤ分担の正本: `.claude/skills/testing-pyramid/SKILL.md`
- PHPUnit: Service 単体・結合・FormRequest・**HTTP Feature（422 / 認可）**
- E2E（Playwright）: ジャーニー正常系と**画面固有**のクリティカル異常に限定。認可の HTTP 網羅は PHPUnit へ

## コマンド

artisan / PHPUnit は **Docker コンテナ内で実行** する。

```bash
run_debug.bat                 # 起動（起動確認込み）
run_debug.bat verify          # 起動確認のみ
docker compose exec app php artisan migrate
docker compose exec app php artisan test
docker compose exec app vendor/bin/phpunit --filter <TestClass>
```

## SDD 連携

- 実装判断は `docs/specs/<slug>/` を正とする
- 設計書（`02-design.md`）に記載されたクラス・メソッドから実装する
