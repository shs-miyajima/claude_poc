# 機能レジストリ

`docs/specs/<slug>/` と E2E テスト資産の対応表。

| slug | display_name | feature_id | spec ファイル | test_case ディレクトリ | 備考 |
|------|-------------|------------|--------------|----------------------|------|
| （例）example-feature | （機能名） | TBD | tests/test_example.spec.ts | tests/test_case/example/ | |

## 追記ルール

- 新機能開始時（`_templates/` を `<slug>/` にコピーした直後）に行を追加する
- `feature_id` は命名規則確定まで `TBD` とする
- E2E 実装後に `spec ファイル` と `test_case ディレクトリ` を更新する
