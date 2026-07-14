<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class RegressionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * PU-122-other: 既存welcomeルートがルート追加後も200を返す
     */
    public function test_existing_welcome_route_still_returns_200(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    /**
     * PU-123-other: DatabaseSeederがSuperUserSeederを呼び出しエラーなく完走する
     */
    public function test_database_seeder_runs_super_user_seeder(): void
    {
        Artisan::call('db:seed');

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', [
            'email' => env('SUPER_USER_EMAIL', 'super@example.com'),
            'role' => 'super_user',
        ]);
    }

    /**
     * PU-124-other: UserFactoryのデフォルト状態でエラーなくユーザーが作成される
     */
    public function test_user_factory_default_state_creates_user(): void
    {
        $user = User::factory()->create();

        $this->assertNotNull($user->id);
        $this->assertSame('user', $user->role->value);
    }

    /**
     * PU-125-other: APP_LOCALE=ja設定下でバリデーションエラーが日本語で返る
     */
    public function test_validation_error_message_is_japanese(): void
    {
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $admin = User::factory()->admin()->forCompany($company)->create();

        $response = $this->actingAs($admin)
            ->withSession(['acting_company_id' => $company->id])
            ->postJson('/company/users', [
                'name' => '',
                'email' => 'regression@example.com',
                'password' => 'password1',
                'birth_date' => '1990-01-01',
                'hire_date' => '2020-01-01',
                'gender' => 'male',
                'department_id' => $department->id,
            ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['name' => ['氏名は必須です。']]);
    }

    /**
     * PU-080-other: アンケート機能追加後に既存のユーザー管理画面へアクセスすると200が返る
     */
    public function test_existing_user_index_route_still_returns_200_after_survey_feature(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->forCompany($company)->create();

        $response = $this->actingAs($admin)->get('/company/users');

        $response->assertOk();
    }

    /**
     * PU-081-other: 企業ホーム画面を取得すると既存のdata-testid(nav-users等)のリンクがアンケート機能追加後も表示される
     */
    public function test_existing_nav_links_still_displayed_after_survey_feature(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->forCompany($company)->create();

        $response = $this->actingAs($admin)->get('/company/home');

        $response->assertSee('data-testid="nav-users"', false);
        $response->assertSee('data-testid="nav-departments"', false);
        $response->assertSee('data-testid="nav-users-csv"', false);
    }

    /**
     * PU-082-other: アンケート機能追加(JS変更)後にユーザー管理画面を取得すると200が返り既存画面の表示に影響がない
     */
    public function test_existing_user_index_view_unaffected_by_survey_js_change(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->forCompany($company)->create();

        $response = $this->actingAs($admin)->get('/company/users');

        $response->assertOk();
        $response->assertSee('data-testid="user-list"', false);
    }

    /**
     * PU-083-other: 既存の部署登録画面で部署名を空欄にして送信すると従来どおり「部署名は必須です。」の422エラーが返る
     */
    public function test_existing_department_validation_message_unaffected_by_survey_validation_attributes(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->forCompany($company)->create();

        $response = $this->actingAs($admin)->postJson('/company/departments', ['name' => '']);

        $response->assertStatus(422);
        $response->assertJsonFragment(['name' => ['部署名は必須です。']]);
    }

    /**
     * PU-086-other: 企業ホーム画面を取得すると既存の「ユーザー管理」「部署マスタ管理」「ユーザーCSV一括登録」リンクがアンケート機能追加後も表示される
     */
    public function test_existing_home_menu_links_still_displayed_after_survey_feature(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->forCompany($company)->create();

        $response = $this->actingAs($admin)->get('/company/home');

        $response->assertSee('ユーザー管理');
        $response->assertSee('部署マスタ管理');
        $response->assertSee('ユーザーCSV一括登録');
    }
}
