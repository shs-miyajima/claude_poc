# 実装完了報告 — アンケート作成（設問作成・編集・削除・下書き/公開・代理作成）

> フェーズ 4 完了時に作成する。`.claude/rules/sdd-workflow.md` の「実装完了の基準」を
> 満たしたことの証拠を記録し、完了報告に含める。
> 承認ゲートの対象外（status ファイルなし）。

## 1. 実装サマリ

`02-design.md` の設計どおり、アンケート（`surveys`/`questions`/`choices` の新設 3 テーブル、
`SurveyStatus`/`AnswerVisibility`/`QuestionType` の 3 Enum、`Survey`/`Question`/`Choice` モデル、
`SurveyService`（全置換方式の作成・更新・削除・公開）、`SurveyController`（一覧・作成・詳細・編集・更新・
削除・公開の 8 アクション）、`SurveyStoreRequest`/`SurveyUpdateRequest`（共通ルールは
`Concerns\SurveyRequestRules` トレイトに集約）を実装した。フロントは一覧/作成/編集/詳細の Blade 4 画面と
`_form`/`_question`/`_choice` 部分ビュー、設問・選択肢の動的追加/削除/並び替え・設問形式による表示切替を
行う `resources/js/surveyForm.js` を新規作成し、`layouts/app.blade.php` のナビと `company/home.blade.php`
のメニューに導線を追加した。

## 2. テスト実行結果

| 種別 | 計画ケース数 | 実装ケース数 | 実行結果 | 実行日 |
|------|-------------|-------------|---------|--------|
| PHPUnit | 86 | 86 | 全件成功 | 2026-07-14 |
| Vitest | 15 | 15 | 全件成功 | 2026-07-14 |
| Playwright E2E | 7 | 7 | 全件成功 | 2026-07-14 |

既存テスト（PHPUnit 138 件・Vitest 1 件）を含めた全体でも回帰なし
（PHPUnit 合計 224 件成功、Vitest 合計 16 件成功）。

Vitest が 14→15 件になっているのは、実装完了後のユーザーレビューによる差分承認
（§6 参照）でVT-015-dynを追加したため。

## 3. Test ID 突合（計画 CSV ↔ テストコード）

`npm run lint:sdd:testid -- survey-create` で確認。

| CSV | 計画 Test ID 数 | 実装済み | 未実装（理由） |
|-----|----------------|---------|----------------|
| 03-test-plan.csv | 7 | 7 | なし |
| 03-test-plan-phpunit.csv | 86 | 86 | なし |
| 03-test-plan-vitest.csv | 15 | 15 | なし |

ERROR 0 件（WARN 143 件はすべて他機能スペック〔survey-accounts 等〕の Test ID との
接頭辞重複によるもので、survey-create 自体の未実装はなし）。

## 4. 基準未達・未実行項目

| 対象 | 基準 | 実績 | 理由・今後の対応 |
|------|------|------|------------------|
| なし | — | — | 全レイヤの計画ケースを実装し全件成功。基準未達・未実行項目はなし |

## 5. エビデンス

| 項目 | パス / 値 |
|------|-----------|
| PHPUnit 実行ログ | `docker compose exec app php artisan test`（全体 224 件成功、うち本機能 86 件） |
| Vitest 実行ログ | `npm run test`（全体 16 件成功、うち本機能 15 件） |
| Playwright レポート・trace | `tests/e2e_tests/test-results/survey-create/`（全 7 件成功） |
| Test ID 突合ログ | `npm run lint:sdd:testid -- survey-create`（ERROR 0 件） |

## 6. 残課題・申し送り

- E2E-003（編集ジャーニーの並び替え）の実装検証中に、設問ブロックを並び替える際
  `name` 属性を単純に振り直すと、同じ `name` を一時的に共有するラジオボタン（`question_type`・
  `is_required`）がブラウザの「同名ラジオは 1 つしか選択できない」仕様により意図せず選択解除される
  不具合を検出した。`resources/js/surveyForm.js` の `renumber()` を、一旦衝突しない一時プレフィックス
  （`tmp`）に振り替えてから最終インデックスへ振り直す 2 段階方式に修正して解消した
  （Vitest の VT-003/VT-004 にラジオの選択状態が並び替え後も保持されることの検証を追加済み）
- 実装完了後のユーザーレビューで下記 2 点の指摘があり、差分承認・追加実装で対応した:
  1. 設問形式ラジオボタンの初期値を「単一選択」に変更（01-requirements.md §5.2 を差分承認のうえ修正。
     `_question.blade.php` の既定値と、「設問を追加」で新規ブロックを追加する際に選択肢入力欄の表示状態・
     削除ボタンの無効化状態を正しく初期化する処理を `surveyForm.js`（`addQuestionBlock`）に追加。
     検証として Vitest に VT-015-dyn を追加し 03-test-plan-vitest.csv を差分承認）
  2. バリデーションエラー発生時に該当入力欄を赤枠表示する対応が、設問文・設問形式・選択肢文言・
     段階評価ラベルの動的フィールドに欠けていたため追加（タイトル・回答期間・記名/匿名は既存対応済み）。
     仕様追加ではなく実装漏れの補完のため差分承認は不要と判断
- 上記以外の残課題なし
