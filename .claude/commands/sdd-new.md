---
description: 仕様駆動開発（SDD）で新機能を開始する
argument-hint: [slug] [機能の説明（任意）]
---

# SDD 新機能の開始

仕様駆動開発（SDD）で新機能を開始する。`.claude/skills/sdd-feature/SKILL.md` と
`.claude/rules/sdd-workflow.md` に従うこと。

引数 `$ARGUMENTS` を以下として解釈する:

- 第 1 引数: slug（英小文字・ハイフン。例: `csv-import`）
- 残り: 機能の日本語名や仕様の説明（任意）

slug が指定されていない場合は、機能内容を確認してから slug を提案し、合意を得てから進める。

## 手順

1. `docs/specs/_templates/` を `docs/specs/<slug>/` にコピーする
2. `meta.yaml` の `display_name`・`slug`・`created`・`updated` を設定する
3. `docs/specs/_registry.md` に行を追加する（`feature_id` は TBD）
4. フェーズ 1（仕様整理）を開始する:
   - 提供された仕様（チャット・Markdown）を読み、`01-requirements.md` を作成する
   - バリデーション・受け入れ条件・非機能要件に一意な ID（`VAL-xx` / `AC-xx` / `NFR-xx`）を付与する
   - `effort-report.md` の「§1 人手想定工数」に、人手作業した場合のフェーズ別見積を根拠つきで記入する
   - 仕様に書かれていないこと・解釈が分かれることは**推測・仮定で埋めず**、
     `open-questions.md` に質問として列挙する（仮置きでの先行は禁止。
     詳細: `.claude/rules/sdd-workflow.md`「フェーズ 1 > 仮定の禁止」）
5. `open-questions.md` の質問をユーザーに提示して**停止**する

## 禁止事項

- フェーズ 1 の承認前に `02-design.md` 以降のファイルを作成しない
- `app/`・`resources/`・`routes/`・`database/` 等の実装コードを変更しない
