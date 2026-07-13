# 変更履歴

<!-- 差戻し・承認・仕様変更の記録。reopened（差分承認で draft に戻した）時は変更理由も書く -->

## 2026-07-13

- フェーズ: 仕様整理
- 操作: approved
- 内容: 01-requirements.md を承認（UC 23 件・VAL 17 件・AC 26 件・NFR 5 件）。
  open-questions 全 16 件 resolved。独立レビュー（sdd-requirements-reviewer）で機械修正 1 件・
  要判断 2 件（Q-15 無効化済みエンティティの扱い / Q-16 無効部署所属ユーザーの編集）を
  ユーザー回答により解消済み。参考事項（日付範囲チェックなし・403 方式・CSV サイズ上限なし）は
  承認確認に提示の上、現仕様のまま承認

## 2026-07-13 (2)

- フェーズ: 設計
- 操作: rejected
- 内容: 独立レビュー（sdd-design-reviewer）の指摘 5 件（IMPACT-11/13・users.name 列長記述・
  FK onDelete 不統一・一覧ページング記載漏れ）は機械修正として反映済みだったが、承認確認提示後に
  ユーザーから §1 の論理削除方針の理由づけに指摘（「SoftDeletes でも RDB 上は論理削除データを
  取得できるはずでは」）があり差戻し。`withTrashed()`/`onlyTrashed()` により SoftDeletes でも
  論理削除済みデータの取得自体は可能なため、「クエリから自動除外されるため相性が悪い」という
  理由づけは不正確。正しい理由（デフォルトスコープの挙動が本要件の「原則全件表示」と逆方向で
  リスクがある点、削除ではなく無効化という業務概念との意味的な違い）に修正し、設計方針を
  維持するか SoftDeletes 採用に変更するかをユーザーに確認の上、再提示する

## 2026-07-13 (3)

- フェーズ: 設計
- 操作: reopened
- 内容: 差戻し理由（§1 の論理削除方針の理由づけ不正確）に対し、`deactivated_at` 維持を
  「削除ではなく業務ステータス変更のため `SoftDeletes`（`deleted_at`/`restore()`/`forceDelete()`
  という削除・復元の語彙）を転用しない」という理由に修正することでユーザーと合意。
  §1 の該当箇所を修正し、再度承認確認を提示する

## 2026-07-13 (4)

- フェーズ: 設計
- 操作: approved
- 内容: 02-design.md を承認。独立レビュー（sdd-design-reviewer）の機械修正 5 件
  （IMPACT-11/13・users.name 列長記述・FK onDelete 不統一・一覧ページング記載漏れ）と、
  ユーザー指摘による §1 論理削除方針の理由づけ修正（差戻し 1 回）を経て確定

## 2026-07-13 (5)

- フェーズ: テスト設計
- 操作: approved
- 内容: 03-test-plan.md・03-test-plan.csv（E2E 17 件）・03-test-plan-phpunit.csv（PHPUnit 136 件）・
  03-test-plan-vitest.csv（該当なし）を承認。PHPUnit → Vitest → E2E 棚卸し → E2E CSV の順で設計し、
  複合 VAL 分解表・閾値チェック一覧・要件カバレッジ表・回帰確認表を作成。独立レビュー
  （sdd-plan-reviewer）の指摘 6 件（更新系 Update 必須項目空入力ケースの欠落・エラーメッセージ
  文言の確定仕様との不一致・CSV 登録の role=user 403 確認欠落・出典 ID 種別不一致・CSV 行内
  入社年月日形式不正ケースの欠落・§2 カテゴリ別件数表の実カウント不一致）はすべて機械修正で
  解消（要判断の残課題なし）。`npm run lint:sdd -- survey-accounts` は ERROR 0 / WARN 0
