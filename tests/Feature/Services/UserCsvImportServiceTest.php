<?php

namespace Tests\Feature\Services;

use App\Models\Company;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserCsvImportServiceTest extends TestCase
{
    use RefreshDatabase;

    private const HEADER = '氏名,メールアドレス,初期パスワード,生年月日,入社年月日,性別,部署名';

    private function actingAdminInCompany(Company $company): User
    {
        $admin = User::factory()->admin()->forCompany($company)->create();

        $this->actingAs($admin);

        return $admin;
    }

    private function csvFile(string $content, string $filename = 'users.csv'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($filename, $content);
    }

    /**
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    private function validRow(array $overrides = []): array
    {
        return array_merge([
            'name' => '山田太郎',
            'email' => 'csvuser1@a.example.com',
            'password' => 'password1',
            'birth_date' => '1990-04-01',
            'hire_date' => '2015-04-01',
            'gender' => '男性',
            'department' => '人事部',
        ], $overrides);
    }

    /**
     * @param  array<string, string>  $row
     */
    private function rowLine(array $row): string
    {
        return implode(',', [
            $row['name'], $row['email'], $row['password'], $row['birth_date'], $row['hire_date'], $row['gender'], $row['department'],
        ]);
    }

    /**
     * PU-094-inp: ファイルを選択せず送信すると422で必須メッセージが返る
     */
    public function test_csv_store_requires_file(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);

        $response = $this->postJson('/company/users/csv', []);

        $response->assertStatus(422);
        $response->assertJsonFragment(['csv_file' => ['CSVファイルを選択してください。']]);
    }

    /**
     * PU-095-inp: 拡張子.txtのファイルを選択して送信すると422で形式エラーが返る
     */
    public function test_csv_store_rejects_non_csv_extension(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);

        $response = $this->postJson('/company/users/csv', [
            'csv_file' => $this->csvFile(self::HEADER."\n".$this->rowLine($this->validRow()), 'users.txt'),
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['csv_file' => ['CSVファイル（UTF-8）の形式が正しくありません。']]);
    }

    /**
     * PU-096-inp: Shift_JISでエンコードされたCSVファイルを選択すると422で形式エラーが返る
     */
    public function test_csv_store_rejects_non_utf8_encoding(): void
    {
        $company = Company::factory()->create();
        Department::factory()->create(['company_id' => $company->id, 'name' => '人事部']);
        $this->actingAdminInCompany($company);

        $utf8Content = self::HEADER."\n".$this->rowLine($this->validRow());
        $sjisContent = mb_convert_encoding($utf8Content, 'SJIS', 'UTF-8');

        $response = $this->post('/company/users/csv', ['csv_file' => $this->csvFile($sjisContent)]);

        $response->assertStatus(422);
        $this->assertStringContainsString('CSVファイル（UTF-8）の形式が正しくありません。', $response->getContent());
    }

    /**
     * PU-097-inp: データ行0件(ヘッダーのみ)のCSVを選択すると422で件数エラーが返る
     */
    public function test_csv_store_rejects_zero_data_rows(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);

        $response = $this->post('/company/users/csv', ['csv_file' => $this->csvFile(self::HEADER."\n")]);

        $response->assertStatus(422);
        $this->assertStringContainsString('CSVのデータ行は1件以上1,000件以下にしてください。', $response->getContent());
    }

    /**
     * PU-098-evt: データ行1000件のCSVを選択すると1000件全員が登録される(境界OK)
     */
    public function test_csv_store_accepts_exactly_1000_rows(): void
    {
        $company = Company::factory()->create();
        Department::factory()->create(['company_id' => $company->id, 'name' => '人事部']);
        $this->actingAdminInCompany($company);

        $lines = [self::HEADER];
        for ($i = 1; $i <= 1000; $i++) {
            $lines[] = $this->rowLine($this->validRow(['email' => "csvuser{$i}@a.example.com"]));
        }

        $response = $this->post('/company/users/csv', ['csv_file' => $this->csvFile(implode("\n", $lines))]);

        $response->assertRedirect(route('company.users.index'));
        $this->assertSame(1000, User::query()->where('company_id', $company->id)->where('role', 'user')->count());
    }

    /**
     * PU-099-inp: データ行1001件のCSVを選択すると422で件数エラーが返り1件も登録されない(境界NG)
     */
    public function test_csv_store_rejects_1001_rows(): void
    {
        $company = Company::factory()->create();
        Department::factory()->create(['company_id' => $company->id, 'name' => '人事部']);
        $this->actingAdminInCompany($company);

        $lines = [self::HEADER];
        for ($i = 1; $i <= 1001; $i++) {
            $lines[] = $this->rowLine($this->validRow(['email' => "csvuser{$i}@a.example.com"]));
        }

        $response = $this->post('/company/users/csv', ['csv_file' => $this->csvFile(implode("\n", $lines))]);

        $response->assertStatus(422);
        $this->assertStringContainsString('CSVのデータ行は1件以上1,000件以下にしてください。', $response->getContent());
        $this->assertSame(0, User::query()->where('company_id', $company->id)->where('role', 'user')->count());
    }

    /**
     * PU-100-inp: ヘッダー行の列名が定義と異なるCSVを選択すると422でヘッダーエラーが返る
     */
    public function test_csv_store_rejects_mismatched_header(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);

        $wrongHeader = '名前,メール,パスワード,生年月日,入社年月日,性別,部署名';
        $response = $this->post('/company/users/csv', [
            'csv_file' => $this->csvFile($wrongHeader."\n".$this->rowLine($this->validRow())),
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('CSVのヘッダー行が正しくありません。', $response->getContent());
    }

    /**
     * PU-101-inp: 2行目の列数が6列のCSVを選択するとエラー一覧に列数エラーが表示され1件も登録されない
     */
    public function test_csv_store_reports_column_count_mismatch(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);

        $shortRow = '山田太郎,csvuser1@a.example.com,password1,1990-04-01,2015-04-01,男性';

        $response = $this->post('/company/users/csv', [
            'csv_file' => $this->csvFile(self::HEADER."\n".$shortRow),
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('2行目: 列数が正しくありません。', $response->getContent());
        $this->assertSame(0, User::query()->where('company_id', $company->id)->where('role', 'user')->count());
    }

    /**
     * PU-102-inp: 2行目の氏名が空のCSV(他行は正常)を選択するとエラー一覧に必須エラーが表示され1件も登録されない
     */
    public function test_csv_store_reports_missing_name(): void
    {
        $company = Company::factory()->create();
        Department::factory()->create(['company_id' => $company->id, 'name' => '人事部']);
        $this->actingAdminInCompany($company);

        $response = $this->post('/company/users/csv', [
            'csv_file' => $this->csvFile(self::HEADER."\n".$this->rowLine($this->validRow(['name' => '']))),
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('2行目: 氏名は必須です。', $response->getContent());
    }

    /**
     * PU-103-inp: 2行目のメールが形式不正なCSV(他行は正常)を選択するとエラー一覧に形式エラーが表示され1件も登録されない
     */
    public function test_csv_store_reports_malformed_email(): void
    {
        $company = Company::factory()->create();
        Department::factory()->create(['company_id' => $company->id, 'name' => '人事部']);
        $this->actingAdminInCompany($company);

        $response = $this->post('/company/users/csv', [
            'csv_file' => $this->csvFile(self::HEADER."\n".$this->rowLine($this->validRow(['email' => 'abc']))),
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('2行目: メールアドレスの形式が正しくありません。', $response->getContent());
    }

    /**
     * PU-104-inp: 2行目のメールが既存ユーザーと重複するCSV(他行は正常)を選択するとエラー一覧に重複エラーが表示され1件も登録されない
     */
    public function test_csv_store_reports_duplicate_email_with_existing_user(): void
    {
        $company = Company::factory()->create();
        Department::factory()->create(['company_id' => $company->id, 'name' => '人事部']);
        User::factory()->forCompany($company)->create(['email' => 'dup4@a.example.com']);
        $this->actingAdminInCompany($company);

        $response = $this->post('/company/users/csv', [
            'csv_file' => $this->csvFile(self::HEADER."\n".$this->rowLine($this->validRow(['email' => 'dup4@a.example.com']))),
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('2行目: このメールアドレスは既に登録されています。', $response->getContent());
    }

    /**
     * PU-105-inp: 2行目の初期パスワードが7文字のCSV(他行は正常)を選択するとエラー一覧に境界NGエラーが表示され1件も登録されない
     */
    public function test_csv_store_reports_password_under_8_chars(): void
    {
        $company = Company::factory()->create();
        Department::factory()->create(['company_id' => $company->id, 'name' => '人事部']);
        $this->actingAdminInCompany($company);

        $response = $this->post('/company/users/csv', [
            'csv_file' => $this->csvFile(self::HEADER."\n".$this->rowLine($this->validRow(['password' => 'pass123']))),
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('2行目: パスワードは8文字以上で入力してください。', $response->getContent());
    }

    /**
     * PU-106-inp: 2行目の生年月日が「2020/13/40」のCSV(他行は正常)を選択するとエラー一覧に日付形式エラーが表示され1件も登録されない
     */
    public function test_csv_store_reports_malformed_birth_date(): void
    {
        $company = Company::factory()->create();
        Department::factory()->create(['company_id' => $company->id, 'name' => '人事部']);
        $this->actingAdminInCompany($company);

        $response = $this->post('/company/users/csv', [
            'csv_file' => $this->csvFile(self::HEADER."\n".$this->rowLine($this->validRow(['birth_date' => '2020/13/40']))),
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('2行目: 生年月日はYYYY-MM-DD形式の正しい日付で入力してください。', $response->getContent());
    }

    /**
     * PU-107-inp: 2行目の性別が「unknown」のCSV(他行は正常)を選択するとエラー一覧に不正値エラーが表示され1件も登録されない
     */
    public function test_csv_store_reports_invalid_gender(): void
    {
        $company = Company::factory()->create();
        Department::factory()->create(['company_id' => $company->id, 'name' => '人事部']);
        $this->actingAdminInCompany($company);

        $response = $this->post('/company/users/csv', [
            'csv_file' => $this->csvFile(self::HEADER."\n".$this->rowLine($this->validRow(['gender' => 'unknown']))),
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('2行目: 性別が正しくありません。', $response->getContent());
    }

    /**
     * PU-108-inp: 2行目の部署名が部署マスタに存在しないCSV(他行は正常)を選択するとエラー一覧に不正値エラーが表示され1件も登録されない
     */
    public function test_csv_store_reports_unknown_department(): void
    {
        $company = Company::factory()->create();
        Department::factory()->create(['company_id' => $company->id, 'name' => '人事部']);
        $this->actingAdminInCompany($company);

        $response = $this->post('/company/users/csv', [
            'csv_file' => $this->csvFile(self::HEADER."\n".$this->rowLine($this->validRow(['department' => '存在しない部']))),
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('2行目: 部署が正しくありません。', $response->getContent());
    }

    /**
     * PU-109-inp: 2行目と4行目のメールアドレスが同一のCSVを選択するとエラー一覧にファイル内重複エラーが表示され1件も登録されない
     */
    public function test_csv_store_reports_duplicate_email_within_file(): void
    {
        $company = Company::factory()->create();
        Department::factory()->create(['company_id' => $company->id, 'name' => '人事部']);
        $this->actingAdminInCompany($company);

        $lines = [
            self::HEADER,
            $this->rowLine($this->validRow(['email' => 'samefile@a.example.com'])),
            $this->rowLine($this->validRow(['email' => 'other@a.example.com'])),
            $this->rowLine($this->validRow(['email' => 'samefile@a.example.com'])),
        ];

        $response = $this->post('/company/users/csv', ['csv_file' => $this->csvFile(implode("\n", $lines))]);

        $response->assertStatus(422);
        $this->assertStringContainsString('4行目: メールアドレスがファイル内で重複しています。', $response->getContent());
        $this->assertSame(0, User::query()->where('company_id', $company->id)->where('role', 'user')->count());
    }

    /**
     * PU-110-evt: 3件とも正常なCSVを選択すると3件全員がユーザー一覧に表示される
     */
    public function test_csv_store_registers_all_valid_rows(): void
    {
        $company = Company::factory()->create();
        Department::factory()->create(['company_id' => $company->id, 'name' => '人事部']);
        $this->actingAdminInCompany($company);

        $lines = [
            self::HEADER,
            $this->rowLine($this->validRow(['email' => 'ok1@a.example.com', 'name' => '登録一郎'])),
            $this->rowLine($this->validRow(['email' => 'ok2@a.example.com', 'name' => '登録二郎'])),
            $this->rowLine($this->validRow(['email' => 'ok3@a.example.com', 'name' => '登録三郎'])),
        ];

        $response = $this->post('/company/users/csv', ['csv_file' => $this->csvFile(implode("\n", $lines))]);

        $response->assertRedirect(route('company.users.index'));

        $indexResponse = $this->get('/company/users');
        $indexResponse->assertSee('登録一郎');
        $indexResponse->assertSee('登録二郎');
        $indexResponse->assertSee('登録三郎');
    }

    /**
     * PU-111-evt: 5行中4行目のみメールが重複するCSVを選択すると1件も登録されずエラー一覧が表示される
     */
    public function test_csv_store_rolls_back_all_rows_when_one_row_has_error(): void
    {
        $company = Company::factory()->create();
        Department::factory()->create(['company_id' => $company->id, 'name' => '人事部']);
        User::factory()->forCompany($company)->create(['email' => 'existing@a.example.com']);
        $this->actingAdminInCompany($company);

        $lines = [self::HEADER];
        for ($i = 1; $i <= 5; $i++) {
            // i=3 は CSV上の4行目(header=1行目, データ1行目=2行目)に対応する
            $email = $i === 3 ? 'existing@a.example.com' : "rowuser{$i}@a.example.com";
            $lines[] = $this->rowLine($this->validRow(['email' => $email]));
        }

        $response = $this->post('/company/users/csv', ['csv_file' => $this->csvFile(implode("\n", $lines))]);

        $response->assertStatus(422);
        $this->assertStringContainsString('4行目: このメールアドレスは既に登録されています。', $response->getContent());
        $this->assertSame(1, User::query()->where('company_id', $company->id)->where('role', 'user')->count());
    }

    /**
     * PU-120-other: CSVで登録したユーザーのpasswordが平文と異なりHash::checkで照合できる
     */
    public function test_csv_registered_user_password_is_hashed(): void
    {
        $company = Company::factory()->create();
        Department::factory()->create(['company_id' => $company->id, 'name' => '人事部']);
        $this->actingAdminInCompany($company);

        $this->post('/company/users/csv', [
            'csv_file' => $this->csvFile(self::HEADER."\n".$this->rowLine($this->validRow(['password' => 'pass1234']))),
        ]);

        $user = User::query()->where('email', 'csvuser1@a.example.com')->first();
        $this->assertNotNull($user);
        $this->assertNotSame('pass1234', $user->password);
        $this->assertTrue(Hash::check('pass1234', $user->password));
    }

    /**
     * PU-136-inp: 2行目の入社年月日が「2020/13/40」のCSV(他行は正常)を選択するとエラー一覧に日付形式エラーが表示され1件も登録されない
     */
    public function test_csv_store_reports_malformed_hire_date(): void
    {
        $company = Company::factory()->create();
        Department::factory()->create(['company_id' => $company->id, 'name' => '人事部']);
        $this->actingAdminInCompany($company);

        $response = $this->post('/company/users/csv', [
            'csv_file' => $this->csvFile(self::HEADER."\n".$this->rowLine($this->validRow(['hire_date' => '2020/13/40']))),
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('2行目: 入社年月日はYYYY-MM-DD形式の正しい日付で入力してください。', $response->getContent());
    }
}
