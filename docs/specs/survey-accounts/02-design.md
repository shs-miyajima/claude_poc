# 設計 — アンケートシステム アカウント基盤（認証・企業/部署/管理者/ユーザー管理）

> status: 02-design.status を参照
> 前提: 01-requirements.status が `approved`

## 1. 設計方針

- **認証**: composer の追加パッケージなし（親 Q28）。Laravel 標準のセッション認証
  （`Auth` ファサード + `web` ガード + users プロバイダ）を自前のログイン Controller / Service で使う。
  Breeze 等のスキャフォールドは導入しない
- **レイヤ**: Controller は薄く、ビジネスロジック（企業コード採番・一括無効化・CSV 取込等）は
  Service に置く。入力検証は FormRequest（`laravel-conventions` 規約）
- **無効化（業務ステータス、論理削除ではない）**: `deactivated_at`（nullable timestamp）の
  独自カラムで表現する。Laravel 標準の `SoftDeletes` は**使わない**。理由は次の 2 点:
  (1) 本要件の「無効化」はデータを失う削除ではなく、再有効化すれば元に戻る業務ステータスの
  切替であり、`deleted_at` / `restore()` / `forceDelete()` という削除・復元の語彙を持つ
  `SoftDeletes` を転用するのは意味的に不適切（`withTrashed()` で無効化済みデータ自体は
  取得可能だが、削除機能を転用している点が問題）。
  (2) `SoftDeletes` はグローバルスコープにより**デフォルトで除外**するため、
  「一覧は原則無効化済みも含め全件表示し、ログイン等の一部処理だけ有効のみに絞る」
  という本要件 §5.1〔Q-15〕の主従関係とは逆方向になり、クエリのたびに `withTrashed()` を
  書き忘れると無効化済み行が一覧から消えるという事故につながりやすい。加えてルートモデル
  紐付けが無効化済みを既定で 404 にするため、AC-26「無効化済みの編集アクセスは 403」を
  素直に実現しにくい
- **テナント分離**: 企業コンテキスト（管理者=自社、スーパーユーザー=画面切替で選択した企業）を
  ミドルウェアで解決し、Controller / Service は常に「現在の企業」のスコープでクエリする。
  他企業のリソース ID を URL に指定された場合は 403（NFR-03・AC-07）
- **ロール制御**: `role` カラム（PHP 8.4 backed enum）+ ロール検査ミドルウェアで実現（NFR-03）
- **メール一意制約**: 「企業単位・無効化済み含む・スーパーユーザー同士も一意」（Q-07・§6）は、
  PostgreSQL 18 の `UNIQUE NULLS NOT DISTINCT`（`company_id, email` 複合）で DB レベルでも保証する
  （company_id が NULL のスーパーユーザー同士も重複不可になる）。既存の `users_email_unique`
  （システム全体一意）は本要件と矛盾するため削除する（IMPACT-01）
- **画面**: フォーム POST + サーバーサイドレンダリング（Blade）中心。①では SPA 的な非同期処理・
  新規 JS モジュールは追加しない（要件 §5.4）。削除・再有効化の確認は `confirm()` を用いた
  最小限のインライン JS とする

## 2. Laravel

### 2.1 Controller（すべて新規）

