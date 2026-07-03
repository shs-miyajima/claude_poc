---
name: sdd-feature
description: >-
  Cursor_Poc の仕様駆動開発（SDD）で新機能を追加する。仕様整理・設計・テスト設計・実装の
  各フェーズを docs/specs/ の成果物と承認ゲートで進める。機能追加・仕様駆動・SDD と
  言及されたときに使用する。
---

# 仕様駆動開発 — 機能追加

## 開始手順

1. `docs/specs/_templates/` を `docs/specs/<slug>/` にコピー
2. `meta.yaml` の `display_name` と `slug` を設定
3. `docs/specs/_registry.md` に行を追加（feature_id は TBD 可）
4. 仕様（チャット・Markdown）を読み、フェーズ 1 から開始

## フェーズチェックリスト

### フェーズ 1: 仕様整理

- [ ] `01-requirements.md` を作成・更新
- [ ] 不明点を `open-questions.md` に記載し、ユーザーに質問
- [ ] Laravel / フロント（Blade / Vite / JS）の責務分界を記載
- [ ] 確定後、ユーザー承認を得て `01-requirements.status` を `approved` に

**停止**: 承認までフェーズ 2 に進まない

### フェーズ 2: 設計

- [ ] `01-requirements.status` が `approved` であることを確認
- [ ] `02-design.md` に以下を記載:
  - 新規・変更クラス（Controller / Service / Model / Job 等）
  - メソッド概要（シグネチャレベル）
  - DB 変更（migration）
  - 画面・ルート・フロント（Blade / JS）
- [ ] 承認後 `02-design.status` を `approved` に

**停止**: 承認までフェーズ 3 に進まない

### フェーズ 3: テスト設計

- [ ] `02-design.status` が `approved` であることを確認
- [ ] `03-test-plan.md` にテスト方針・カテゴリ別件数を記載
- [ ] `03-test-plan.csv` にケース一覧（正常系・異常系・境界値・権限・派生パターン）
- [ ] 承認後 `03-test-plan.status` を `approved` に

**停止**: 承認までコード編集・テスト実行に進まない

### フェーズ 4: 実装・テスト

- [ ] `03-test-plan.status` が `approved` であることを確認
- [ ] Laravel 実装（リポジトリ直下）
- [ ] フロント実装（Blade / Vite / JavaScript / Tailwind）
- [ ] PHPUnit（Service 単体、該当時）
- [ ] Vitest（JS 単体、該当時）
- [ ] Playwright E2E（`tests/e2e_tests/`）
- [ ] 失敗時は原因分析 → 修正（最大 3 回）→ エスカレーション

## 承認の受け方

- ユーザーが「承認」「OK」「次へ」と返答 → 対応する `*.status` を `approved` に更新
- 差戻し → `rejected` + `changelog.md` に理由

## 参照

| リソース | パス |
|---------|------|
| ワークフロー Rule | `.cursor/rules/sdd-workflow.mdc` |
| Laravel 規約 | `.cursor/rules/laravel-conventions.mdc` |
| フロント規約 | `.cursor/rules/frontend-vite-tailwind.mdc` |
| Playwright 規約 | `.cursor/rules/testing-playwright.mdc` |
| Vitest 規約 | `.cursor/rules/testing-vitest.mdc` |

## テスト実行コマンド

```bash
# PHPUnit（ローカル）
php artisan test
# または
vendor/bin/phpunit --filter <TestClass>

# Vitest（プロジェクトルート）
npm run test

# Playwright（tests/e2e_tests ディレクトリ）
cd tests/e2e_tests
npx playwright test tests/test_<name>.spec.ts
```

## changelog.md の書き方

```markdown
## YYYY-MM-DD
- フェーズ: 設計
- 操作: rejected
- 理由: ○○の責務分界が不明
- 対応: 02-design.md の §3 を修正
```
