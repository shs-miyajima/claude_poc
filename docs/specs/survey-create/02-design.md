# 設計 — アンケート作成（設問作成・編集・削除・下書き/公開・代理作成）

> status: 02-design.status を参照
> 前提: 01-requirements.status が `approved`

## 1. 設計方針

- **既存基盤の踏襲**: `survey-accounts` で実装済みの認証・ロール制御・テナント分離の仕組み
  （`EnsureRole` / `EnsureAccountActive` / `SetCompanyContext` ミドルウェア、`app('currentCompany')`
  によるコンテナバインディング経由の企業コンテキスト取得、Controller 側での
  `ensureCompanyMatch()` による手動 403 判定）をそのまま利用する。ミドルウェア・認証の仕組み自体には
  一切手を入れない
- **ルートグループ**: 管理者・個別企業画面のスーパーユーザーの両方が操作対象（§8）のため、
  既存の `users` / `departments` と同じ `role:super_user,admin` + `ctx` グループに
  `company.surveys.*` を追加する（`admins` 専用の `role:super_user` のみのグループには入れない）。
  全体画面のスーパーユーザー（未切替）は `ctx` ミドルウェアが企業解決に失敗して 403 にする
  （既存ロジックのまま。AC-07 は追加実装なしで満たされる）
- **削除方式（物理削除・全置換更新）**: `surveys` / `questions` / `choices` は物理削除方針（要件 §6）。
  下書き中は回答実績が存在しない（`survey-answer` は別スペックで未実装）ため、
  **アンケート編集時は既存の設問・選択肢を一旦すべて削除し、リクエスト内容で作り直す（全置換方式）**。
  設問・選択肢の追加/編集/削除/並び替えを個別 API に分けず、アンケート全体を 1 回の
  フォーム送信でまとめて保存する設計（要件 §5.3 で設計判断とされていた点の結論）。
  この方式により、画面側は各設問・選択肢に DB の `id` を持ち回る必要がなく（**送信データに
  `questions.*.id` は含めない**）、並び順も「配列のインデックス = `sort_order`」として扱えるため
  実装・画面 JS の両方が大幅に単純化される
- **カスケード削除は DB 制約に委譲**: `questions.survey_id` / `choices.question_id` の外部キーを
  `cascadeOnDelete()` にする。これにより、更新時の「既存設問を一括削除」も、アンケート自体の削除
  （UC-03）も、Eloquent 側で子テーブルを個別に削除するコードを書かずに DB レベルのカスケードで
  一貫して処理できる（`companies.id` / `users.id` への外部キーは `survey-accounts` の既存方針を継承し
  `restrictOnDelete()` のまま。企業・ユーザーは物理削除されない運用のため）
- **フォーム POST + サーバーサイドレンダリング（SPA 化しない）**: `survey-accounts` の画面方針
  （§5.4「①では SPA 的な非同期処理・新規 JS モジュールは追加しない」）を維持しつつ、本機能固有の
  「設問・選択肢の動的追加・削除・並び替え」は素の JavaScript（DOM 操作 + `name` 属性の
  再採番）で実現する。画面全体としては 1 回のフォーム POST でアンケート・設問・選択肢を
  まとめて保存する（axios によるバックグラウンド送信は使わない）
- **並び順 UI は上下ボタン方式（ドラッグ&ドロップ・新規ライブラリは使わない）**:
  open-questions #5 で「変更可能にする（UI 方式は 02-design で確定）」とされている点への回答。
  `package.json` にドラッグ&ドロップ用ライブラリ（Sortable.js 等）は未導入で、新規パッケージ追加は
  IT 部門への CA 証明書設定相談が必要になり本機能のスコープに対して過大なため、**設問の「↑」「↓」
  ボタンでの並び替え**（素の JS で DOM ノードを入れ替え、表示順どおりに `name` 属性の
  インデックスを振り直す）を採用する。ドラッグ&ドロップより操作性は落ちるが追加依存なしで実装でき、
  Playwright でのテストも安定する