| クラス | 新規/変更 | メソッド | 概要 |
|--------|----------|----------|------|
| `App\Http\Controllers\Auth\LoginController` | 新規 | `showLoginForm()` / `login(LoginRequest)` / `logout(Request)` | ログイン画面表示・認証（成功時ロール別リダイレクト: super→企業一覧, admin→company.home, user→user.home）・ログアウト（セッション破棄） |
| `App\Http\Controllers\Super\CompanyController` | 新規 | `index()` / `create()` / `store(CompanyStoreRequest)` / `edit(Company)` / `update(CompanyUpdateRequest, Company)` / `deactivate(Company)` / `activate(Company)` | 企業一覧（20 件ページング・無効含む全件）・登録（コード採番は Service）・編集・無効化・再有効化 |
| `App\Http\Controllers\Super\CompanySwitchController` | 新規 | `enter(Company)` / `exit()` | 個別企業画面への切替（有効な企業のみ・セッションに `acting_company_id` を保存）・全体画面へ戻る（セッションキー削除） |
| `App\Http\Controllers\Company\HomeController` | 新規 | `index()` | 管理者向けトップ（個別企業画面の切替後トップ兼用） |
| `App\Http\Controllers\Company\AdminController` | 新規 | `index()` / `create()` / `store(AdminStoreRequest)` / `edit(User)` / `update(AdminUpdateRequest, User)` / `deactivate(User)` / `activate(User)` | 管理者一覧（20 件ページング・無効含む全件）・管理者管理（スーパーユーザー専用・個別企業画面内・Q-11） |
| `App\Http\Controllers\Company\UserController` | 新規 | `index()` / `create()` / `store(UserStoreRequest)` / `edit(User)` / `update(UserUpdateRequest, User)` / `deactivate(User)` / `activate(User)` | ユーザー一覧（20 件ページング・無効含む全件・AC-20）・ユーザー管理（管理者 + スーパーユーザー個別企業画面） |
| `App\Http\Controllers\Company\UserCsvController` | 新規 | `show()` / `store(UserCsvImportRequest)` | CSV アップロード画面・取込実行（結果/エラー行一覧を同画面に表示） |
| `App\Http\Controllers\Company\DepartmentController` | 新規 | `index()` / `create()` / `store(DepartmentStoreRequest)` / `edit(Department)` / `update(DepartmentUpdateRequest, Department)` / `deactivate(Department)` / `activate(Department)` | 部署一覧（20 件ページング・無効含む全件）・部署マスタ管理 |
| `App\Http\Controllers\User\HomeController` | 新規 | `index()` | ユーザー（社員）ロールのログイン後プレースホルダ画面（アンケート一覧は③で置換） |

- 編集系 Controller は対象エンティティが「現在の企業」に属さない場合・無効化済みの編集アクセス
  （AC-26）の場合に `abort(403)` する

### 2.2 Service（すべて新規・`app/Services/`）

| クラス | 新規/変更 | メソッド | 概要 |
|--------|----------|----------|------|
| `App\Services\AuthenticationService` | 新規 | `attempt(?string $companyCode, string $email, string $password): ?User` | VAL-02 の認証判定を一元化。企業コード空欄→スーパーユーザー、入力あり→有効な企業を code で解決し、企業内の有効ユーザーを email で特定、`Hash::check` 成功で `Auth::login`。失敗理由は区別せず null（NFR-05） |
| `App\Services\CompanyService` | 新規 | `create(array $data): Company` / `update(Company, array): Company` / `deactivate(Company): void` / `activate(Company): void` / `nextCode(): string` | 登録時に `nextCode()`（既存 code の最大連番 + 1 を "C%04d" で採番・トランザクション内）。`deactivate` は企業 + 配下の管理者・ユーザーを一括無効化（AC-09・トランザクション）。`activate` は企業のみ有効化（配下は戻さない・Q-15） |
| `App\Services\AdminService` | 新規 | `create(Company, array): User` / `update(User, array): User` / `deactivate(User): void` / `activate(User): void` | 管理者の作成（role=admin・パスワードハッシュ化）・更新（パスワード空欄なら変更しない・Q-08）・無効化・再有効化 |
| `App\Services\UserService` | 新規 | `create(Company, array): User` / `update(User, array): User` / `deactivate(User): void` / `activate(User): void` | ユーザー（role=user）の同上 + 属性 4 項目の保存 |
| `App\Services\DepartmentService` | 新規 | `create(Company, array): Department` / `update(Department, array): Department` / `deactivate(Department): void` / `activate(Department): void` | 部署マスタの CRUD（無効化は所属ユーザーがいても可・Q-14） |
| `App\Services\UserCsvImportService` | 新規 | `import(Company, UploadedFile $file): CsvImportResult` | UTF-8 検証（`mb_check_encoding`）・ヘッダー検証（VAL-14）・行数 1〜1,000（VAL-13）・行単位検証（VAL-15: `Validator` に UserStoreRequest と同一ルールを適用、VAL-16: ファイル内メール重複、VAL-17: 列数不一致）。1 件でもエラーがあれば登録せずエラー一覧を返す。全行正常時のみトランザクションで一括登録。CSV パースは PHP 標準 `fgetcsv`（追加パッケージなし） |
| `App\Services\CsvImportResult`（DTO） | 新規 | `succeeded(): bool` / `successCount: int` / `errors: array{line: int, message: string}[]` | CSV 取込結果（画面表示用） |

