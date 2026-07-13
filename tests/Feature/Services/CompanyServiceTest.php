<?php

namespace Tests\Feature\Services;

use App\Models\Company;
use App\Models\User;
use App\Services\CompanyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * PU-032-other: companiesテーブルが空の状態でCompanyService::create すると企業コードC0001が採番される
     */
    public function test_next_code_starts_from_c0001(): void
    {
        $company = app(CompanyService::class)->create(['name' => '株式会社サンプル']);

        $this->assertSame('C0001', $company->code);
    }

    /**
     * PU-033-other: 既存最大コードがC0005の場合CompanyService::createするとC0006が採番される
     */
    public function test_next_code_increments_from_existing_max(): void
    {
        Company::factory()->create(['code' => 'C0005']);

        $company = app(CompanyService::class)->create(['name' => '次の企業']);

        $this->assertSame('C0006', $company->code);
    }

    /**
     * PU-034-evt: スーパーユーザーが企業登録すると一覧に企業名と採番済み企業コードが表示される
     */
    public function test_super_user_can_register_company(): void
    {
        $superUser = User::factory()->superUser()->create();

        $response = $this->actingAs($superUser)->post('/super/companies', ['name' => '株式会社サンプル']);

        $response->assertRedirect(route('super.companies.index'));

        $indexResponse = $this->actingAs($superUser)->get('/super/companies');
        $indexResponse->assertSee('株式会社サンプル');
        $indexResponse->assertSee('C0001');
    }

    /**
     * PU-035-inp: 企業名を空にして送信すると422で必須メッセージが返る
     */
    public function test_company_store_requires_name(): void
    {
        $superUser = User::factory()->superUser()->create();

        $response = $this->actingAs($superUser)->postJson('/super/companies', ['name' => '']);

        $response->assertStatus(422);
        $response->assertJsonFragment(['name' => ['企業名は必須です。']]);
    }

    /**
     * PU-036-inp: 企業名に101文字を入力すると422で最大長エラーが返る
     */
    public function test_company_store_rejects_name_over_100_chars(): void
    {
        $superUser = User::factory()->superUser()->create();

        $response = $this->actingAs($superUser)->postJson('/super/companies', ['name' => str_repeat('あ', 101)]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['name' => ['企業名は100文字以内で入力してください。']]);
    }

    /**
     * PU-037-inp: 企業名に100文字ちょうどを入力すると登録が成功する(境界OK)
     */
    public function test_company_store_accepts_name_exactly_100_chars(): void
    {
        $superUser = User::factory()->superUser()->create();

        $response = $this->actingAs($superUser)->post('/super/companies', ['name' => str_repeat('あ', 100)]);

        $response->assertRedirect(route('super.companies.index'));
        $this->assertDatabaseHas('companies', ['name' => str_repeat('あ', 100)]);
    }

    /**
     * PU-038-evt: 企業名を変更して送信すると企業一覧・編集画面の企業名が更新される
     */
    public function test_super_user_can_update_company_name(): void
    {
        $superUser = User::factory()->superUser()->create();
        $company = Company::factory()->create(['name' => '旧会社名']);

        $response = $this->actingAs($superUser)->put("/super/companies/{$company->id}", ['name' => '新会社名']);

        $response->assertRedirect(route('super.companies.index'));
        $this->assertDatabaseHas('companies', ['id' => $company->id, 'name' => '新会社名']);
    }

    /**
     * PU-039-inp: 企業編集で企業名を空にして送信すると422が返りDBの企業名が変更前の値のまま維持される
     */
    public function test_company_update_requires_name_and_keeps_original_value(): void
    {
        $superUser = User::factory()->superUser()->create();
        $company = Company::factory()->create(['name' => '元の会社名']);

        $response = $this->actingAs($superUser)->putJson("/super/companies/{$company->id}", ['name' => '']);

        $response->assertStatus(422);
        $response->assertJsonFragment(['name' => ['企業名は必須です。']]);
        $this->assertDatabaseHas('companies', ['id' => $company->id, 'name' => '元の会社名']);
    }

    /**
     * PU-040-evt: 企業を無効化すると企業と配下の管理者・ユーザーがすべて無効化されるが部署は無効化されない
     */
    public function test_deactivate_company_cascades_to_admins_and_users_but_not_departments(): void
    {
        $superUser = User::factory()->superUser()->create();
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->forCompany($company)->create();
        $user = User::factory()->forCompany($company)->create();
        $department = \App\Models\Department::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($superUser)->post("/super/companies/{$company->id}/deactivate");

        $response->assertRedirect(route('super.companies.index'));
        $this->assertFalse($company->fresh()->isActive());
        $this->assertFalse($admin->fresh()->isActive());
        $this->assertFalse($user->fresh()->isActive());
        $this->assertTrue($department->fresh()->isActive());
    }

    /**
     * PU-041-evt: 無効化済み企業を再有効化すると企業は有効に戻るが配下の管理者は無効化されたままである
     */
    public function test_activate_company_does_not_reactivate_admins(): void
    {
        $superUser = User::factory()->superUser()->create();
        $company = Company::factory()->deactivated()->create();
        $admin = User::factory()->admin()->forCompany($company)->deactivated()->create();

        $response = $this->actingAs($superUser)->post("/super/companies/{$company->id}/activate");

        $response->assertRedirect(route('super.companies.index'));
        $this->assertTrue($company->fresh()->isActive());
        $this->assertFalse($admin->fresh()->isActive());
    }

    /**
     * PU-042-evt: 企業へ切替を実行するとセッションにacting_company_idが保存され企業のデータが表示される
     */
    public function test_switch_enter_stores_acting_company_id_in_session(): void
    {
        $superUser = User::factory()->superUser()->create();
        $company = Company::factory()->create();

        $response = $this->actingAs($superUser)->post("/super/companies/{$company->id}/switch");

        $response->assertRedirect(route('company.home'));
        $this->assertSame($company->id, session('acting_company_id'));

        $homeResponse = $this->get('/company/home');
        $homeResponse->assertSee($company->name);
    }

    /**
     * PU-043-evt: 全体画面へ戻る操作を実行するとセッションのacting_company_idが削除され企業一覧へ遷移できる
     */
    public function test_switch_exit_removes_acting_company_id_from_session(): void
    {
        $superUser = User::factory()->superUser()->create();
        $company = Company::factory()->create();

        $response = $this->actingAs($superUser)
            ->withSession(['acting_company_id' => $company->id])
            ->post('/super/switch/exit');

        $response->assertRedirect(route('super.companies.index'));
        $this->assertNull(session('acting_company_id'));

        $indexResponse = $this->get('/super/companies');
        $indexResponse->assertStatus(200);
    }

    /**
     * PU-044-dsp: 無効化済み企業は一覧に「無効」の状態表示があり切替導線が表示されない
     */
    public function test_company_index_shows_inactive_status_and_hides_switch_link(): void
    {
        $superUser = User::factory()->superUser()->create();
        $company = Company::factory()->deactivated()->create();

        $response = $this->actingAs($superUser)->get('/super/companies');

        $response->assertSee('無効');
        $response->assertDontSee("company-switch-{$company->id}");
    }
}
