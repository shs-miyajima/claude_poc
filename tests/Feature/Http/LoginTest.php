<?php

namespace Tests\Feature\Http;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * PU-001-inp: メールアドレス欄を空にして送信すると422で必須メッセージが返る
     */
    public function test_login_requires_email(): void
    {
        $response = $this->postJson('/login', [
            'company_code' => '',
            'email' => '',
            'password' => 'password1',
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['email' => ['メールアドレスは必須です。']]);
    }

    /**
     * PU-002-inp: パスワード欄を空にして送信すると422で必須メッセージが返る
     */
    public function test_login_requires_password(): void
    {
        $response = $this->postJson('/login', [
            'company_code' => '',
            'email' => 'user@example.com',
            'password' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['password' => ['パスワードは必須です。']]);
    }

    /**
     * PU-003-auth: 正しい企業コード+メール+パスワードで管理者ログインするとcompany.homeへリダイレクトされる
     */
    public function test_admin_login_succeeds_and_redirects_to_company_home(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->forCompany($company)->create(['password' => Hash::make('password1')]);

        $response = $this->post('/login', [
            'company_code' => $company->code,
            'email' => $admin->email,
            'password' => 'password1',
        ]);

        $response->assertRedirect(route('company.home'));
        $this->assertAuthenticatedAs($admin);
    }

    /**
     * PU-004-auth: 正しい企業コード+メール+パスワードでユーザーログインするとuser.homeへリダイレクトされる
     */
    public function test_user_login_succeeds_and_redirects_to_user_home(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->forCompany($company)->create(['password' => Hash::make('password1')]);

        $response = $this->post('/login', [
            'company_code' => $company->code,
            'email' => $user->email,
            'password' => 'password1',
        ]);

        $response->assertRedirect(route('user.home'));
        $this->assertAuthenticatedAs($user);
    }

    /**
     * PU-005-auth: 企業コード空欄+正しいメール+パスワードでスーパーユーザーログインするとsuper.companies.indexへリダイレクトされる
     */
    public function test_super_user_login_succeeds_and_redirects_to_companies_index(): void
    {
        $superUser = User::factory()->superUser()->create(['password' => Hash::make('password1')]);

        $response = $this->post('/login', [
            'company_code' => '',
            'email' => $superUser->email,
            'password' => 'password1',
        ]);

        $response->assertRedirect(route('super.companies.index'));
        $this->assertAuthenticatedAs($superUser);
    }

    /**
     * PU-006-auth: 存在しない企業コードでログインすると統一メッセージで422が返る
     */
    public function test_login_fails_with_nonexistent_company_code(): void
    {
        $response = $this->postJson('/login', [
            'company_code' => 'C9999',
            'email' => 'anyone@example.com',
            'password' => 'password1',
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['email' => ['企業コード、メールアドレス、またはパスワードが正しくありません。']]);
        $this->assertGuest();
    }

    /**
     * PU-007-auth: 企業コードが形式不正(6文字)でもVAL-02と同一の統一メッセージで422が返る
     */
    public function test_login_fails_with_malformed_company_code(): void
    {
        Company::factory()->create();

        $response = $this->postJson('/login', [
            'company_code' => 'C00011',
            'email' => 'anyone@example.com',
            'password' => 'password1',
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['email' => ['企業コード、メールアドレス、またはパスワードが正しくありません。']]);
    }

    /**
     * PU-008-auth: 企業コードは存在するがメールが存在しない場合も統一メッセージで422が返る
     */
    public function test_login_fails_with_nonexistent_email(): void
    {
        $company = Company::factory()->create();

        $response = $this->postJson('/login', [
            'company_code' => $company->code,
            'email' => 'nobody@example.com',
            'password' => 'password1',
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['email' => ['企業コード、メールアドレス、またはパスワードが正しくありません。']]);
    }

    /**
     * PU-009-auth: メール一致・パスワード不一致の場合も統一メッセージで422が返る
     */
    public function test_login_fails_with_wrong_password(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->forCompany($company)->create(['password' => Hash::make('password1')]);

        $response = $this->postJson('/login', [
            'company_code' => $company->code,
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['email' => ['企業コード、メールアドレス、またはパスワードが正しくありません。']]);
    }

    /**
     * PU-010-auth: 無効化済みユーザーは正しい情報でも認証されず統一メッセージで422が返る
     */
    public function test_login_fails_for_deactivated_user(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->forCompany($company)->deactivated()->create(['password' => Hash::make('password1')]);

        $response = $this->postJson('/login', [
            'company_code' => $company->code,
            'email' => $user->email,
            'password' => 'password1',
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['email' => ['企業コード、メールアドレス、またはパスワードが正しくありません。']]);
        $this->assertGuest();
    }

    /**
     * PU-011-auth: 無効化済み企業に所属する管理者は正しい情報でも認証されない
     */
    public function test_login_fails_for_deactivated_company(): void
    {
        $company = Company::factory()->deactivated()->create();
        $admin = User::factory()->admin()->forCompany($company)->create(['password' => Hash::make('password1')]);

        $response = $this->postJson('/login', [
            'company_code' => $company->code,
            'email' => $admin->email,
            'password' => 'password1',
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['email' => ['企業コード、メールアドレス、またはパスワードが正しくありません。']]);
    }

    /**
     * PU-012-auth: 存在しないメールと無効化済みアカウントの失敗メッセージが完全に一致する(NFR-05)
     */
    public function test_login_failure_messages_are_identical_regardless_of_reason(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->forCompany($company)->deactivated()->create(['password' => Hash::make('password1')]);

        $responseNonexistentEmail = $this->postJson('/login', [
            'company_code' => $company->code,
            'email' => 'nobody@example.com',
            'password' => 'password1',
        ]);

        $responseDeactivated = $this->postJson('/login', [
            'company_code' => $company->code,
            'email' => $user->email,
            'password' => 'password1',
        ]);

        $this->assertSame(
            $responseNonexistentEmail->json('errors.email.0'),
            $responseDeactivated->json('errors.email.0'),
        );
    }

    /**
     * PU-013-auth: ログアウト後に保護画面へアクセスするとログイン画面へリダイレクトされる
     */
    public function test_logout_redirects_to_login_on_next_protected_access(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->forCompany($company)->create();

        $this->actingAs($admin)->post('/logout')->assertRedirect(route('login'));

        $this->assertGuest();

        $this->get('/company/home')->assertRedirect(route('login'));
    }

    /**
     * PU-014-auth: 未ログインで認証必須URLへ直接アクセスするとログイン画面へリダイレクトされる
     */
    public function test_unauthenticated_access_redirects_to_login(): void
    {
        $response = $this->get('/company/home');

        $response->assertRedirect(route('login'));
    }

    /**
     * PU-015-auth: SuperUserSeeder投入済みスーパーユーザーでログインできる
     */
    public function test_seeded_super_user_can_login(): void
    {
        $this->seed(\Database\Seeders\SuperUserSeeder::class);

        $response = $this->post('/login', [
            'company_code' => '',
            'email' => env('SUPER_USER_EMAIL', 'super@example.com'),
            'password' => env('SUPER_USER_PASSWORD', 'super1234'),
        ]);

        $response->assertRedirect(route('super.companies.index'));
        $this->assertAuthenticated();
    }
}
