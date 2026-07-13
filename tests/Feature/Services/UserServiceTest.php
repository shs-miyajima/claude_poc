<?php

namespace Tests\Feature\Services;

use App\Models\Company;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    private function actingAdminInCompany(Company $company): User
    {
        $admin = User::factory()->admin()->forCompany($company)->create();

        $this->actingAs($admin);

        return $admin;
    }

    /**
     * @return array<string, mixed>
     */
    private function validUserPayload(Department $department, array $overrides = []): array
    {
        return array_merge([
            'name' => '山田太郎',
            'email' => 'yamada@a.example.com',
            'password' => 'password1',
            'birth_date' => '1990-04-01',
            'hire_date' => '2015-04-01',
            'gender' => 'male',
            'department_id' => $department->id,
        ], $overrides);
    }

    /**
     * PU-058-evt: 全項目を入力し登録すると企業Aのユーザーとして作成されユーザー一覧に表示される
     */
    public function test_admin_can_create_user(): void
    {
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $this->actingAdminInCompany($company);

        $response = $this->post('/company/users', $this->validUserPayload($department));

        $response->assertRedirect(route('company.users.index'));

        $indexResponse = $this->get('/company/users');
        $indexResponse->assertSee('山田太郎');

        $user = User::query()->where('email', 'yamada@a.example.com')->first();
        $this->assertSame($company->id, $user->company_id);
        $this->assertSame('user', $user->role->value);
    }

    /**
     * PU-059-inp: 氏名を空にして送信すると422で必須メッセージが返る
     */
    public function test_user_store_requires_name(): void
    {
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $this->actingAdminInCompany($company);

        $response = $this->postJson('/company/users', $this->validUserPayload($department, ['name' => '']));

        $response->assertStatus(422);
        $response->assertJsonFragment(['name' => ['氏名は必須です。']]);
    }

    /**
     * PU-060-inp: 氏名に101文字を入力すると422で最大長エラーが返る
     */
    public function test_user_store_rejects_name_over_100_chars(): void
    {
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $this->actingAdminInCompany($company);

        $response = $this->postJson('/company/users', $this->validUserPayload($department, ['name' => str_repeat('あ', 101)]));

        $response->assertStatus(422);
        $response->assertJsonFragment(['name' => ['氏名は100文字以内で入力してください。']]);
    }

    /**
     * PU-061-inp: 氏名に100文字を入力すると登録が成功する(境界OK)
     */
    public function test_user_store_accepts_name_exactly_100_chars(): void
    {
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $this->actingAdminInCompany($company);

        $response = $this->post('/company/users', $this->validUserPayload($department, ['name' => str_repeat('あ', 100)]));

        $response->assertRedirect(route('company.users.index'));
    }

    /**
     * PU-062-inp: メールを空にして送信すると422で必須メッセージが返る
     */
    public function test_user_store_requires_email(): void
    {
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $this->actingAdminInCompany($company);

        $response = $this->postJson('/company/users', $this->validUserPayload($department, ['email' => '']));

        $response->assertStatus(422);
        $response->assertJsonFragment(['email' => ['メールアドレスは必須です。']]);
    }

    /**
     * PU-063-inp: メールに形式不正な値を入力すると422で形式エラーが返る
     */
    public function test_user_store_rejects_malformed_email(): void
    {
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $this->actingAdminInCompany($company);

        $response = $this->postJson('/company/users', $this->validUserPayload($department, ['email' => 'abc']));

        $response->assertStatus(422);
        $response->assertJsonFragment(['email' => ['メールアドレスの形式が正しくありません。']]);
    }

    /**
     * PU-064-inp: メールに256文字の値を入力すると422で最大長エラーが返る
     */
    public function test_user_store_rejects_email_over_255_chars(): void
    {
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $this->actingAdminInCompany($company);

        $longEmail = str_repeat('a', 250).'@ex.com';
        $this->assertGreaterThan(255, strlen($longEmail));

        $response = $this->postJson('/company/users', $this->validUserPayload($department, ['email' => $longEmail]));

        $response->assertStatus(422);
        $response->assertJsonFragment(['email' => ['メールアドレスは255文字以内で入力してください。']]);
    }

    /**
     * PU-065-inp: 無効化済みユーザーと同一メールを入力すると422で重複エラーが返る
     */
    public function test_user_store_rejects_duplicate_email_including_deactivated(): void
    {
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        User::factory()->forCompany($company)->deactivated()->create(['email' => 'dup2@a.example.com']);
        $this->actingAdminInCompany($company);

        $response = $this->postJson('/company/users', $this->validUserPayload($department, ['email' => 'dup2@a.example.com']));

        $response->assertStatus(422);
        $response->assertJsonFragment(['email' => ['このメールアドレスは既に登録されています。']]);
    }

    /**
     * PU-066-inp: 企業Bに同一メールが存在しても企業単位一意のため登録が成功する
     */
    public function test_user_store_allows_same_email_in_different_company(): void
    {
        $companyB = Company::factory()->create();
        User::factory()->forCompany($companyB)->create(['email' => 'dupB@example.com']);

        $companyA = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $companyA->id]);
        $this->actingAdminInCompany($companyA);

        $response = $this->post('/company/users', $this->validUserPayload($department, ['email' => 'dupB@example.com']));

        $response->assertRedirect(route('company.users.index'));
        $this->assertDatabaseHas('users', ['email' => 'dupB@example.com', 'company_id' => $companyA->id]);
    }

    /**
     * PU-067-inp: 初期パスワードを空にして送信すると422で必須メッセージが返る
     */
    public function test_user_store_requires_password(): void
    {
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $this->actingAdminInCompany($company);

        $response = $this->postJson('/company/users', $this->validUserPayload($department, ['password' => '']));

        $response->assertStatus(422);
        $response->assertJsonFragment(['password' => ['初期パスワードは必須です。']]);
    }

    /**
     * PU-068-inp: 初期パスワードに7文字を入力すると422で境界NGエラーが返る
     */
    public function test_user_store_rejects_password_under_8_chars(): void
    {
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $this->actingAdminInCompany($company);

        $response = $this->postJson('/company/users', $this->validUserPayload($department, ['password' => 'pass123']));

        $response->assertStatus(422);
        $response->assertJsonFragment(['password' => ['パスワードは8文字以上で入力してください。']]);
    }

    /**
     * PU-069-inp: 初期パスワードに8文字を入力すると登録が成功する(境界OK)
     */
    public function test_user_store_accepts_password_exactly_8_chars(): void
    {
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $this->actingAdminInCompany($company);

        $response = $this->post('/company/users', $this->validUserPayload($department, ['password' => 'pass1234']));

        $response->assertRedirect(route('company.users.index'));
    }

    /**
     * PU-070-inp: 初期パスワードに256文字を入力すると422で最大長エラーが返る
     */
    public function test_user_store_rejects_password_over_255_chars(): void
    {
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $this->actingAdminInCompany($company);

        $response = $this->postJson('/company/users', $this->validUserPayload($department, ['password' => str_repeat('a', 256)]));

        $response->assertStatus(422);
        $response->assertJsonFragment(['password' => ['パスワードは255文字以内で入力してください。']]);
    }

    /**
     * PU-071-inp: 生年月日を空にして送信すると422で必須メッセージが返る
     */
    public function test_user_store_requires_birth_date(): void
    {
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $this->actingAdminInCompany($company);

        $response = $this->postJson('/company/users', $this->validUserPayload($department, ['birth_date' => '']));

        $response->assertStatus(422);
        $response->assertJsonFragment(['birth_date' => ['生年月日は必須です。']]);
    }

    /**
     * PU-072-inp: 生年月日に「2020/13/40」を入力すると422で日付形式エラーが返る
     */
    public function test_user_store_rejects_malformed_birth_date(): void
    {
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $this->actingAdminInCompany($company);

        $response = $this->postJson('/company/users', $this->validUserPayload($department, ['birth_date' => '2020/13/40']));

        $response->assertStatus(422);
        $response->assertJsonFragment(['birth_date' => ['生年月日はYYYY-MM-DD形式の正しい日付で入力してください。']]);
    }

    /**
     * PU-073-inp: 入社年月日を空にして送信すると422で必須メッセージが返る
     */
    public function test_user_store_requires_hire_date(): void
    {
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $this->actingAdminInCompany($company);

        $response = $this->postJson('/company/users', $this->validUserPayload($department, ['hire_date' => '']));

        $response->assertStatus(422);
        $response->assertJsonFragment(['hire_date' => ['入社年月日は必須です。']]);
    }

    /**
     * PU-074-inp: 入社年月日に「2020/13/40」を入力すると422で日付形式エラーが返る
     */
    public function test_user_store_rejects_malformed_hire_date(): void
    {
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $this->actingAdminInCompany($company);

        $response = $this->postJson('/company/users', $this->validUserPayload($department, ['hire_date' => '2020/13/40']));

        $response->assertStatus(422);
        $response->assertJsonFragment(['hire_date' => ['入社年月日はYYYY-MM-DD形式の正しい日付で入力してください。']]);
    }

    /**
     * PU-075-inp: 性別を未選択で送信すると422で必須メッセージが返る
     */
    public function test_user_store_requires_gender(): void
    {
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $this->actingAdminInCompany($company);

        $response = $this->postJson('/company/users', $this->validUserPayload($department, ['gender' => '']));

        $response->assertStatus(422);
        $response->assertJsonFragment(['gender' => ['性別は必須です。']]);
    }

    /**
     * PU-076-inp: 性別に不正な値「unknown」を入力すると422で不正値エラーが返る
     */
    public function test_user_store_rejects_invalid_gender(): void
    {
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $this->actingAdminInCompany($company);

        $response = $this->postJson('/company/users', $this->validUserPayload($department, ['gender' => 'unknown']));

        $response->assertStatus(422);
        $response->assertJsonFragment(['gender' => ['性別が正しくありません。']]);
    }

    /**
     * PU-077-inp: 部署を未選択で送信すると422で必須メッセージが返る
     */
    public function test_user_store_requires_department(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);

        $response = $this->postJson('/company/users', [
            'name' => '山田太郎',
            'email' => 'yamada2@a.example.com',
            'password' => 'password1',
            'birth_date' => '1990-04-01',
            'hire_date' => '2015-04-01',
            'gender' => 'male',
            'department_id' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['department_id' => ['部署は必須です。']]);
    }

    /**
     * PU-078-inp: 新規登録で無効化済み部署を指定すると422で不正値エラーが返る
     */
    public function test_user_store_rejects_deactivated_department(): void
    {
        $company = Company::factory()->create();
        $department = Department::factory()->deactivated()->create(['company_id' => $company->id]);
        $this->actingAdminInCompany($company);

        $response = $this->postJson('/company/users', $this->validUserPayload($department));

        $response->assertStatus(422);
        $response->assertJsonFragment(['department_id' => ['部署が正しくありません。']]);
    }

    /**
     * PU-079-inp: 企業Aのユーザー登録で他社(企業B)の部署を指定すると422で不正値エラーが返る
     */
    public function test_user_store_rejects_department_from_other_company(): void
    {
        $companyB = Company::factory()->create();
        $departmentB = Department::factory()->create(['company_id' => $companyB->id]);

        $companyA = Company::factory()->create();
        $this->actingAdminInCompany($companyA);

        $response = $this->postJson('/company/users', $this->validUserPayload($departmentB));

        $response->assertStatus(422);
        $response->assertJsonFragment(['department_id' => ['部署が正しくありません。']]);
    }

    /**
     * PU-080-evt: パスワード欄を空にし氏名のみ変更すると氏名が更新され旧パスワードでログインできる
     */
    public function test_user_update_with_blank_password_keeps_old_password(): void
    {
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->forCompany($company)->create([
            'name' => '旧氏名',
            'password' => Hash::make('oldpassword'),
            'department_id' => $department->id,
            'birth_date' => '1990-04-01',
            'hire_date' => '2015-04-01',
            'gender' => 'male',
        ]);
        $this->actingAdminInCompany($company);

        $response = $this->put("/company/users/{$user->id}", $this->validUserPayload($department, [
            'name' => '新氏名',
            'email' => $user->email,
            'password' => '',
        ]));

        $response->assertRedirect(route('company.users.index'));
        $user->refresh();
        $this->assertSame('新氏名', $user->name);
        $this->assertTrue(Hash::check('oldpassword', $user->password));
    }

    /**
     * PU-081-inp: 所属部署が無効化済みでも部署を変更せず氏名のみ変更すると422にならず更新が成功し所属表示が維持される(Q-16)
     */
    public function test_user_update_keeps_deactivated_department_when_unchanged(): void
    {
        $company = Company::factory()->create();
        $department = Department::factory()->deactivated()->create(['company_id' => $company->id]);
        $user = User::factory()->forCompany($company)->create([
            'department_id' => $department->id,
            'birth_date' => '1990-04-01',
            'hire_date' => '2015-04-01',
            'gender' => 'male',
        ]);
        $this->actingAdminInCompany($company);

        $response = $this->put("/company/users/{$user->id}", $this->validUserPayload($department, [
            'name' => '氏名変更',
            'email' => $user->email,
            'password' => '',
        ]));

        $response->assertRedirect(route('company.users.index'));
        $user->refresh();
        $this->assertSame('氏名変更', $user->name);
        $this->assertSame($department->id, $user->department_id);
    }

    /**
     * PU-082-inp: 現所属(無効化済み)から別の無効化済み部署へ変更すると422で不正値エラーが返る
     */
    public function test_user_update_rejects_change_to_another_deactivated_department(): void
    {
        $company = Company::factory()->create();
        $departmentCurrent = Department::factory()->deactivated()->create(['company_id' => $company->id]);
        $departmentOther = Department::factory()->deactivated()->create(['company_id' => $company->id]);
        $user = User::factory()->forCompany($company)->create([
            'department_id' => $departmentCurrent->id,
            'birth_date' => '1990-04-01',
            'hire_date' => '2015-04-01',
            'gender' => 'male',
        ]);
        $this->actingAdminInCompany($company);

        $response = $this->putJson("/company/users/{$user->id}", $this->validUserPayload($departmentOther, [
            'email' => $user->email,
            'password' => '',
        ]));

        $response->assertStatus(422);
        $response->assertJsonFragment(['department_id' => ['部署が正しくありません。']]);
    }

    /**
     * PU-083-evt: ユーザーを無効化するとログインできなくなる
     */
    public function test_user_deactivate_prevents_login(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->forCompany($company)->create(['password' => Hash::make('password1')]);
        $this->actingAdminInCompany($company);

        $response = $this->post("/company/users/{$user->id}/deactivate");

        $response->assertRedirect(route('company.users.index'));
        $this->assertFalse($user->fresh()->isActive());

        $this->post('/logout');

        $loginResponse = $this->postJson('/login', [
            'company_code' => $company->code,
            'email' => $user->email,
            'password' => 'password1',
        ]);
        $loginResponse->assertStatus(422);
    }

    /**
     * PU-084-evt: 無効化済みユーザーを再有効化すると再びログインできる
     */
    public function test_user_activate_allows_login_again(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->forCompany($company)->deactivated()->create(['password' => Hash::make('password1')]);
        $this->actingAdminInCompany($company);

        $response = $this->post("/company/users/{$user->id}/activate");

        $response->assertRedirect(route('company.users.index'));
        $this->assertTrue($user->fresh()->isActive());

        $this->post('/logout');

        $loginResponse = $this->post('/login', [
            'company_code' => $company->code,
            'email' => $user->email,
            'password' => 'password1',
        ]);
        $loginResponse->assertRedirect(route('user.home'));
    }

    /**
     * PU-112-dsp: 有効・無効合わせて21名のユーザーが存在する場合、1ページ目に20件・2ページ目に残り1件が表示される
     */
    public function test_user_index_paginates_20_per_page(): void
    {
        $company = Company::factory()->create();
        User::factory()->forCompany($company)->count(21)->create();
        $this->actingAdminInCompany($company);

        $page1 = $this->get('/company/users');
        $page1->assertStatus(200);
        $this->assertCount(20, $page1->viewData('users')->items());

        $page2 = $this->get('/company/users?page=2');
        $this->assertCount(1, $page2->viewData('users')->items());
    }

    /**
     * PU-113-dsp: 部署マスタに登録済みの部署がユーザー登録画面の選択肢に表示される
     */
    public function test_user_create_shows_active_department_option(): void
    {
        $company = Company::factory()->create();
        Department::factory()->create(['company_id' => $company->id, 'name' => '人事部']);
        $this->actingAdminInCompany($company);

        $response = $this->get('/company/users/create');

        $response->assertSee('人事部');
    }

    /**
     * PU-114-dsp: 無効化済みの部署はユーザー登録画面の選択肢に表示されない
     */
    public function test_user_create_hides_deactivated_department_option(): void
    {
        $company = Company::factory()->create();
        Department::factory()->deactivated()->create(['company_id' => $company->id, 'name' => '経理部']);
        $this->actingAdminInCompany($company);

        $response = $this->get('/company/users/create');

        $response->assertDontSee('経理部');
    }

    /**
     * PU-119-other: 初期パスワードを登録するとDB上のpasswordが平文と異なりHash::checkで照合できる
     */
    public function test_user_password_is_hashed(): void
    {
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $this->actingAdminInCompany($company);

        $this->post('/company/users', $this->validUserPayload($department, ['password' => 'pass1234']));

        $user = User::query()->where('email', 'yamada@a.example.com')->first();
        $this->assertNotSame('pass1234', $user->password);
        $this->assertTrue(Hash::check('pass1234', $user->password));
    }

    /**
     * PU-126-inp: メールに255文字ちょうどの値を入力すると登録が成功する(境界OK)
     */
    public function test_user_store_accepts_email_exactly_255_chars(): void
    {
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $this->actingAdminInCompany($company);

        $localPart = str_repeat('a', 255 - strlen('@ex.com'));
        $email = $localPart.'@ex.com';
        $this->assertSame(255, strlen($email));

        $response = $this->post('/company/users', $this->validUserPayload($department, ['email' => $email]));

        $response->assertRedirect(route('company.users.index'));
        $this->assertDatabaseHas('users', ['email' => $email]);
    }

    /**
     * PU-129-inp: ユーザー編集で氏名を空にして送信すると422が返りDBの氏名が変更前の値のまま維持される
     */
    public function test_user_update_requires_name_and_keeps_original_value(): void
    {
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->forCompany($company)->create([
            'name' => '元の氏名', 'department_id' => $department->id,
            'birth_date' => '1990-04-01', 'hire_date' => '2015-04-01', 'gender' => 'male',
        ]);
        $this->actingAdminInCompany($company);

        $response = $this->putJson("/company/users/{$user->id}", $this->validUserPayload($department, [
            'name' => '', 'email' => $user->email, 'password' => '',
        ]));

        $response->assertStatus(422);
        $response->assertJsonFragment(['name' => ['氏名は必須です。']]);
        $this->assertSame('元の氏名', $user->fresh()->name);
    }

    /**
     * PU-130-inp: ユーザー編集でメールを空にして送信すると422が返りDBのメールが変更前の値のまま維持される
     */
    public function test_user_update_requires_email_and_keeps_original_value(): void
    {
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->forCompany($company)->create([
            'email' => 'keep-user@a.example.com', 'department_id' => $department->id,
            'birth_date' => '1990-04-01', 'hire_date' => '2015-04-01', 'gender' => 'male',
        ]);
        $this->actingAdminInCompany($company);

        $response = $this->putJson("/company/users/{$user->id}", $this->validUserPayload($department, [
            'email' => '', 'password' => '',
        ]));

        $response->assertStatus(422);
        $response->assertJsonFragment(['email' => ['メールアドレスは必須です。']]);
        $this->assertSame('keep-user@a.example.com', $user->fresh()->email);
    }

    /**
     * PU-131-inp: ユーザー編集で生年月日を空にして送信すると422が返りDBの生年月日が変更前の値のまま維持される
     */
    public function test_user_update_requires_birth_date_and_keeps_original_value(): void
    {
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->forCompany($company)->create([
            'department_id' => $department->id,
            'birth_date' => '1990-04-01', 'hire_date' => '2015-04-01', 'gender' => 'male',
        ]);
        $this->actingAdminInCompany($company);

        $response = $this->putJson("/company/users/{$user->id}", $this->validUserPayload($department, [
            'email' => $user->email, 'password' => '', 'birth_date' => '',
        ]));

        $response->assertStatus(422);
        $response->assertJsonFragment(['birth_date' => ['生年月日は必須です。']]);
        $this->assertSame('1990-04-01', $user->fresh()->birth_date->format('Y-m-d'));
    }

    /**
     * PU-132-inp: ユーザー編集で入社年月日を空にして送信すると422が返りDBの入社年月日が変更前の値のまま維持される
     */
    public function test_user_update_requires_hire_date_and_keeps_original_value(): void
    {
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->forCompany($company)->create([
            'department_id' => $department->id,
            'birth_date' => '1990-04-01', 'hire_date' => '2015-04-01', 'gender' => 'male',
        ]);
        $this->actingAdminInCompany($company);

        $response = $this->putJson("/company/users/{$user->id}", $this->validUserPayload($department, [
            'email' => $user->email, 'password' => '', 'hire_date' => '',
        ]));

        $response->assertStatus(422);
        $response->assertJsonFragment(['hire_date' => ['入社年月日は必須です。']]);
        $this->assertSame('2015-04-01', $user->fresh()->hire_date->format('Y-m-d'));
    }

    /**
     * PU-133-inp: ユーザー編集で性別を未選択で送信すると422が返りDBの性別が変更前の値のまま維持される
     */
    public function test_user_update_requires_gender_and_keeps_original_value(): void
    {
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->forCompany($company)->create([
            'department_id' => $department->id,
            'birth_date' => '1990-04-01', 'hire_date' => '2015-04-01', 'gender' => 'male',
        ]);
        $this->actingAdminInCompany($company);

        $response = $this->putJson("/company/users/{$user->id}", $this->validUserPayload($department, [
            'email' => $user->email, 'password' => '', 'gender' => '',
        ]));

        $response->assertStatus(422);
        $response->assertJsonFragment(['gender' => ['性別は必須です。']]);
        $this->assertSame('male', $user->fresh()->gender->value);
    }
}
