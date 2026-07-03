# Cursor_Poc

Laravel 12 管理画面の仕様駆動開発（SDD）PoC プロジェクト。

## クイックスタート

```bat
scripts\init-vendor-volume.bat   # 初回のみ（LLax27 から vendor コピー）
run_debug.bat                    # Docker 起動 + 起動確認
```

- アプリ: http://localhost:8000
- PostgreSQL: localhost:5433

起動確認のみ:

```bat
run_debug.bat verify
```

## ドキュメント

- [AGENTS.md](AGENTS.md) — エージェント向けガイド・Docker 構成
- [docs/specs/README.md](docs/specs/README.md) — SDD ワークフロー
- [CHANGELOG.md](CHANGELOG.md) — リリースノート

## 開発

| 用途 | コマンド |
|------|---------|
| artisan | `docker compose exec app php artisan <command>` |
| PHPUnit | `docker compose exec app php artisan test` |
| Vite | `npm run dev`（ホスト） |
| Vitest | `npm run test`（ホスト） |
| Playwright | `cd tests/e2e_tests && npx playwright test`（ホスト） |