- **カテゴリ値は backed enum で表現**: `survey-accounts` の `UserRole` / `Gender` と同じ方針で、
  `SurveyStatus`（下書き/公開）・`AnswerVisibility`（記名/匿名）・`QuestionType`
  （単一選択/複数選択/自由記述/段階評価）はいずれも PHP 8.4 backed string enum とし、
  画面表示用の `label(): string` メソッドを持たせる
- **VAL-12（自由記述回答 2,000 文字上限）の扱い**: 要件 §7 のとおり実際の入力検証は
  `survey-answer`（未実装）側の責務。本機能では固定値（設問ごとに設定不可・全設問共通）として
  `App\Models\Question::ANSWER_MAX_LENGTH = 2000` 定数のみを持ち、画面・DB カラムは追加しない
  （設定 UI を作らないのは要件どおり固定値のため）
- **Vite エントリは増やさない**: 新規 JS（設問・選択肢の動的操作）は既存の唯一のエントリ
  `resources/js/app.js` から読み込む新規モジュールとして追加し、`vite.config.js` の
  `input` は変更しない（既存 1 エントリ構成を維持）。モジュール側は対象 DOM
  （`data-survey-form` 属性等）が存在する画面でのみ初期化処理を実行するガード節を持ち、
  他画面の描画・動作に影響しない

## 2. Laravel

### 2.1 Controller

| クラス | 新規/変更 | メソッド | 概要 |
|--------|----------|----------|------|
| `App\Http\Controllers\Company\SurveyController` | 新規 | `index()` / `create()` / `store(SurveyStoreRequest)` / `show(Survey)` / `edit(Survey)` / `update(SurveyUpdateRequest, Survey)` / `destroy(Survey)` / `publish(Survey)` | アンケート一覧（`where('company_id', app('currentCompany')->id)` で自社スコープに絞り込み・20 件ページング・`orderByDesc('id')`）・新規作成画面・保存（設問・選択肢を含めて `SurveyService::create` に一括委譲）・詳細表示・編集画面（下書きのみ）・更新（全置換）・削除（物理削除）・公開 |

- `store` / `update` / `destroy` / `publish` はいずれも `company.surveys.index` へリダイレクト
  （既存の `DepartmentController` 等と同じ「一覧に戻る」規約に合わせる。詳細画面へは
  一覧の行クリック〔要件 §5.1〕で遷移する運用のため、保存直後の自動遷移先を詳細画面にはしない）
- `index()` は既存 `DepartmentController` / `UserController` の `orderBy('id')`（昇順）とは異なり
  `orderByDesc('id')`（降順）とする。作成中の下書きを編集・公開するために一覧へ戻る操作が
  ジャーニー上多く（UC-02, UC-06〜11 は下書きへの繰り返し編集）、直近に作成・操作したアンケートが
  先頭に表示される方が実用上有用なため意図的に採用する（既存一覧との不一致は許容する設計判断）
- 権限・状態チェックは既存 Controller と同じく手動判定とする（Policy は使わない）:
  - `private ensureCompanyMatch(Survey $survey): void` — `$survey->company_id !== app('currentCompany')->id` なら `abort(403)`（VAL-15）。`show` / `edit` / `update` / `destroy` / `publish` の冒頭で呼び出す
  - `private ensureEditable(Survey $survey): void` — `ensureCompanyMatch()` に加え `! $survey->isDraft()` なら `abort(403)`（VAL-14）。`edit` / `update` / `destroy` / `publish` の冒頭で呼び出す（公開後は「公開」操作自体も不可であることを含む）

### 2.2 Service

| クラス | 新規/変更 | メソッド | 概要 |
|--------|----------|----------|------|
| `App\Services\SurveyService` | 新規 | `create(Company $company, User $creator, array $data): Survey` / `update(Survey $survey, array $data): Survey` / `delete(Survey $survey): void` / `publish(Survey $survey): void` | アンケート + 設問 + 選択肢の一括保存・全置換更新・削除・公開 |

- `create()`: `DB::transaction` 内で `Survey::query()->create([...company_id, created_by, title,
  answer_start_date, answer_end_date, answer_visibility, status => SurveyStatus::Draft])` の後、
  private `syncQuestions(Survey $survey, array $questionsData): void` を呼び出して設問・選択肢を
  作成する
