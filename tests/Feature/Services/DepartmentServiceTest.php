<?php

namespace Tests\Feature\Services;

use App\Models\Company;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private function actingAdminInCompany(Company $company): User
    {
        $admin = User::factory()->admin()->forCompany($company)->create();

        $this->actingAs($admin);

        return $admin;
    }

    /**
     * PU-085-evt: 部署名「人事部」を入力し登録すると部署マスタに追加され一覧に表示される
     */
    public function test_admin_can_create_department(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);

        $response = $this->post('/company/departments', ['name' => '人事部']);

        $response->assertRedirect(route('company.departments.index'));

        $indexResponse = $this->get('/company/departments');
        $indexResponse->assertSee('人事部');
    }

    /**
     * PU-086-inp: 部署名を空にして送信すると422で必須メッセージが返る
     */
    public function test_department_store_requires_name(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);

        $response = $this->postJson('/company/departments', ['name' => '']);

        $response->assertStatus(422);
        $response->assertJsonFragment(['name' => ['部署名は必須です。']]);
    }

    /**
     * PU-087-inp: 部署名に101文字を入力すると422で最大長エラーが返る
     */
    public function test_department_store_rejects_name_over_100_chars(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);

        $response = $this->postJson('/company/departments', ['name' => str_repeat('部', 101)]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['name' => ['部署名は100文字以内で入力してください。']]);
    }

    /**
     * PU-088-inp: 部署名に100文字を入力すると登録が成功する(境界OK)
     */
    public function test_department_store_accepts_name_exactly_100_chars(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);

        $response = $this->post('/company/departments', ['name' => str_repeat('部', 100)]);

        $response->assertRedirect(route('company.departments.index'));
        $this->assertDatabaseHas('departments', ['name' => str_repeat('部', 100), 'company_id' => $company->id]);
    }

    /**
     * PU-089-inp: 無効化済み部署「総務部」と同一部署名を入力すると422で重複エラーが返る
     */
    public function test_department_store_rejects_duplicate_name_including_deactivated(): void
    {
        $company = Company::factory()->create();
        Department::factory()->deactivated()->create(['company_id' => $company->id, 'name' => '総務部']);
        $this->actingAdminInCompany($company);

        $response = $this->postJson('/company/departments', ['name' => '総務部']);

        $response->assertStatus(422);
        $response->assertJsonFragment(['name' => ['この部署名は既に登録されています。']]);
    }

    /**
     * PU-090-inp: 企業Bに部署名「経理部」が存在しても企業単位一意のため登録が成功する
     */
    public function test_department_store_allows_same_name_in_different_company(): void
    {
        $companyB = Company::factory()->create();
        Department::factory()->create(['company_id' => $companyB->id, 'name' => '経理部']);

        $companyA = Company::factory()->create();
        $this->actingAdminInCompany($companyA);

        $response = $this->post('/company/departments', ['name' => '経理部']);

        $response->assertRedirect(route('company.departments.index'));
        $this->assertDatabaseHas('departments', ['name' => '経理部', 'company_id' => $companyA->id]);
    }

    /**
     * PU-091-evt: 部署名を「新部署名」に変更して送信すると部署名が更新される
     */
    public function test_admin_can_update_department_name(): void
    {
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id, 'name' => '旧部署名']);
        $this->actingAdminInCompany($company);

        $response = $this->put("/company/departments/{$department->id}", ['name' => '新部署名']);

        $response->assertRedirect(route('company.departments.index'));
        $this->assertDatabaseHas('departments', ['id' => $department->id, 'name' => '新部署名']);
    }

    /**
     * PU-092-evt: 所属ユーザーがいる部署を無効化すると選択肢から消えるが所属ユーザーの表示は維持される
     */
    public function test_deactivate_department_hides_from_options_but_keeps_user_assignment(): void
    {
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id, 'name' => '人事部']);
        $user = User::factory()->forCompany($company)->create(['department_id' => $department->id]);
        $this->actingAdminInCompany($company);

        $response = $this->post("/company/departments/{$department->id}/deactivate");

        $response->assertRedirect(route('company.departments.index'));
        $this->assertFalse($department->fresh()->isActive());

        $createResponse = $this->get('/company/users/create');
        $createResponse->assertDontSee('人事部');

        $this->assertSame($department->id, $user->fresh()->department_id);
    }

    /**
     * PU-093-evt: 無効化済み部署を再有効化すると部署選択肢に再度表示される
     */
    public function test_activate_department_reappears_in_options(): void
    {
        $company = Company::factory()->create();
        $department = Department::factory()->deactivated()->create(['company_id' => $company->id, 'name' => '経理部']);
        $this->actingAdminInCompany($company);

        $response = $this->post("/company/departments/{$department->id}/activate");

        $response->assertRedirect(route('company.departments.index'));
        $this->assertTrue($department->fresh()->isActive());

        $createResponse = $this->get('/company/users/create');
        $createResponse->assertSee('経理部');
    }

    /**
     * PU-134-inp: 部署編集で部署名を空にして送信すると422が返りDBの部署名が変更前の値のまま維持される
     */
    public function test_department_update_requires_name_and_keeps_original_value(): void
    {
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id, 'name' => '維持される部署名']);
        $this->actingAdminInCompany($company);

        $response = $this->putJson("/company/departments/{$department->id}", ['name' => '']);

        $response->assertStatus(422);
        $response->assertJsonFragment(['name' => ['部署名は必須です。']]);
        $this->assertSame('維持される部署名', $department->fresh()->name);
    }
}
