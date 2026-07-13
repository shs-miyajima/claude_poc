# claude_poc — エージェント向けガイド

## プロジェクト概要

Laravel 12 管理画面。仕様駆動開発（SDD）で機能を追加する。

| パス | 内容 |
|------|------|
| `app/` | Laravel アプリケーション（リポジトリ直下） |
| `resources/views/` | Blade テンプレート |
| `resources/js/` | Vite エントリ・axios・JavaScript |
| `tests/e2e_tests/` | Playwright E2E テスト（TypeScript） |
| `docs/specs/` | **仕様の正本（SDD）** |
| `conf/php/` | Docker 用 PHP 設定 |
| `conf/nginx/` | nginx 設定 |
| `latest_images/` | Docker イメージ tar |
| `docker-compose.yml` | Docker Compose 定義 |

ローカル起動: `run_debug.bat` → http://localhost:8000

## 技術スタック

| レイヤ | 技術 |
|--------|------|
| バックエンド | Laravel 12 / PHP 8.4（Docker イメージ） |
| DB | PostgreSQL 18（Docker） |
| フロント | Blade + Vite + axios + JavaScript + Tailwind CSS |
| 認証 | Laravel 標準（session / Breeze 等）※Breeze は今後追加 |
| 単体（サーバー） | PHPUnit（Service 単体・結合） |
| 単体（フロント） | Vitest（JavaScript 純関数） |
| E2E | Playwright（TypeScript）— ジャーニー正常系＋クリティカル異常 |

## Docker 構成（既存 LLax27 方式）

```
run_debug.bat
  → docker load（laravel_app_1.0.tar）
  → docker compose up -d

app（laravel_app:1.0）  … PHP-FPM + Composer
nginx                   … localhost:8000
db（postgres:18）       … localhost:5433
laravel_vendor          … cursor_poc_laravel_vendor（LLax27 とは独立）
```

### 起動・停止

```bat
run_debug.bat          # 起動 + 起動確認
run_debug.bat verify   # 起動確認のみ
run_debug.bat down     # 停止
run_debug.bat logs     # ログ表示
```

### artisan 実行

```bat
docker compose exec app php artisan migrate
docker compose exec app php artisan test
```

## 仕様駆動開発（SDD）

新機能追加は **仕様駆動** で進めます。詳細は `docs/specs/README.md` を参照。

### 必須ルール

- 返答は **日本語**
- 実装判断は `docs/specs/` を正とする
- 各フェーズの `*.status` が `approved` になるまで次フェーズに進まない
- 指定範囲以外のコードは修正しない

### Claude Code 設定

| ファイル | 用途 |
|---------|------|
| `CLAUDE.md` | 設定エントリポイント（本ファイルと常時適用の規約を読み込む） |
| `.claude/settings.json` | チーム共有設定（テスト・ビルド・lint コマンドの許可リスト） |
| `.claude/rules/sdd-workflow.md` | SDD フェーズ・承認ゲート（常時適用） |
| `.claude/rules/windows-file-editing-safety.md` | Windows でのファイル編集時の文字化け事故防止（常時適用） |
| `.claude/agents/sdd-requirements-reviewer.md` | フェーズ 1（仕様整理）の独立レビュー用サブエージェント（読み取り専用） |
| `.claude/agents/sdd-design-reviewer.md` | フェーズ 2（設計）の独立レビュー用サブエージェント（読み取り専用・コードベース裏取り） |
| `.claude/agents/sdd-plan-reviewer.md` | フェーズ 3（テスト計画）の独立レビュー用サブエージェント（読み取り専用） |
| `.claude/skills/sdd-bootstrap/SKILL.md` | プロジェクト立ち上げ |
| `.claude/skills/sdd-feature/SKILL.md` | 機能追加 |
| `.claude/skills/laravel-conventions/SKILL.md` | Laravel 規約（実装・設計時に参照） |
| `.claude/skills/frontend-vite-tailwind/SKILL.md` | フロント規約（実装・設計時に参照） |
| `.claude/skills/testing-pyramid/SKILL.md` | テストピラミッド（レイヤ分担）の正本 |
| `.claude/skills/testing-playwright/SKILL.md` | Playwright E2E 規約（テスト設計・実装時に参照） |
| `.claude/skills/testing-phpunit/SKILL.md` | PHPUnit 規約（テスト設計・実装時に参照） |
| `.claude/skills/testing-vitest/SKILL.md` | Vitest 規約（テスト設計・実装時に参照） |
| `.claude/commands/sdd-new.md` | `/sdd-new <slug>` — 新機能の開始（テンプレート配置〜フェーズ 1） |
| `.claude/commands/sdd-status.md` | `/sdd-status` — 全機能の SDD 進捗一覧 |

## テスト

テストピラミッドの正本: `.claude/skills/testing-pyramid/SKILL.md`

| レイヤ | コマンド |
|--------|----------|
| PHPUnit（単体・結合） | `docker compose exec app php artisan test` |
| Vitest（JS 単体） | `npm run test` |
| Playwright（ジャーニー・クリティカル異常） | `cd tests/e2e_tests && npx playwright test` |

```bat
docker compose exec app php artisan test     # PHPUnit
npm run test                                  # Vitest（ホスト）
cd tests/e2e_tests && npx playwright test    # E2E（ホスト）
```

## 注意（社内ネットワーク）

- コンテナ内 `composer install` は SSL 制約で失敗する場合あり
- 初回 vendor 準備: `scripts\init-vendor-volume.bat`（LLax27 の vendor を **コピー** するだけ。共有・書き換えなし）
- 新規パッケージ追加時は IT 部門に CA 証明書設定を相談

## 依頼例

```
仕様駆動で「○○」機能を追加してください。
仕様: docs/specs/<slug>/01-requirements.md
```