- `update()`: `DB::transaction` 内で `$survey->update([...title, answer_start_date, answer_end_date,
  answer_visibility])`（`status` はここでは変更しない）→ `$survey->questions()->delete()`
  （DB の `cascadeOnDelete` により配下の `choices` も自動削除される）→ `syncQuestions()` で
  リクエスト内容から作り直す
- `private syncQuestions(Survey $survey, array $questionsData): void`: `$questionsData` を
  配列インデックス順に走査し、各要素で `Question::query()->create([...survey_id, question_type,
  body, is_required（未指定時は false = 任意。open-questions #3）, sort_order => $index,
  scale_min_label, scale_max_label])` を作成。`question_type` が
  `QuestionType::SingleChoice` / `MultipleChoice` の場合のみ、`choices` 配列を同様に
  インデックス順で `Choice::query()->create([...question_id, body, sort_order => $choiceIndex])`。
  それ以外の設問形式では送信された `choices` があっても無視する（永続化しない）
- `delete()`: `$survey->delete()`（`cascadeOnDelete` により `questions` → `choices` まで
  DB レベルで連鎖削除される。UC-03）
- `publish()`: `$survey->update(['status' => SurveyStatus::Published])`

### 2.3 Model / Enum

| クラス | 新規/変更 | 概要 |
|--------|----------|------|
| `App\Models\Survey` | 新規 | fillable: `company_id` / `created_by` / `title` / `answer_start_date` / `answer_end_date` / `answer_visibility` / `status`。casts: `answer_start_date`・`answer_end_date` => `date`、`answer_visibility` => `AnswerVisibility::class`、`status` => `SurveyStatus::class`。`company(): BelongsTo`、`creator(): BelongsTo(User::class, 'created_by')`（画面には出さないが監査目的で保持）、`questions(): HasMany`（`orderBy('sort_order')`）、`isDraft(): bool`、`isPublished(): bool` |
| `App\Models\Question` | 新規 | fillable: `survey_id` / `question_type` / `body` / `is_required` / `sort_order` / `scale_min_label` / `scale_max_label`。casts: `question_type` => `QuestionType::class`、`is_required` => `bool`。`survey(): BelongsTo`、`choices(): HasMany`（`orderBy('sort_order')`）、`hasChoices(): bool`（`question_type` が単一選択/複数選択のとき true）。定数 `ANSWER_MAX_LENGTH = 2000`（VAL-12・`survey-answer` 側が参照する固定値） |
| `App\Models\Choice` | 新規 | fillable: `question_id` / `body` / `sort_order`。`question(): BelongsTo` |
| `App\Models\Company` | 変更 | 既存の `departments(): HasMany` / `users(): HasMany` と同じ一貫性のため `surveys(): HasMany` を追加する（`SurveyController` / `SurveyService` 自体は既存 `DepartmentController` 等と同様 `company_id` の直接クエリで完結し本リレーションを使わないが、既存モデルの慣例〔企業配下の全エンティティを `Company` からリレーションで辿れる〕に合わせる） |
| `App\Enums\SurveyStatus` | 新規 | backed enum: `Draft = 'draft'` / `Published = 'published'`。`label(): string`（下書き/公開） |
| `App\Enums\AnswerVisibility` | 新規 | backed enum: `Named = 'named'` / `Anonymous = 'anonymous'`。`label(): string`（記名/匿名） |
| `App\Enums\QuestionType` | 新規 | backed enum: `SingleChoice = 'single_choice'` / `MultipleChoice = 'multiple_choice'` / `FreeText = 'free_text'` / `Scale = 'scale'`。`label(): string`（単一選択/複数選択/自由記述/段階評価）、`hasChoices(): bool`（`Question::hasChoices()` から委譲呼び出しできる static 相当の判定。実装時に Model 側 or Enum 側どちらに置くかは実装フェーズで確定してよい軽微な判断） |

### 2.4 Migration