### 2.3 Model / Enum

| クラス | 新規/変更 | 概要 |
|--------|----------|------|
| `App\Models\Company` | 新規 | `name` / `code` / `deactivated_at`。`departments()` / `users()` リレーション、`scopeActive()`、`isActive(): bool` |
| `App\Models\Department` | 新規 | `company_id` / `name` / `deactivated_at`。`company()`、`scopeActive()`、`isActive()` |
| `App\Models\User` | **変更** | 既存モデルに追加: `role`（`UserRole` cast）・`company_id`・`department_id`・`birth_date`（date cast）・`hire_date`（date cast）・`gender`（`Gender` cast）・`deactivated_at`。`company()` / `department()` リレーション、`scopeActive()`、`isActive()`、`isSuperUser()` / `isAdmin()` / `isUser()`。`password` は既存の `hashed` cast を継続使用（NFR-01） |
| `App\Enums\UserRole` | 新規 | backed enum: `SuperUser = 'super_user'` / `Admin = 'admin'` / `User = 'user'` |
| `App\Enums\Gender` | 新規 | backed enum: `Male = 'male'` / `Female = 'female'` / `Other = 'other'`。`label(): string`（男性/女性/その他・画面表示と CSV 値の変換に使用） |

### 2.4 Migration（すべて新規）

| ファイル名（案） | 操作 | 概要 |
|-----------------|------|------|
| `2026_07_13_000001_create_companies_table.php` | CREATE | `id` / `name` string(100) / `code` string(5) **unique** / `deactivated_at` timestamp nullable / timestamps |
| `2026_07_13_000002_create_departments_table.php` | CREATE | `id` / `company_id` FK→companies（`restrictOnDelete`。物理削除は運用しないが保全のため）/ `name` string(100) / `deactivated_at` nullable / timestamps / **unique(`company_id`, `name`)**（VAL-11・無効化済み含む一意） |
| `2026_07_13_000003_add_account_columns_to_users_table.php` | ALTER | (1) 既存 `users_email_unique` を**削除**（親 Q7 のシステム全体一意と矛盾するため）(2) 追加: `role` string(20) / `company_id` FK nullable→companies（`restrictOnDelete`。departments と同様、物理削除は運用しないが保全のため）/ `department_id` FK nullable→departments（`restrictOnDelete`、同上）/ `birth_date` date nullable / `hire_date` date nullable / `gender` string(10) nullable / `deactivated_at` timestamp nullable (3) `DB::statement` で `CREATE UNIQUE INDEX users_company_email_unique ON users (company_id, email) NULLS NOT DISTINCT`（PostgreSQL 18。VAL-06・スーパーユーザー同士の一意も保証） |

- インデックス方針: 一意制約（companies.code / departments(company_id,name) / users(company_id,email)）と
  FK の自動インデックスのみ。`role` / `deactivated_at` の単独インデックスは、規模要件がスコープ外
  （親 Q27 dropped）のため追加しない
- 企業名・部署名（companies.name / departments.name、新設テーブル）の DB 列は string(100)
  （§5.2 の最大長と一致）。既存 `users.name`（`0001_01_01_000000_create_users_table.php` 由来）は
  今回の ALTER 対象外のため既存の列長（既定）のままとし、VAL-04 の 100 文字上限は DB 制約ではなく
  アプリ層（FormRequest / CSV Service のバリデーション）でのみ担保する。email は既存 255 のまま
- 属性列（birth_date 等）を nullable にするのは管理者・スーパーユーザーが属性を持たないため
  （Q-06）。ユーザー（role=user）の必須性は FormRequest / Service で強制する（VAL-08〜10）

### 2.5 View / Route

