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
}