| ファイル名（案） | 操作 | 概要 |
|-----------------|------|------|
| `2026_07_14_000001_create_surveys_table.php` | CREATE | `id` / `company_id` FK→companies（`restrictOnDelete`）/ `created_by` FK→users（`restrictOnDelete`）/ `title` string(500) / `answer_start_date` date / `answer_end_date` date / `answer_visibility` string(20) / `status` string(20) default `'draft'` / timestamps |
| `2026_07_14_000002_create_questions_table.php` | CREATE | `id` / `survey_id` FK→surveys（`cascadeOnDelete`）/ `question_type` string(20) / `body` string(500) / `is_required` boolean default `false` / `sort_order` unsignedInteger / `scale_min_label` string(500) nullable / `scale_max_label` string(500) nullable / timestamps |
| `2026_07_14_000003_create_choices_table.php` | CREATE | `id` / `question_id` FK→questions（`cascadeOnDelete`）/ `body` string(500) / `sort_order` unsignedInteger / timestamps |

- 一意制約は設けない（同一企業内でのタイトル重複を許容する要件 §5.2・open-questions #9 のため、
  `surveys.title` に一意制約は不要。`questions` / `choices` も並び順のみで一意性の要件なし）
- インデックス方針は `survey-accounts` を継承し、外部キーの自動インデックスのみ追加する
  （規模要件は NFR-01 でスコープ外のため、`status` 等への追加インデックスは行わない）
- `title` / `body`（設問文・選択肢文言）/ `scale_min_label` / `scale_max_label` はいずれも
  string(500)（親決定 Q33 の一律 500 文字上限。VAL-02, VAL-07, VAL-11, VAL-13 と一致）

### 2.5 View / Route

ルートは既存の `role:super_user,admin` + `ctx` グループ（`routes/web.php` の
`users` / `departments` と同じブロック）に追加する。ルート宣言順は `create`（静的パス）を
`{survey}` を含むワイルドカードルートより前に置く（既存 `users` / `departments` と同じ回避策）。

| View | Route（name / URL / method） | 概要 |
|------|------|------|
| `company/surveys/index.blade.php` | `company.surveys.index` GET `/company/surveys` | アンケート一覧（状態バッジ〔下書き/公開〕・20 件ページング・「新規作成」導線・行クリックで詳細へ） |
| `company/surveys/create.blade.php` | `company.surveys.create` GET `/company/surveys/create`・`company.surveys.store` POST `/company/surveys` | 新規作成（`_form` 部分ビューを空状態で描画） |
| `company/surveys/show.blade.php` | `company.surveys.show` GET `/company/surveys/{survey}` | 詳細（設問構成の読み取り専用表示。下書きのみ「編集」「削除」「公開」導線を表示。作成者情報は表示しない〔open-questions #7〕・回答数/回答率も表示しない〔open-questions #11〕） |
| `company/surveys/edit.blade.php` | `company.surveys.edit` GET `/company/surveys/{survey}/edit`・`company.surveys.update` PUT `/company/surveys/{survey}` | 編集（下書きのみ。`_form` 部分ビューを既存データで描画。公開済みは `ensureEditable` で 403） |
| —（詳細画面内操作） | `company.surveys.destroy` DELETE `/company/surveys/{survey}`・`company.surveys.publish` POST `/company/surveys/{survey}/publish` | 削除（物理削除・確認ダイアログ経由）・公開 |
| `company/surveys/_form.blade.php` | —（部分ビュー、ルートなし） | create/edit 共通のアンケート + 設問 + 選択肢の入力フォーム。`data-survey-form` を付与し JS 初期化の対象にする |

- FormRequest（新規・`app/Http/Requests/`）: `SurveyStoreRequest` / `SurveyUpdateRequest`
  （§6 に検証ルール対応を記載。両者は同一のバリデーションルールになる見込みで、実装時に
  共通ルールをどちらかに寄せる／trait化するかは実装フェーズの軽微な判断とする）