ルートは `routes/web.php` に追加（既存 `GET /` は変更しない）。ミドルウェア略記:
`auth`=未ログイン時ログイン画面へリダイレクト（NFR-02）、`active`=`EnsureAccountActive`、
`role:x`=`EnsureRole`、`ctx`=`SetCompanyContext`。

| View | Route（name / URL / method） | 概要 |
|------|------|------|
| `auth/login.blade.php` | `login` GET `/login`（guest）/ `login.attempt` POST `/login` / `logout` POST `/logout`（auth） | ログイン・ログアウト |
| `super/companies/index.blade.php` | `super.companies.index` GET `/super/companies`〔auth,active,role:super_user〕 | 全体（全企業）画面 = 企業一覧（状態表示・切替導線は有効企業のみ） |
| `super/companies/create.blade.php` | `super.companies.create` GET `/super/companies/create`・`super.companies.store` POST `/super/companies` | 企業登録 |
| `super/companies/edit.blade.php` | `super.companies.edit` GET `/super/companies/{company}/edit`・`super.companies.update` PUT `/super/companies/{company}` | 企業編集（無効化済みは 403・AC-26） |
| —（一覧内操作） | `super.companies.deactivate` POST `/super/companies/{company}/deactivate`・`super.companies.activate` POST `/super/companies/{company}/activate` | 無効化・再有効化 |
| —（切替） | `super.switch.enter` POST `/super/companies/{company}/switch`・`super.switch.exit` POST `/super/switch/exit` | 個別企業画面へ切替（無効化済み企業は 403・Q-15）/ 全体画面へ戻る |
| `company/home.blade.php` | `company.home` GET `/company/home`〔auth,active,role:super_user,admin,ctx〕 | 管理者向けトップ（切替後トップ兼用） |
| `company/admins/index.blade.php` ほか create / edit | `company.admins.*` GET `/company/admins` ほか REST 同型 + `deactivate` / `activate`〔上記 + role:super_user〕 | 管理者管理（Q-11・スーパーユーザーのみ） |
| `company/users/index.blade.php` ほか create / edit | `company.users.*` GET `/company/users` ほか REST 同型 + `deactivate` / `activate` | ユーザー管理 |
| `company/users/csv.blade.php` | `company.users.csv` GET `/company/users/csv`・`company.users.csv.store` POST `/company/users/csv` | CSV 一括登録（結果・エラー行一覧を同画面表示） |
| `company/departments/index.blade.php` ほか create / edit | `company.departments.*` GET `/company/departments` ほか REST 同型 + `deactivate` / `activate` | 部署マスタ管理 |
| `user/home.blade.php` | `user.home` GET `/home`〔auth,active,role:user〕 | ユーザートップ（プレースホルダ。③で置換） |
| `layouts/app.blade.php` | —（共通レイアウト） | ロール別ナビ・フラッシュメッセージ・`@vite` 読み込み |

- FormRequest（新規・`app/Http/Requests/`）: `LoginRequest`（VAL-01）/ `CompanyStoreRequest`・
  `CompanyUpdateRequest`（VAL-03）/ `AdminStoreRequest`・`AdminUpdateRequest`（VAL-04〜07）/
  `UserStoreRequest`・`UserUpdateRequest`（VAL-04〜10。Update は password nullable〔Q-08〕・
  department は「有効 or 変更前の現所属」〔Q-16〕）/ `DepartmentStoreRequest`・
  `DepartmentUpdateRequest`（VAL-11）/ `UserCsvImportRequest`（VAL-12 のファイルレベル検証）
- ミドルウェア（新規・`app/Http/Middleware/`）:
  - `EnsureRole`: パラメータのロール以外は `abort(403)`（NFR-03・AC-06）
  - `EnsureAccountActive`: ログイン中ユーザーまたは所属企業が無効化済みならログアウトさせ
    ログイン画面へリダイレクト（AC-09 のセッション残存対策）
  - `SetCompanyContext`: `/company/*` の企業コンテキストを解決（admin=自社、super_user=
    セッション `acting_company_id` の有効企業）。解決不能・無効企業は `abort(403)`。解決した
    Company をコンテナ（`app()->instance('currentCompany', $company)`）で共有
