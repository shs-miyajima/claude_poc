<?php

namespace Tests\Feature\Http;

use App\Models\Company;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * PU-016-auth: role=userでスーパーユーザー専用URLへ直接アクセスすると403が返る
     */
    public function test_user_role_cannot_access_super_companies(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->forCompany($company)->create();

        $this->actingAs($user)->get('/super/companies')->assertForbidden();
    }

    /**
     * PU-017-auth: role=userで管理者管理URLへ直接アクセスすると403が返る
     */
    public function test_user_role_cannot_access_company_admins(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->forCompany($company)->create();

        $this->actingAs($user)->get('/company/admins')->assertForbidden();
    }

    /**
     * PU-018-auth: role=userでユーザー管理URLへ直接アクセスすると403が返る
     */
    public function test_user_role_cannot_access_company_users(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->forCompany($company)->create();

        $this->actingAs($user)->get('/company/users')->assertForbidden();
    }

    /**
     * PU-019-auth: role=userで部署管理URLへ直接アクセスすると403が返る
     */
    public function test_user_role_cannot_access_company_departments(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->forCompany($company)->create();

        $this->actingAs($user)->get('/company/departments')->assertForbidden();
    }

    /**
     * PU-020-auth: role=adminでスーパーユーザー専用URLへ直接アクセスすると403が返る
     */
    public function test_admin_role_cannot_access_super_companies(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->forCompany($company)->create();

        $this->actingAs($admin)->get('/super/companies')->assertForbidden();
    }

    /**
     * PU-021-auth: role=adminで管理者管理URL(スーパーユーザー専用・Q-11)へ直接アクセスすると403が返る
     */
    public function test_admin_role_cannot_access_company_admins(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->forCompany($company)->create();

        $this->actingAs($admin)->get('/company/admins')->assertForbidden();
    }

    /**
     * PU-022-auth: 企業Aの管理者が他企業(企業B)のユーザー編集URLへ直接アクセスすると403が返る
     */
    public function test_admin_cannot_access_other_company_user_edit(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $adminA = User::factory()->admin()->forCompany($companyA)->create();
        $userB = User::factory()->forCompany($companyB)->create();

        $this->actingAs($adminA)->get("/company/users/{$userB->id}/edit")->assertForbidden();
    }

    /**
     * PU-023-auth: 企業Aの管理者が他企業(企業B)の部署編集URLへ直接アクセスすると403が返る
     */
    public function test_admin_cannot_access_other_company_department_edit(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $adminA = User::factory()->admin()->forCompany($companyA)->create();
        $departmentB = Department::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($adminA)->get("/company/departments/{$departmentB->id}/edit")->assertForbidden();
    }

    /**
     * PU-024-auth: 無効化済み企業の編集URLへ直接アクセスすると403が返る
     */
    public function test_deactivated_company_edit_returns_403(): void
    {
        $superUser = User::factory()->superUser()->create();
        $company = Company::factory()->deactivated()->create();

        $this->actingAs($superUser)->get("/super/companies/{$company->id}/edit")->assertForbidden();
    }

    /**
     * PU-025-auth: 無効化済み管理者の編集URLへ直接アクセスすると403が返る
     */
    public function test_deactivated_admin_edit_returns_403(): void
    {
        $company = Company::factory()->create();
        $superUser = User::factory()->superUser()->create();
        $admin = User::factory()->admin()->forCompany($company)->deactivated()->create();

        $this->actingAs($superUser)
            ->withSession(['acting_company_id' => $company->id])
            ->get("/company/admins/{$admin->id}/edit")
            ->assertForbidden();
    }

    /**
     * PU-026-auth: 無効化済みユーザーの編集URLへ直接アクセスすると403が返る
     */
    public function test_deactivated_user_edit_returns_403(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->forCompany($company)->create();
        $user = User::factory()->forCompany($company)->deactivated()->create();

        $this->actingAs($admin)->get("/company/users/{$user->id}/edit")->assertForbidden();
    }

    /**
     * PU-027-auth: 無効化済み部署の編集URLへ直接アクセスすると403が返る
     */
    public function test_deactivated_department_edit_returns_403(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->forCompany($company)->create();
        $department = Department::factory()->deactivated()->create(['company_id' => $company->id]);

        $this->actingAs($admin)->get("/company/departments/{$department->id}/edit")->assertForbidden();
    }

    /**
     * PU-028-auth: 無効化済み企業への画面切替を試みると403が返る
     */
    public function test_switch_to_deactivated_company_returns_403(): void
    {
        $superUser = User::factory()->superUser()->create();
        $company = Company::factory()->deactivated()->create();

        $this->actingAs($superUser)
            ->post("/super/companies/{$company->id}/switch")
            ->assertForbidden();
    }

    /**
     * PU-029-auth: ログイン済みセッションがあるユーザーが別経路で無効化された場合、次のリクエストでログアウトされる
     */
    public function test_active_session_is_logged_out_once_account_deactivated(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->forCompany($company)->create();

        $this->actingAs($admin);

        $admin->update(['deactivated_at' => now()]);

        $response = $this->get('/company/home');

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    /**
     * PU-030-auth: 管理者のユーザー一覧は自社スコープのみが表示され他企業のユーザーは含まれない
     */
    public function test_admin_user_list_is_scoped_to_own_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $adminA = User::factory()->admin()->forCompany($companyA)->create();
        $userA = User::factory()->forCompany($companyA)->create(['name' => '企業Aユーザー']);
        $userB = User::factory()->forCompany($companyB)->create(['name' => '企業Bユーザー']);

        $response = $this->actingAs($adminA)->get('/company/users');

        $response->assertSee('企業Aユーザー');
        $response->assertDontSee('企業Bユーザー');
    }

    /**
     * PU-031-auth: スーパーユーザーが個別企業画面へ切替前に直接アクセスすると403が返る
     */
    public function test_super_user_without_switch_cannot_access_company_home(): void
    {
        $superUser = User::factory()->superUser()->create();

        $this->actingAs($superUser)->get('/company/home')->assertForbidden();
    }

    /**
     * PU-135-auth: role=userでCSVアップロード画面へ直接アクセスすると403が返る
     */
    public function test_user_role_cannot_access_users_csv(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->forCompany($company)->create();

        $this->actingAs($user)->get('/company/users/csv')->assertForbidden();
    }
}