- `layouts/app.blade.php` のナビに `company.surveys.index` へのリンク（`data-testid="nav-surveys"`）
  を追加する（既存ナビ項目と同じ並びに追加。既存 E2E は `getByTestId` で個別要素を参照しており
  ナビ項目の追加自体では既存テストは壊れない。§5 IMPACT-02 参照）
- バリデーションメッセージは既存規約どおり各 FormRequest の `messages()` に §7 の確定文言を定義し、
  `lang/ja/validation.php` の `attributes` に新規項目（`title` / `answer_start_date` /
  `answer_end_date` / `answer_visibility` 等）を追記する（既存キーは変更しない）

### 2.6 Job（該当時）

なし（同期処理で完結するため非同期 Job は使わない）。

### 2.7 Factory（PHPUnit 用）

| クラス | 新規/変更 | 概要 |
|--------|----------|------|
| `Database\Factories\SurveyFactory` | 新規 | `company_id => Company::factory()` / `created_by => User::factory()->admin()` / `title` はダミー文字列 / `answer_start_date` / `answer_end_date`（開始 <= 終了になるダミー範囲）/ `answer_visibility => AnswerVisibility::Named` / `status => SurveyStatus::Draft`。state `published()` |
| `Database\Factories\QuestionFactory` | 新規 | `survey_id => Survey::factory()` / `body` ダミー文字列 / `question_type => QuestionType::FreeText`（既定） / `is_required => false` / `sort_order => 0`。state `singleChoice()` / `multipleChoice()` / `scale()` |
| `Database\Factories\ChoiceFactory` | 新規 | `question_id => Question::factory()` / `body` ダミー文字列 / `sort_order => 0` |

## 3. フロント（Blade / Vite / JavaScript）

### 3.1 Blade

| ファイル | 新規/変更 | 概要 |
|---------|----------|------|
| `resources/views/company/surveys/index.blade.php` | 新規 | 一覧（状態バッジ、20 件ページング、行クリックで詳細へ、「新規作成」ボタン） |
| `resources/views/company/surveys/create.blade.php` | 新規 | `@include('company.surveys._form')` を空データで描画 |
| `resources/views/company/surveys/edit.blade.php` | 新規 | `@include('company.surveys._form', ['survey' => $survey])` を既存データで描画 |
| `resources/views/company/surveys/show.blade.php` | 新規 | 詳細（設問一覧・選択肢・必須/任意・段階評価ラベルの読み取り専用表示。下書き時のみ編集/削除/公開ボタン） |
| `resources/views/company/surveys/_form.blade.php` | 新規 | アンケート本体項目 + 設問リピーター（`<template>` タグによる複製 + 「設問を追加」「削除」「↑」「↓」ボタン）+ 単一選択/複数選択時の選択肢リピーター（同様の追加・削除ボタン、選択肢が 2 件のときは JS 側で「削除」ボタンを無効化し 1 件まで減らせないようにする。サーバー側 VAL-10 は不正なリクエスト（無効化を回避した直接送信等）に対する保険として維持する） |
| `resources/views/layouts/app.blade.php` | 変更 | ナビに「アンケート一覧」リンク（`data-testid="nav-surveys"`）を追加 |
| `resources/views/company/home.blade.php` | 変更 | 要件 §5.1「アンケート一覧の遷移元 = 企業ホーム画面のメニュー」に対応するため、既存の `管理者管理` / `ユーザー管理` / `部署マスタ管理` 等のメニュー項目と同じ形式で `<li><a href="{{ route('company.surveys.index') }}" data-testid="home-surveys">アンケート管理</a></li>` 相当のリンクを追加する |

### 3.2 JavaScript（Vite エントリ）

| ファイル | 新規/変更 | 概要 |
|---------|----------|------|
| `resources/js/surveyForm.js` | 新規 | `data-survey-form` 要素が存在する画面でのみ初期化。設問ブロックの追加（`<template>` 複製）・削除（`confirm()` 確認後に DOM 除去）・上下移動（DOM ノード入れ替え）、単一選択/複数選択設問内の選択肢の追加・削除、設問形式（ラジオボタン）変更に応じた選択肢入力欄・段階評価ラベル入力欄の表示切替。選択肢が 2 件のときは「削除」ボタンに `disabled` を付与し 1 件まで減らせないようにする（3 件以上に戻ったら再度有効化する）。いずれの操作後も、表示順どおりに全ブロックの `name="questions[n][...]"` / `name="questions[n][choices][m][...]"` の `n`/`m` を振り直す（送信データの配列順序 = 保存時の `sort_order` になるため） |
| `resources/js/app.js` | 変更 | `import './surveyForm'` を追加（ガード節により対象画面以外では何もしない） |