- バリデーションメッセージは `lang/ja/validation.php` + 各 FormRequest の `messages()` で
  §7 の確定文言に合わせる（`lang/ja/` は新規作成）

### 2.6 Job（該当時）

なし（CSV は 1,000 件上限・同期処理で完結するため非同期 Job は使わない）。

### 2.7 Seeder / Factory

| クラス | 新規/変更 | 概要 |
|--------|----------|------|
| `Database\Seeders\SuperUserSeeder` | 新規 | 初期スーパーユーザー 1 名を投入(親 Q4）。認証情報は `.env`（`SUPER_USER_EMAIL` / `SUPER_USER_PASSWORD`、既定値 `super@example.com` / `super1234`）から取得し `firstOrCreate` |
| `Database\Seeders\DatabaseSeeder` | **変更** | `SuperUserSeeder` の呼び出しを追加（既存のサンプルユーザー生成は削除しない範囲で維持判断→§5 IMPACT-04） |
| `Database\Factories\UserFactory` | **変更** | 追加列のデフォルト（`role`=user 等）と `superUser()` / `admin()` / `deactivated()` / `forCompany(Company)` の state を追加（PHPUnit 用） |
| `Database\Factories\CompanyFactory` / `DepartmentFactory` | 新規 | PHPUnit 用 |

## 3. フロント（Blade / Vite / JavaScript）

### 3.1 Blade

§2.5 のとおり新規 16 ファイル（layouts/app + auth/login + super 3 + company/home +
admins 3 + users 3 + users/csv + departments 3 + user/home）。すべて Tailwind ユーティリティ
クラスでスタイリングし、`layouts/app.blade.php` から `@vite(['resources/css/app.css', 'resources/js/app.js'])`
を読み込む。`welcome.blade.php` は変更しない。

### 3.2 JavaScript（Vite エントリ）

| ファイル | 新規/変更 | 概要 |
|---------|----------|------|
| `resources/js/app.js` / `bootstrap.js` | 変更なし | 既存のまま読み込むのみ |
| （新規 JS モジュール） | なし | ①はフォーム POST 中心のため追加しない。無効化・再有効化の確認は Blade 内の `onsubmit="return confirm('...')"` で行う（Vitest 対象の純関数なし） |

## 4. シーケンス（主要フロー）

### ログイン（UC-01/02・VAL-02）

```
ブラウザ → POST /login（企業コード・メール・パスワード）
  → LoginRequest（VAL-01: 必須）
  → LoginController@login → AuthenticationService::attempt()
      企業コードあり: Company::active()->where(code) 解決（失敗→null）
      企業コード空欄: role=super_user として扱う
      → User::active()->where(email)->where(company_id) 特定 → Hash::check
      → 成功: Auth::login + セッション再生成 → ロール別リダイレクト
      → 失敗: null → VAL-02 統一メッセージでログイン画面へ戻す
```

### ユーザー CSV 取込（UC-15/15a・VAL-12〜17）

```
ブラウザ → POST /company/users/csv（multipart）
  → UserCsvImportRequest（VAL-12: ファイル必須・形式）
  → UserCsvController@store → UserCsvImportService::import(currentCompany, file)
      UTF-8 検証（VAL-12）→ ヘッダー検証（VAL-14）→ 行数 1〜1,000（VAL-13）
      → 各行: 列数（VAL-17）→ Validator（VAL-15: 個別登録と同一ルール）→ ファイル内重複（VAL-16）
      → エラー 0 件: DB::transaction で全件 INSERT → 成功件数を表示
      → エラーあり: 登録せず {行番号, 理由} 一覧を表示（全件ロールバック相当）
```

## 5. 影響範囲

| ID | 対象 | 影響あり/なし | 内容・理由 |
|----|------|--------------|-----------|
| IMPACT-01 | 既存 `users` テーブル（`0001_01_01_000000_create_users_table.php` 由来） | **あり** | 新規マイグレーションで列追加 + `users_email_unique`（システム全体一意）を削除し `(company_id, email) NULLS NOT DISTINCT` 複合一意に置き換える（親 Q7）。既存データは未投入のため移行処理は不要。同マイグレーション内の `password_reset_tokens` / `sessions` テーブルは**残置・変更しない**（パスワードリセットはスコープ外、sessions は標準セッションで使用） |
| IMPACT-02 | `routes/web.php` | **あり** | ルート追加。既存 `GET /`（welcome）は変更しない |
| IMPACT-03 | `resources/views/welcome.blade.php` | なし | 要件 §3 で変更禁止。ログイン画面への導線は追加せず `/login` への直接アクセスとする（導線の追加はスコープ外と判断） |
| IMPACT-04 | `database/seeders/DatabaseSeeder.php` | **あり** | `SuperUserSeeder` 呼び出しを追加。既存のサンプルユーザー生成（`User::factory()->create(...)`）は role 必須化により**そのままでは動かなくなるため削除**する（本番相当データとして不要） |
| IMPACT-05 | `database/factories/UserFactory.php` | **あり** | 追加列のデフォルト定義と role 別 state を追加（これを怠ると factory 利用テストが全滅するため） |
| IMPACT-06 | 既存 PHPUnit（`tests/Feature/ExampleTest.php`・`tests/Unit/ExampleTest.php`） | なし | `GET /` の 200 確認と純粋アサーションのみで、users テーブル・ルート変更の影響を受けない（`/` は変更しないため） |
| IMPACT-07 | 既存 E2E（`tests/e2e_tests/tests/test_example.spec.ts`） | なし | 対象は welcome 画面のみで変更しない。ログイン必須化もしない |
| IMPACT-08 | `resources/js/`（`app.js` / `bootstrap.js` / `sampleHelper.js`）・Vitest 既存テスト | なし | JS は変更しない（§3.2）。sampleHelper のテストも対象外 |
| IMPACT-09 | `config/auth.php` ほか認証設定 | なし | 標準の `web` ガード + users プロバイダをそのまま使用（User モデル拡張のみで成立） |
| IMPACT-10 | `resources/css/app.css`・`vite.config.js`・`package.json` | なし | Tailwind v4 + laravel-vite-plugin は導入済み。新規 Blade は既存エントリを読むだけで、npm パッケージ追加もなし（Chart.js は④で追加） |
| IMPACT-11 | `lang/` ディレクトリ | **あり（新規）** | `lang/ja/validation.php` を新規作成し §7 の確定文言を定義（既存ファイルなし）。`config/app.php` の locale は `env('APP_LOCALE', 'en')` で、`.env` に `APP_LOCALE=en` が明示設定されているため、**`config/app.php` のデフォルト値変更だけでは実行時ロケールは変わらない**。`.env` と `.env.example` の `APP_LOCALE` を `ja` に変更する（IMPACT-13 参照） |
| IMPACT-12 | `bootstrap/app.php` | **あり** | ミドルウェアエイリアス（`role` / `active` / `ctx`）の登録を追加（Laravel 12 の登録箇所） |
| IMPACT-13 | `.env` / `.env.example` | **あり（新規）** | 新規キー追加: `SUPER_USER_EMAIL` / `SUPER_USER_PASSWORD`（`SuperUserSeeder` が参照・§2.7）。既存キー変更: `APP_LOCALE` を `ja` に変更（IMPACT-11）。`.env.example` に追記し、ローカル docker 環境の `.env` にも実値を設定する |

## 6. 検証ルール対応（VAL × 実装）

| VAL ID | サブルール・観点 | 検証箇所（FormRequest / Service 等のクラス・メソッド） | 備考 |
|--------|------------------|--------------------------------------------------------|------|
| VAL-01 | ログイン必須（メール・パスワード） | `LoginRequest::rules()` | 企業コードは任意のため required にしない |
| VAL-02 | 認証失敗の統一判定（存在しない/形式不正コード/不一致/無効化済み/無効企業） | `AuthenticationService::attempt()` | 失敗理由を区別せず null → Controller で統一メッセージ（NFR-05）。企業コードの形式・長さも個別エラーにしない（§5.2） |
| VAL-03 | 企業名 必須/最大 100 | `CompanyStoreRequest` / `CompanyUpdateRequest` | |
| VAL-04 | 氏名 必須/最大 100 | `AdminStoreRequest` / `AdminUpdateRequest` / `UserStoreRequest` / `UserUpdateRequest`、CSV は `UserCsvImportService::import`（Validator 適用） | 画面 POST と CSV の 2 経路 |
| VAL-05 | メール 必須/形式/最大 255 | 同上（Admin/User 各 Request + CSV Service） | |
| VAL-06 | メール企業内一意（無効化済み含む） | 同上: `Rule::unique('users','email')->where('company_id', ...)`（deactivated を除外**しない**）+ DB 複合一意インデックス | 編集時は自身を except。DB 制約が最終防衛線 |
| VAL-07 | パスワード 登録時必須/8 文字以上/255 以内（編集時は空欄可） | `AdminStoreRequest`・`UserStoreRequest`（required）/ `AdminUpdateRequest`・`UserUpdateRequest`（nullable）/ CSV Service | Q-08 |
| VAL-08 | 生年月日・入社年月日 必須/Y-m-d 日付 | `UserStoreRequest` / `UserUpdateRequest` / CSV Service（`date_format:Y-m-d`） | 管理者には項目なし（Q-06） |
| VAL-09 | 性別 必須/enum 値 | 同上（`Rule::enum(Gender)`。CSV は日本語表記→enum 変換後に検証） | |
| VAL-10 | 部署 必須/現在企業の有効部署（編集時は変更前の現所属も許容） | `UserStoreRequest`（active 部署のみ）/ `UserUpdateRequest`（active or 変更前 department_id）/ CSV Service（部署名→有効部署の解決・VAL-15 経路） | Q-14・Q-16 |
| VAL-11 | 部署名 必須/最大 100/企業内一意（無効化済み含む） | `DepartmentStoreRequest` / `DepartmentUpdateRequest` + DB 複合一意 | 編集時は自身を except |
| VAL-12 | CSV ファイル必須/形式/UTF-8 | ファイル必須・拡張子: `UserCsvImportRequest`。UTF-8・パース可否: `UserCsvImportService::import` | 2 段階検証 |
| VAL-13 | CSV データ行 1〜1,000 件 | `UserCsvImportService::import` | |
| VAL-14 | CSV ヘッダー 7 列一致 | `UserCsvImportService::import` | |
| VAL-15 | CSV 行単位検証（VAL-04〜10 と同一基準・全件ロールバック） | `UserCsvImportService::import`（行ごとに `Validator::make` + 部署解決） | エラーは {行番号, 理由} で収集 |
| VAL-16 | CSV ファイル内メール重複 | `UserCsvImportService::import` | 大文字小文字は同一視しない（メール検証は形式のみのため） |
| VAL-17 | CSV データ行の列数不一致 | `UserCsvImportService::import` | 行単位エラーとして VAL-15 と同じ一覧に表示 |

## 7. 設計上の補足（レビュー観点）

- **無効化済み編集の 403（AC-26）**: 各 Controller の `edit` / `update` 冒頭で対象の
  `isActive()` を確認し `abort(403)`。一覧側は編集導線自体を出さない（§5.1）
- **他企業リソースの 403（AC-07・NFR-03）**: `/company/users/{user}` 等のルートモデル解決後、
  対象の `company_id` が現在の企業コンテキストと一致しない場合 `abort(403)`（Controller 共通処理。
  ルートスコープには頼らない）
- **企業無効化の一括処理（UC-07・AC-09）**: `CompanyService::deactivate` はトランザクションで
  企業 + 配下 users（管理者・ユーザー）の `deactivated_at` を一括更新。部署は無効化**しない**
  （親 Q32 の対象は管理者・ユーザー。企業自体が無効なら部署は到達不能のため。再有効化時に
  部署状態が維持される利点もある）
- **ログイン済みセッションの失効（AC-09 派生）**: 無効化はセッション破棄を伴わないため、
  `EnsureAccountActive` ミドルウェアが毎リクエストで有効性を確認しログアウトさせる
- **CSRF（NFR-04）**: Laravel 標準（Blade `@csrf`）。axios は①では未使用
