<?php

namespace Tests\Feature\Services;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminServiceTest extends TestCase
{
    use RefreshDatabase;

    private function actingSuperUserInCompany(Company $company): User
    {
        $superUser = User::factory()->superUser()->create();

        $this->actingAs($superUser)->withSession(['acting_company_id' => $company->id]);

        return $superUser;
    }

    /**
     * PU-045-evt: 氏名・メール・初期パスワードを入力し登録するとrole=adminの管理者が作成されパスワード照合できる
     */
    public function test_super_user_can_create_admin(): void
    {
        $company = Company::factory()->create();
        $this->actingSuperUserInCompany($company);

        $response = $this->post('/company/admins', [
            'name' => '管理太郎',
            'email' => 'admin1@a.example.com',
            'password' => 'password1',
        ]);

        $response->assertRedirect(route('company.admins.index'));

        $admin = User::query()->where('email', 'admin1@a.example.com')->first();
        $this->assertNotNull($admin);
        $this->assertSame('admin', $admin->role->value);
        $this->assertTrue(Hash::check('password1', $admin->password));
    }

    /**
     * PU-046-inp: 氏名を空にして送信すると422で必須メッセージが返る
     */
    public function test_admin_store_requires_name(): void
    {
        $company = Company::factory()->create();
        $this->actingSuperUserInCompany($company);

        $response = $this->postJson('/company/admins', [
            'name' => '',
            'email' => 'admin2@a.example.com',
            'password' => 'password1',
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['name' => ['氏名は必須です。']]);
    }

    /**
     * PU-047-inp: メールを空にして送信すると422で必須メッセージが返る
     */
    public function test_admin_store_requires_email(): void
    {
        $company = Company::factory()->create();
        $this->actingSuperUserInCompany($company);

        $response = $this->postJson('/company/admins', [
            'name' => '管理太郎',
            'email' => '',
            'password' => 'password1',
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['email' => ['メールアドレスは必須です。']]);
    }

    /**
     * PU-048-inp: メールに形式不正な値を入力すると422で形式エラーが返る
     */
    public function test_admin_store_rejects_malformed_email(): void
    {
        $company = Company::factory()->create();
        $this->actingSuperUserInCompany($company);

        $response = $this->postJson('/company/admins', [
            'name' => '管理太郎',
            'email' => 'abc',
            'password' => 'password1',
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['email' => ['メールアドレスの形式が正しくありません。']]);
    }

    /**
     * PU-049-inp: メールに256文字の値を入力すると422で最大長エラーが返る
     */
    public function test_admin_store_rejects_email_over_255_chars(): void
    {
        $company = Company::factory()->create();
        $this->actingSuperUserInCompany($company);

        $longEmail = str_repeat('a', 250).'@ex.com';
        $this->assertGreaterThan(255, strlen($longEmail));

        $response = $this->postJson('/company/admins', [
            'name' => '管理太郎',
            'email' => $longEmail,
            'password' => 'password1',
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['email' => ['メールアドレスは255文字以内で入力してください。']]);
    }

    /**
     * PU-050-inp: 無効化済み管理者と同一メールを入力すると422で重複エラーが返る
     */
    public function test_admin_store_rejects_duplicate_email_including_deactivated(): void
    {
        $company = Company::factory()->create();
        User::factory()->admin()->forCompany($company)->deactivated()->create(['email' => 'dup@a.example.com']);
        $this->actingSuperUserInCompany($company);

        $response = $this->postJson('/company/admins', [
            'name' => '管理太郎',
            'email' => 'dup@a.example.com',
            'password' => 'password1',
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['email' => ['このメールアドレスは既に登録されています。']]);
    }

    /**
     * PU-051-inp: 初期パスワードを空にして送信すると422で必須メッセージが返る
     */
    public function test_admin_store_requires_password(): void
    {
        $company = Company::factory()->create();
        $this->actingSuperUserInCompany($company);

        $response = $this->postJson('/company/admins', [
            'name' => '管理太郎',
            'email' => 'admin3@a.example.com',
            'password' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['password' => ['初期パスワードは必須です。']]);
    }

    /**
     * PU-052-inp: 初期パスワードに7文字を入力すると422で境界NGエラーが返る
     */
    public function test_admin_store_rejects_password_under_8_chars(): void
    {
        $company = Company::factory()->create();
        $this->actingSuperUserInCompany($company);

        $response = $this->postJson('/company/admins', [
            'name' => '管理太郎',
            'email' => 'admin4@a.example.com',
            'password' => 'pass123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['password' => ['パスワードは8文字以上で入力してください。']]);
    }

    /**
     * PU-053-inp: 初期パスワードに8文字を入力すると登録が成功する(境界OK)
     */
    public function test_admin_store_accepts_password_exactly_8_chars(): void
    {
        $company = Company::factory()->create();
        $this->actingSuperUserInCompany($company);

        $response = $this->post('/company/admins', [
            'name' => '管理太郎',
            'email' => 'admin5@a.example.com',
            'password' => 'pass1234',
        ]);

        $response->assertRedirect(route('company.admins.index'));
        $this->assertDatabaseHas('users', ['email' => 'admin5@a.example.com']);
    }

    /**
     * PU-054-evt: パスワード欄を空にし氏名のみ変更すると氏名が更新され旧パスワードでログインできる
     */
    public function test_admin_update_with_blank_password_keeps_old_password(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->forCompany($company)->create([
            'name' => '旧氏名', 'password' => Hash::make('oldpassword'),
        ]);
        $this->actingSuperUserInCompany($company);

        $response = $this->put("/company/admins/{$admin->id}", [
            'name' => '新氏名',
            'email' => $admin->email,
            'password' => '',
        ]);

        $response->assertRedirect(route('company.admins.index'));
        $admin->refresh();
        $this->assertSame('新氏名', $admin->name);
        $this->assertTrue(Hash::check('oldpassword', $admin->password));
    }

    /**
     * PU-055-inp: 管理者編集でパスワード欄を空にして送信しても422にならず更新が成功する(Q-08)
     */
    public function test_admin_update_allows_blank_password(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->forCompany($company)->create();
        $this->actingSuperUserInCompany($company);

        $response = $this->put("/company/admins/{$admin->id}", [
            'name' => $admin->name,
            'email' => $admin->email,
            'password' => '',
        ]);

        $response->assertStatus(302);
        $response->assertRedirect(route('company.admins.index'));
    }

    /**
     * PU-056-evt: 管理者を無効化するとログインできなくなる
     */
    public function test_admin_deactivate_prevents_login(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->forCompany($company)->create(['password' => Hash::make('password1')]);
        $this->actingSuperUserInCompany($company);

        $response = $this->post("/company/admins/{$admin->id}/deactivate");

        $response->assertRedirect(route('company.admins.index'));
        $this->assertFalse($admin->fresh()->isActive());

        $this->post('/logout');

        $loginResponse = $this->postJson('/login', [
            'company_code' => $company->code,
            'email' => $admin->email,
            'password' => 'password1',
        ]);
        $loginResponse->assertStatus(422);
    }

    /**
     * PU-057-evt: 無効化済み管理者を再有効化すると再びログインできる
     */
    public function test_admin_activate_allows_login_again(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->forCompany($company)->deactivated()->create(['password' => Hash::make('password1')]);
        $this->actingSuperUserInCompany($company);

        $response = $this->post("/company/admins/{$admin->id}/activate");

        $response->assertRedirect(route('company.admins.index'));
        $this->assertTrue($admin->fresh()->isActive());

        $this->post('/logout');

        $loginResponse = $this->post('/login', [
            'company_code' => $company->code,
            'email' => $admin->email,
            'password' => 'password1',
        ]);
        $loginResponse->assertRedirect(route('company.home'));
    }

    /**
     * PU-127-inp: 管理者編集で氏名を空にして送信すると422が返りDBの氏名が変更前の値のまま維持される
     */
    public function test_admin_update_requires_name_and_keeps_original_value(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->forCompany($company)->create(['name' => '元の氏名']);
        $this->actingSuperUserInCompany($company);

        $response = $this->putJson("/company/admins/{$admin->id}", [
            'name' => '',
            'email' => $admin->email,
            'password' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['name' => ['氏名は必須です。']]);
        $this->assertSame('元の氏名', $admin->fresh()->name);
    }

    /**
     * PU-128-inp: 管理者編集でメールを空にして送信すると422が返りDBのメールが変更前の値のまま維持される
     */
    public function test_admin_update_requires_email_and_keeps_original_value(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->forCompany($company)->create(['email' => 'keep@a.example.com']);
        $this->actingSuperUserInCompany($company);

        $response = $this->putJson("/company/admins/{$admin->id}", [
            'name' => $admin->name,
            'email' => '',
            'password' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['email' => ['メールアドレスは必須です。']]);
        $this->assertSame('keep@a.example.com', $admin->fresh()->email);
    }
}
