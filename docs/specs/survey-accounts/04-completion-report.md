# 実装完了報告 — アンケートシステム アカウント基盤（認証・企業/部署/管理者/ユーザー管理）

> フェーズ 4 完了時に作成。承認ゲートの対象外（status ファイルなし）。

## 1. 実装サマリ

`02-design.md` に基づき、Laravel 標準セッション認証によるログイン・ログアウト、企業/部署/管理者/
ユーザーの登録・編集・無効化・再有効化、スーパーユーザーの全体/個別企業画面切替、ユーザー CSV
一括登録を実装した。認証・認可はロール判定ミドルウェア（`EnsureRole`）・アカウント有効性確認
ミドルウェア（`EnsureAccountActive`）・企業コンテキスト解決ミドルウェア（`SetCompanyContext`）で
実現し、Controller は薄く保ちビジネスロジックは `app/Services/` の各 Service に集約した。

## 2. テスト実行結果

| 種別 | 計画ケース数 | 実装ケース数 | 実行結果 | 実行日 |
|------|-------------|-------------|---------|--------|
| PHPUnit | 136 件 | 136 件 | 全件成功（既存 ExampleTest 2 件を含め 138 件成功） | 2026-07-13 |
| Vitest | 0 件（該当なし） | 0 件 | 該当なし（新規 JS モジュール追加なし） | — |
| Playwright E2E | 17 件 | 17 件 | 全件成功（既存 test_example.spec.ts 1 件を含め 18 件成功） | 2026-07-13 |

## 3. Test ID 突合（計画 CSV ↔ テストコード）

`npm run lint:sdd:testid -- survey-accounts` の結果:

```
── E2E（Playwright） ──
  ✓ CSV 17 件 / コード 17 件 — 一致

── PHPUnit ──
  ✓ CSV 136 件 / コード 136 件 — 一致

[survey-accounts] 突合結果: ERROR 0 件 / WARN 0 件
```

| CSV | 計画 Test ID 数 | 実装済み | 未実装（理由） |
|-----|----------------|---------|----------------|
| 03-test-plan.csv | 17 | 17 | なし |
| 03-test-plan-phpunit.csv | 136 | 136 | なし |
| 03-test-plan-vitest.csv | 0（該当なし） | 0 | 該当なし |

## 4. 基準未達・未実行項目

なし。PHPUnit 136 件・Playwright E2E 17 件とも計画どおり実装し全件成功を確認した。

## 5. エビデンス

| 項目 | パス / 値 |
|------|-----------|
| PHPUnit 実行ログ | `docker compose exec app php artisan test` → 138 passed (349 assertions) |
| Vitest 実行ログ | 該当なし |
| Playwright レポート・trace | `tests/e2e_tests/test-results/survey-accounts/`（`FEATURE=survey-accounts npx playwright test` 実行、18 passed） |

## 6. 残課題・申し送り

- **PHPUnit のテスト用 DB を PostgreSQL に変更した**: `phpunit.xml` の既定（SQLite `:memory:`）では、
  設計で採用した PostgreSQL 専用構文 `NULLS NOT DISTINCT`（`users_company_email_unique` 複合一意
  インデックス、VAL-06 の DB レベル最終防衛線）を含むマイグレーションが実行できずテストが全滅する
  ため、`DB_CONNECTION=pgsql` / `DB_DATABASE=laravel_db_test`（docker の `db` サービス上に新規作成）
  に変更した。ローカル環境のセットアップ時は `docker compose exec db psql -U postgres -c "CREATE DATABASE laravel_db_test;"`
  を一度実行しておく必要がある（既存 dev DB `laravel_db` とは別の使い捨て DB のため、直接操作しても
  開発データに影響しない）。今後 SQLite 前提の運用に戻す場合は本変更の要否を要検討。
- **企業コード採番の同時実行対策**: `CompanyService::nextCode()` は `MAX(code)` を読み取ってから
  採番するため、同時に複数の企業登録が走ると同一コードが算出され得るレースコンディションが
  存在した（Playwright を並列実行して実際に再現）。`CompanyService::create()` のトランザクション内で
  `LOCK TABLE companies IN SHARE ROW EXCLUSIVE MODE` を実行し直列化することで解消した。
- **`.env` 関連**: `.env.local` / `.env.example` に `SUPER_USER_EMAIL` / `SUPER_USER_PASSWORD`
  （既定値 `super@example.com` / `super1234`）を追加し、`.env.example` の `APP_LOCALE` 系を `ja` に
  変更した（IMPACT-11・IMPACT-13）。ローカル docker 環境の実 `.env` は `run_debug.bat` 実行時に
  `.env.local` からコピーされるため追加のセットアップは不要。
- E2E は Playwright の並列実行数と PHP-FPM の `pm.max_children=5`（`conf/php/`）の兼ね合いで、
  既定のワーカー数のまま実行すると request がキューイングされタイムアウトすることがある。
  ローカルで再実行する場合は `npx playwright test --workers=4` 程度に抑えることを推奨する
  （CI 設定 `playwright.config.ts` の `workers: process.env.CI ? 1 : undefined` は据え置き、
  本件はローカルでの手動実行時の注意点として申し送る）。
- 上記のうち「PHPUnit テスト用 DB の変更」「企業コード採番の同時実行対策」「PHP-FPM ワーカー数と
  Playwright 並列数の兼ね合い」はいずれも本機能固有の対応として実施した。プロジェクト共通規約への
  昇格要否（`.claude/rules/` 等への反映）は別途ユーザーに確認する。