<!-- resources/js/ は JavaScript のみ。TypeScript は E2E（Playwright）専用 -->

## 4. シーケンス（主要フロー）

### アンケート新規作成（UC-01, UC-06, UC-10, UC-11）

```
ブラウザ → POST /company/surveys（title, answer_start_date, answer_end_date,
                                   answer_visibility, questions[n][...], questions[n][choices][m][...]）
  → SurveyStoreRequest（VAL-01〜17 のうち §6 記載の各ルール）
  → SurveyController@store → SurveyService::create(currentCompany, currentUser, validated)
      DB::transaction:
        Survey::create(status = draft)
        → syncQuestions: questions を配列順に Question::create（sort_order = index）
            → 単一選択/複数選択なら choices を配列順に Choice::create（sort_order = index）
  → redirect company.surveys.index（一覧に下書き状態で表示される）
```

### アンケート編集・全置換更新（UC-02, UC-07, UC-08, UC-09）

```
ブラウザ → GET /company/surveys/{survey}/edit
  → ensureEditable（company一致 + isDraft。公開済みなら 403・VAL-14）
  → 既存の questions/choices を _form に展開して表示
ブラウザ → PUT /company/surveys/{survey}（新しい questions[n][...] 一式）
  → SurveyUpdateRequest（§6 と同じルール）
  → SurveyController@update → ensureEditable → SurveyService::update(survey, validated)
      DB::transaction:
        survey 本体項目を update
        → questions()->delete()（cascadeOnDelete で配下 choices も DB レベルで連鎖削除）
        → syncQuestions で新しい内容から作り直す
  → redirect company.surveys.index
```

### 公開・削除（UC-03, UC-04）

```
POST /company/surveys/{survey}/publish → ensureEditable → SurveyService::publish
  → status: draft → published（以後 ensureEditable が isDraft()=false で 403 を返すため
     編集・削除・再公開はすべて不可になる。VAL-14）

DELETE /company/surveys/{survey} → ensureEditable → SurveyService::delete
  → $survey->delete()（cascadeOnDelete で questions → choices まで連鎖削除。UC-03）
```

### 代理作成（UC-12）

```
スーパーユーザーが個別企業画面に切替済み（survey-accounts の super.switch.enter 済み、
session の acting_company_id 設定済み）の状態で上記フローをそのまま実行する。
SetCompanyContext（変更なし）が app('currentCompany') を切替中企業に解決するため、
SurveyController / SurveyService は「管理者が自社で操作している」場合と全く同じコードパスになる。
Survey.created_by にはスーパーユーザー自身の user_id が保存されるが、
show/index の Blade は created_by（creator リレーション）を一切描画しないため
画面に作成者情報は表示されない（open-questions #7）。
```

## 5. 影響範囲

