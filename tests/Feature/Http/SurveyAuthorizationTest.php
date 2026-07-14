<?php

namespace Tests\Feature\Http;

use App\Models\Company;
use App\Models\Survey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurveyAuthorizationTest extends TestCase
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
    private function validSurveyPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'アンケートタイトル',
            'answer_start_date' => '2026-08-01',
            'answer_end_date' => '2026-08-31',
            'answer_visibility' => 'named',
            'questions' => [['body' => '設問文', 'question_type' => 'free_text']],
        ], $overrides);
    }

    /**
     * PU-066-auth: 下書きのアンケートAの詳細・編集にアクセスするといずれも200が返り403にならない
     */
    public function test_admin_can_access_own_company_draft_survey(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);
        $survey = Survey::factory()->create(['company_id' => $company->id]);

        $this->get("/company/surveys/{$survey->id}")->assertOk();
        $this->get("/company/surveys/{$survey->id}/edit")->assertOk();
    }

    /**
     * PU-067-auth: 企業Aの管理者が他社アンケートの詳細URLへ直接アクセスすると403が返る
     */
    public function test_admin_cannot_view_other_company_survey(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $this->actingAdminInCompany($companyA);
        $surveyB = Survey::factory()->create(['company_id' => $companyB->id]);

        $this->get("/company/surveys/{$surveyB->id}")->assertForbidden();
    }

    /**
     * PU-068-auth: 企業Aの管理者が他社アンケートの編集URLへ直接アクセスすると403が返る
     */
    public function test_admin_cannot_edit_other_company_survey(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $this->actingAdminInCompany($companyA);
        $surveyB = Survey::factory()->create(['company_id' => $companyB->id]);

        $this->get("/company/surveys/{$surveyB->id}/edit")->assertForbidden();
    }

    /**
     * PU-069-auth: 企業Aの管理者が他社アンケートの更新に直接アクセスすると403が返る
     */
    public function test_admin_cannot_update_other_company_survey(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $this->actingAdminInCompany($companyA);
        $surveyB = Survey::factory()->create(['company_id' => $companyB->id]);

        $this->put("/company/surveys/{$surveyB->id}", $this->validSurveyPayload())->assertForbidden();
    }

    /**
     * PU-070-auth: 企業Aの管理者が他社アンケートの削除に直接アクセスすると403が返る
     */
    public function test_admin_cannot_delete_other_company_survey(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $this->actingAdminInCompany($companyA);
        $surveyB = Survey::factory()->create(['company_id' => $companyB->id]);

        $this->delete("/company/surveys/{$surveyB->id}")->assertForbidden();
    }

    /**
     * PU-071-auth: 企業Aの管理者が他社アンケートの公開に直接アクセスすると403が返る
     */
    public function test_admin_cannot_publish_other_company_survey(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $this->actingAdminInCompany($companyA);
        $surveyB = Survey::factory()->create(['company_id' => $companyB->id]);

        $this->post("/company/surveys/{$surveyB->id}/publish")->assertForbidden();
    }

    /**
     * PU-072-auth: 公開済みのアンケートAの編集URLへ直接アクセスすると403が返る
     */
    public function test_cannot_edit_published_survey(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);
        $survey = Survey::factory()->published()->create(['company_id' => $company->id]);

        $this->get("/company/surveys/{$survey->id}/edit")->assertForbidden();
    }

    /**
     * PU-073-auth: 公開済みのアンケートAの更新に直接アクセスすると403が返る
     */
    public function test_cannot_update_published_survey(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);
        $survey = Survey::factory()->published()->create(['company_id' => $company->id]);

        $this->put("/company/surveys/{$survey->id}", $this->validSurveyPayload())->assertForbidden();
    }

    /**
     * PU-074-auth: 公開済みのアンケートAの削除に直接アクセスすると403が返る
     */
    public function test_cannot_delete_published_survey(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);
        $survey = Survey::factory()->published()->create(['company_id' => $company->id]);

        $this->delete("/company/surveys/{$survey->id}")->assertForbidden();
    }

    /**
     * PU-075-auth: 公開済みのアンケートAの公開操作に再度直接アクセスすると403が返る(二重公開防止)
     */
    public function test_cannot_republish_published_survey(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);
        $survey = Survey::factory()->published()->create(['company_id' => $company->id]);

        $this->post("/company/surveys/{$survey->id}/publish")->assertForbidden();
    }

    /**
     * PU-076-auth: 公開済みのアンケートAの詳細URLへアクセスすると200が返り閲覧できる
     */
    public function test_can_view_published_survey(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);
        $survey = Survey::factory()->published()->create(['company_id' => $company->id]);

        $this->get("/company/surveys/{$survey->id}")->assertOk();
    }

    /**
     * PU-077-auth: role=userでアンケート一覧URLへ直接アクセスすると403が返る
     */
    public function test_user_role_cannot_access_survey_index(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->forCompany($company)->create();

        $this->actingAs($user)->get('/company/surveys')->assertForbidden();
    }

    /**
     * PU-078-auth: 個別企業画面へ未切替のスーパーユーザーがアンケート一覧URLへ直接アクセスすると403が返る
     */
    public function test_super_user_without_switch_cannot_access_survey_index(): void
    {
        $superUser = User::factory()->superUser()->create();

        $this->actingAs($superUser)->get('/company/surveys')->assertForbidden();
    }

    /**
     * PU-079-auth: 個別企業画面へ切替済みのスーパーユーザーがアンケートを新規作成すると管理者と同様にstatus=draftで保存される
     */
    public function test_super_user_with_switch_can_create_survey(): void
    {
        $company = Company::factory()->create();
        $superUser = User::factory()->superUser()->create();
        $this->actingAs($superUser)->withSession(['acting_company_id' => $company->id]);

        $response = $this->post('/company/surveys', $this->validSurveyPayload(['title' => '代理作成アンケート']));

        $response->assertRedirect(route('company.surveys.index'));
        $this->assertDatabaseHas('surveys', [
            'company_id' => $company->id,
            'title' => '代理作成アンケート',
            'status' => 'draft',
        ]);
    }
}
