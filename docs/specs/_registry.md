# 機能レジストリ

`docs/specs/<slug>/` と E2E テスト資産の対応表。

| slug | display_name | feature_id | spec ファイル | 備考 |
|------|-------------|------------|--------------|------|
| （例）example-feature | （機能名） | TBD | tests/test_example.spec.ts | |
| survey-system | アンケート作成・回答・管理システム（全体・親） | TBD | — | 親スペック（全体概要・Q&A 保持・承認ゲートなし） |
| survey-accounts | アンケートシステム アカウント基盤 | TBD | （E2E 実装後に追記） | ① フェーズ 1 承認済み（2026-07-13） |
| survey-create | アンケート作成 | TBD | — | ② 未着手（①完了後に開始） |
| survey-answer | アンケート回答 | TBD | — | ③ 未着手（②完了後に開始） |
| survey-dashboard | アンケートダッシュボード | TBD | — | ④ 未着手（③完了後に開始） |

## 追記ルール

- 新機能開始時（`_templates/` を `<slug>/` にコピーした直後）に行を追加する
- `feature_id` は命名規則確定まで `TBD` とする
- E2E 実装後に `spec ファイル` を更新する