| ID | 対象 | 影響あり/なし | 内容・理由 |
|----|------|--------------|-----------|
| IMPACT-01 | `routes/web.php` | あり（追加のみ） | 既存の `role:super_user,admin` + `ctx` グループに `surveys.*` 8 ルートを追加する。既存ルート（`users` / `departments` / `admins` 等）の定義・順序は変更しない |
| IMPACT-02 | `resources/views/layouts/app.blade.php` | あり | ナビに `data-testid="nav-surveys"` リンクを追加。既存 E2E（`test_admin.spec.ts` 等）は `page.getByTestId('nav-xxx')` で個別要素を指定して参照しており、位置や兄弟要素数に依存する記述はない（grep で確認済み）ため、リンク追加による既存 E2E への破壊的影響はない |
| IMPACT-03 | `app/Http/Middleware/EnsureRole.php` / `EnsureAccountActive.php` / `SetCompanyContext.php` | なし | いずれも変更しない。新規ルートは既存グループにぶら下げるだけで、ミドルウェアのロジックはそのまま利用できる（全体画面スーパーユーザーの 403〔AC-07〕も `SetCompanyContext` の既存判定でそのまま満たされる） |
| IMPACT-04 | `resources/js/app.js` | あり | `import './surveyForm'` を追加。`surveyForm.js` は `data-survey-form` 要素の有無をガード節で確認してから初期化するため、他画面（ログイン・ユーザー管理等）の描画・動作には影響しない |
| IMPACT-05 | `vite.config.js` / `package.json` | なし | 新規 Vite エントリ・新規 npm パッケージのいずれも追加しない（既存 `resources/js/app.js` 単一エントリのまま） |
| IMPACT-06 | 既存テーブル（`companies` / `users`） | なし（参照のみ） | `surveys.company_id` / `surveys.created_by` から外部キー参照するのみで、既存テーブルへのカラム追加・変更は行わない |
| IMPACT-07 | 既存 PHPUnit（`tests/Feature/Http/AuthorizationTest.php` ほか `survey-accounts` のテスト） | なし | 新規コントローラ・ルート・テーブルの追加のみで、既存のテナント分離・403 判定ロジック自体は変更しない。本機能固有の 403 パターン（VAL-14, VAL-15）は本機能のテスト計画（フェーズ 3）で新規ケースとして追加する |
| IMPACT-08 | 既存 E2E（`test_admin.spec.ts` / `test_department.spec.ts` / `test_user.spec.ts` / `test_user_csv.spec.ts` / `test_company.spec.ts` / `test_login.spec.ts` / `test_example.spec.ts`） | なし | いずれも対象画面・操作フローが本機能と独立しており、ナビ追加（IMPACT-02）以外に参照されるセレクタ・データ前提の変更はない |
| IMPACT-09 | `lang/ja/validation.php` | あり（追記のみ） | `attributes` 配列に本機能の新規項目名（`title` / `answer_start_date` / `answer_end_date` / `answer_visibility` / 設問・選択肢関連）を追記する。既存キーの値は変更しない |
| IMPACT-10 | `database/seeders/DatabaseSeeder.php` | なし | 本機能はシーダーの新規データ投入を必要としない（PHPUnit は Factory で個別にデータ作成するため） |
| IMPACT-11 | `resources/views/company/home.blade.php` | あり（追記のみ） | 要件 §5.1 が明示する遷移元（企業ホーム画面のメニュー）に対応するため「アンケート管理」リンクを追加する。既存 E2E（`test_admin.spec.ts` 等）は企業ホーム画面到達の確認を `data-testid="company-home"` 等の可視性のみで行っており、メニュー項目一覧の内容・件数に依存するアサーションはないため、リンク追加による既存 E2E への破壊的影響はない |

## 6. 検証ルール対応（VAL × 実装）

