# 仕様（Specs）

本ディレクトリは **仕様駆動開発（SDD）の正本** です。実装・テストの判断はここを優先します。

仕様は **Markdown** で管理します。

## ディレクトリ構成

```
docs/specs/
  _templates/          # 新機能開始時にコピーするテンプレート
  _registry.md         # slug と E2E 資産の対応表
  <slug>/              # 機能ごとの SDD 成果物
```

## 新機能の開始手順

1. `_templates/` を `<slug>/` にコピー（slug は英小文字・ハイフン推奨）
2. `meta.yaml` の `display_name` を設定
3. `_registry.md` に行を追加
4. Cursor で「仕様駆動で ○○ 機能を追加」と依頼（`sdd-feature` Skill が適用される）

## フェーズと承認

| フェーズ | 成果物 | 承認ファイル |
|---------|--------|-------------|
| 1. 仕様整理 | `01-requirements.md` | `01-requirements.status` |
| 2. 設計 | `02-design.md` | `02-design.status` |
| 3. テスト設計 | `03-test-plan.md`, `03-test-plan.csv` | `03-test-plan.status` |
| 4. 実装 | コード・テスト | — |

`*.status` の値: `draft` | `approved` | `rejected`

各フェーズで承認（`approved`）を得てから次へ進みます。

## 機能 ID について

命名規則は **未定（TBD）** です。`meta.yaml` の `feature_id` は `TBD` のままにできます。フォルダ名は `<slug>` を使用します。

## 関連設定

- Cursor Rules: `.cursor/rules/sdd-workflow.mdc` 他
- Cursor Skills: `.cursor/skills/sdd-bootstrap/SKILL.md`, `.cursor/skills/sdd-feature/SKILL.md`
- エージェント概要: リポジトリ直下の `AGENTS.md`