| VAL ID | サブルール・観点 | 検証箇所（FormRequest / Service 等のクラス・メソッド） | 備考 |
|--------|------------------|--------------------------------------------------------|------|
| VAL-01 | タイトル必須 | `SurveyStoreRequest` / `SurveyUpdateRequest`::`rules()`（`title` => `required`） | |
| VAL-02 | タイトル最大 500 文字 | 同上（`title` => `max:500`） | |
| VAL-03 | 回答期間（開始日・終了日）必須 | 同上（`answer_start_date` / `answer_end_date` => `required`, `date_format:Y-m-d`） | |
| VAL-04 | 終了日が開始日より前 | 同上（`answer_end_date` => `after_or_equal:answer_start_date`） | |
| VAL-05 | 記名/匿名未選択 | 同上（`answer_visibility` => `required`, `Rule::enum(AnswerVisibility::class)`） | |
| VAL-06 | 設問文未入力 | 同上（`questions.*.body` => `required`） | |
| VAL-07 | 設問文最大 500 文字 | 同上（`questions.*.body` => `max:500`） | |
| VAL-08 | 設問数が 101 件目 | 同上（`questions` => `array`, `max:100`） | |
| VAL-09 | 選択肢が 11 件目（上限超過） | 同上 `withValidator()`（`question_type` が単一選択/複数選択の設問ごとに `choices` 件数を判定） | 標準の配列ルールでは兄弟フィールド〔`question_type`〕条件付きの件数チェックができないため `withValidator()` のクロージャで実装 |
| VAL-10 | 選択肢が 1 件のみ（下限未満） | 同上 `withValidator()`（同上、件数 2 未満を判定） | VAL-09 と同じクロージャ内で判定 |
| VAL-11 | 選択肢文言が最大 500 文字超過 | 同上（`questions.*.choices.*.body` => `max:500`） | |
| VAL-12 | 自由記述回答 2,000 文字上限 | `App\Models\Question::ANSWER_MAX_LENGTH` 定数（`survey-answer` 側で使用） | 本機能では入力検証しない（要件どおり）。設問定義時の固定値として記録するのみ |
| VAL-13 | 段階評価両端ラベル最大 500 文字 | `SurveyStoreRequest` / `SurveyUpdateRequest`（`questions.*.scale_min_label` / `scale_max_label` => `nullable`, `max:500`） | |
| VAL-14 | 公開済みアンケートへの編集・削除操作（URL 直接アクセス含む） | `SurveyController::ensureEditable()`（`edit` / `update` / `destroy` / `publish` の冒頭で呼び出し） | |
| VAL-15 | 他社のアンケートへのアクセス | `SurveyController::ensureCompanyMatch()`（`show` / `edit` / `update` / `destroy` / `publish` の冒頭で呼び出し。`ensureEditable()` からも内部的に呼び出される）。一覧（`index()`）は個別アクセスの 403 判定ではなく `where('company_id', app('currentCompany')->id)` によるクエリスコープで自社分のみを表示する（NFR-02） | |
| VAL-16 | 設問形式が未選択 | `SurveyStoreRequest` / `SurveyUpdateRequest`（`questions.*.question_type` => `required`, `Rule::enum(QuestionType::class)`） | |
| VAL-17 | 選択肢文言が未入力（個々の選択肢） | 同上（`questions.*.choices.*.body` => `required`。ただし単一選択/複数選択の設問のみ対象、`withValidator()` で type 別に判定） | VAL-09/10 と同じ `withValidator()` クロージャ内で type 別に必須判定する |

## 7. 設計上の補足（レビュー観点）

- **`questions.*.id` を持たない全置換方式のトレードオフ**: 更新のたびに設問・選択肢を
  DELETE→INSERT し直すため、既存設問の `id` は編集のたびに変わる（同じ内容でも新しい行になる）。
  本機能のスコープでは設問 `id` を外部から参照する機能（回答・ダッシュボード）は未実装
  （`survey-answer` / `survey-dashboard` は別スペックで着手前）であり、公開後は編集不可
  （VAL-14）なので「公開後に他スペックが参照する設問 `id` が編集で変わる」事態も起こり得ない。
  将来 `survey-answer` 実装時に、この前提（下書き中の設問 `id` は不変性を保証しない）を
  引き継ぎドキュメント（`survey-answer` の参照資料）に明記する想定
- **他社アンケートの 403（VAL-15）とルートモデルバインディング**: `departments` / `users` と同様、
  ルートモデルバインディングで解決した `Survey` に対し Controller 側で `company_id` を
  手動チェックする（Policy 不使用の既存方針を継承）
- **公開後の「編集不可」の一元化**: `ensureEditable()` 1 箇所に集約することで、
  「編集」「削除」「公開（二重公開の防止）」の 3 操作すべてが同じ判定ロジックを使う。
  公開処理自体も `ensureEditable()` を通すため、公開済みアンケートへの再度の公開操作
  （URL 直接アクセス）も自動的に 403 になる
- **CSRF**: Laravel 標準（Blade `@csrf`）。既存方針を継承し、axios によるバックグラウンド送信は
  本機能でも使わない（§1 参照）
