<?php

namespace Tests\Feature\Services;

use App\Models\Choice;
use App\Models\Company;
use App\Models\Question;
use App\Models\Survey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurveyServiceTest extends TestCase
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
        ], $overrides);
    }

    /**
     * @param  list<string>  $choiceBodies
     * @return array<string, mixed>
     */
    private function choiceQuestion(string $type, array $choiceBodies, array $overrides = []): array
    {
        return array_merge([
            'body' => '設問文',
            'question_type' => $type,
            'choices' => array_map(fn ($body) => ['body' => $body], $choiceBodies),
        ], $overrides);
    }

    /**
     * PU-037-evt: タイトル・回答期間・記名/匿名を入力して保存するとstatus=draftで保存され一覧に表示される
     */
    public function test_create_saves_survey_as_draft(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);

        $response = $this->post('/company/surveys', $this->validSurveyPayload(['title' => '満足度調査']));

        $response->assertRedirect(route('company.surveys.index'));
        $this->assertDatabaseHas('surveys', ['title' => '満足度調査', 'status' => 'draft']);

        $indexResponse = $this->get('/company/surveys');
        $indexResponse->assertSee('満足度調査');
    }

    /**
     * PU-038-evt: 単一選択設問と選択肢3件を追加して保存すると設問と選択肢がDBに保存され詳細画面に表示される
     */
    public function test_create_saves_single_choice_question_with_choices(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);

        $this->post('/company/surveys', $this->validSurveyPayload([
            'questions' => [$this->choiceQuestion('single_choice', ['営業部', '開発部', '総務部'])],
        ]));

        $survey = Survey::query()->latest('id')->first();
        $this->assertDatabaseHas('questions', ['survey_id' => $survey->id, 'question_type' => 'single_choice']);
        $this->assertSame(3, Question::query()->where('survey_id', $survey->id)->first()->choices()->count());

        $showResponse = $this->get("/company/surveys/{$survey->id}");
        $showResponse->assertSee('営業部');
        $showResponse->assertSee('開発部');
        $showResponse->assertSee('総務部');
    }

    /**
     * PU-039-evt: 複数選択設問と選択肢3件を追加して保存すると設問と選択肢がDBに保存され詳細画面に表示される
     */
    public function test_create_saves_multiple_choice_question_with_choices(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);

        $this->post('/company/surveys', $this->validSurveyPayload([
            'questions' => [$this->choiceQuestion('multiple_choice', ['選択肢A', '選択肢B', '選択肢C'])],
        ]));

        $survey = Survey::query()->latest('id')->first();
        $question = Question::query()->where('survey_id', $survey->id)->first();
        $this->assertSame('multiple_choice', $question->question_type->value);
        $this->assertSame(3, $question->choices()->count());

        $showResponse = $this->get("/company/surveys/{$survey->id}");
        $showResponse->assertSee('選択肢A');
        $showResponse->assertSee('選択肢B');
        $showResponse->assertSee('選択肢C');
    }

    /**
     * PU-040-evt: 自由記述設問を追加して保存すると設問がDBに保存され詳細画面に表示される
     */
    public function test_create_saves_free_text_question(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);

        $this->post('/company/surveys', $this->validSurveyPayload([
            'questions' => [['body' => 'ご意見をお聞かせください', 'question_type' => 'free_text']],
        ]));

        $survey = Survey::query()->latest('id')->first();
        $this->assertDatabaseHas('questions', [
            'survey_id' => $survey->id,
            'question_type' => 'free_text',
            'body' => 'ご意見をお聞かせください',
        ]);

        $showResponse = $this->get("/company/surveys/{$survey->id}");
        $showResponse->assertSee('ご意見をお聞かせください');
    }

    /**
     * PU-041-evt: 段階評価設問を追加して保存すると設問がDBに保存され詳細画面に表示される
     */
    public function test_create_saves_scale_question(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);

        $this->post('/company/surveys', $this->validSurveyPayload([
            'questions' => [['body' => '満足度を教えてください', 'question_type' => 'scale']],
        ]));

        $survey = Survey::query()->latest('id')->first();
        $this->assertDatabaseHas('questions', [
            'survey_id' => $survey->id,
            'question_type' => 'scale',
            'body' => '満足度を教えてください',
        ]);

        $showResponse = $this->get("/company/surveys/{$survey->id}");
        $showResponse->assertSee('満足度を教えてください');
    }

    /**
     * PU-042-other: 自由記述設問にchoices配列を付けて送信するとchoicesがDBに保存されない(0件)
     */
    public function test_free_text_question_ignores_submitted_choices(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);

        $this->post('/company/surveys', $this->validSurveyPayload([
            'questions' => [[
                'body' => '自由記述設問',
                'question_type' => 'free_text',
                'choices' => [['body' => '無視される選択肢']],
            ]],
        ]));

        $survey = Survey::query()->latest('id')->first();
        $question = Question::query()->where('survey_id', $survey->id)->first();
        $this->assertSame(0, $question->choices()->count());
    }

    /**
     * PU-043-other: 設問を3件の配列順で送信するとDBのsort_orderが配列インデックス順で保存される
     */
    public function test_questions_are_saved_with_sort_order_matching_array_index(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);

        $this->post('/company/surveys', $this->validSurveyPayload([
            'questions' => [
                ['body' => '設問1', 'question_type' => 'free_text'],
                ['body' => '設問2', 'question_type' => 'free_text'],
                ['body' => '設問3', 'question_type' => 'free_text'],
            ],
        ]));

        $survey = Survey::query()->latest('id')->first();
        $sortOrders = $survey->questions()->orderBy('sort_order')->pluck('body', 'sort_order')->all();
        $this->assertSame(['設問1', '設問2', '設問3'], [$sortOrders[0], $sortOrders[1], $sortOrders[2]]);
    }

    /**
     * PU-044-other: 選択肢を3件の配列順で送信するとDBのsort_orderが配列インデックス順で保存される
     */
    public function test_choices_are_saved_with_sort_order_matching_array_index(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);

        $this->post('/company/surveys', $this->validSurveyPayload([
            'questions' => [$this->choiceQuestion('single_choice', ['選択肢1', '選択肢2', '選択肢3'])],
        ]));

        $survey = Survey::query()->latest('id')->first();
        $question = $survey->questions()->first();
        $sortOrders = $question->choices()->orderBy('sort_order')->pluck('body', 'sort_order')->all();
        $this->assertSame(['選択肢1', '選択肢2', '選択肢3'], [$sortOrders[0], $sortOrders[1], $sortOrders[2]]);
    }

    /**
     * PU-045-other: 設問のis_requiredを指定せずに送信するとDBのis_requiredがfalse(任意)で保存される
     */
    public function test_question_is_required_defaults_to_false(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);

        $this->post('/company/surveys', $this->validSurveyPayload([
            'questions' => [['body' => '設問文', 'question_type' => 'free_text']],
        ]));

        $survey = Survey::query()->latest('id')->first();
        $this->assertFalse($survey->questions()->first()->is_required);
    }

    /**
     * PU-046-other: 設問のis_requiredをtrueにして送信するとDBのis_requiredがtrueで保存される
     */
    public function test_question_is_required_can_be_set_true(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);

        $this->post('/company/surveys', $this->validSurveyPayload([
            'questions' => [['body' => '設問文', 'question_type' => 'free_text', 'is_required' => '1']],
        ]));

        $survey = Survey::query()->latest('id')->first();
        $this->assertTrue($survey->questions()->first()->is_required);
    }

    /**
     * PU-047-evt: 既存設問2件を持つ下書きアンケートに新しい設問1件を送信して更新すると全置換される
     */
    public function test_update_replaces_all_existing_questions(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);
        $survey = Survey::factory()->create(['company_id' => $company->id]);
        $questionA = Question::factory()->create(['survey_id' => $survey->id, 'sort_order' => 0, 'body' => '旧設問A']);
        Choice::factory()->create(['question_id' => $questionA->id, 'sort_order' => 0]);
        Question::factory()->create(['survey_id' => $survey->id, 'sort_order' => 1, 'body' => '旧設問B']);

        $response = $this->put("/company/surveys/{$survey->id}", $this->validSurveyPayload([
            'questions' => [['body' => '新設問', 'question_type' => 'free_text']],
        ]));

        $response->assertRedirect(route('company.surveys.index'));
        $this->assertSame(1, $survey->questions()->count());
        $this->assertSame('新設問', $survey->questions()->first()->body);
        $this->assertDatabaseMissing('questions', ['body' => '旧設問A']);
        $this->assertDatabaseMissing('questions', ['body' => '旧設問B']);
    }

    /**
     * PU-048-other: 設問2件・選択肢を持つ下書きアンケートを削除するとquestions・choicesテーブルからも削除される
     */
    public function test_delete_cascades_to_questions_and_choices(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);
        $survey = Survey::factory()->create(['company_id' => $company->id]);
        $question = Question::factory()->singleChoice()->create(['survey_id' => $survey->id, 'sort_order' => 0]);
        $choice = Choice::factory()->create(['question_id' => $question->id, 'sort_order' => 0]);

        $response = $this->delete("/company/surveys/{$survey->id}");

        $response->assertRedirect(route('company.surveys.index'));
        $this->assertDatabaseMissing('surveys', ['id' => $survey->id]);
        $this->assertDatabaseMissing('questions', ['id' => $question->id]);
        $this->assertDatabaseMissing('choices', ['id' => $choice->id]);
    }

    /**
     * PU-049-evt: 下書きのアンケートを公開するとDBのstatusがpublishedに変わり一覧の状態表示が「公開」になる
     */
    public function test_publish_changes_status_to_published(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);
        $survey = Survey::factory()->create(['company_id' => $company->id]);

        $response = $this->post("/company/surveys/{$survey->id}/publish");

        $response->assertRedirect(route('company.surveys.index'));
        $this->assertSame('published', $survey->fresh()->status->value);

        $indexResponse = $this->get('/company/surveys');
        $indexResponse->assertSee('公開');
    }

    /**
     * PU-050-evt: 企業Aに2件・企業Bに1件のアンケートが存在する状態で一覧を取得すると企業Aの2件のみ表示される
     */
    public function test_index_is_scoped_to_own_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        Survey::factory()->create(['company_id' => $companyA->id, 'title' => '企業Aアンケート1']);
        Survey::factory()->create(['company_id' => $companyA->id, 'title' => '企業Aアンケート2']);
        Survey::factory()->create(['company_id' => $companyB->id, 'title' => '企業Bアンケート']);
        $this->actingAdminInCompany($companyA);

        $response = $this->get('/company/surveys');

        $response->assertSee('企業Aアンケート1');
        $response->assertSee('企業Aアンケート2');
        $response->assertDontSee('企業Bアンケート');
    }

    /**
     * PU-051-dsp: 企業Aに3件のアンケートを異なる時刻に作成すると直近作成したアンケートが1行目に表示される
     */
    public function test_index_orders_by_id_descending(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);
        Survey::factory()->create(['company_id' => $company->id, 'title' => '1件目']);
        Survey::factory()->create(['company_id' => $company->id, 'title' => '2件目']);
        Survey::factory()->create(['company_id' => $company->id, 'title' => '3件目']);

        $response = $this->get('/company/surveys');

        $response->assertSeeInOrder(['3件目', '2件目', '1件目']);
    }

    /**
     * PU-052-dsp: 企業Aに21件のアンケートが存在する状態で一覧の2ページ目を取得すると残り1件が表示される
     */
    public function test_index_paginates_21_surveys_into_2_pages(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);
        Survey::factory()->count(21)->create(['company_id' => $company->id]);

        $response = $this->get('/company/surveys?page=2');

        $response->assertOk();
        $response->assertViewHas('surveys', fn ($surveys) => $surveys->count() === 1);
    }

    /**
     * PU-053-dsp: 単一選択設問・選択肢3件を持つ下書きアンケートの詳細を取得すると保存済み内容どおり表示される
     */
    public function test_show_displays_saved_question_composition(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);
        $survey = Survey::factory()->create(['company_id' => $company->id]);
        $question = Question::factory()->singleChoice()->create([
            'survey_id' => $survey->id,
            'body' => '好きな色は？',
            'is_required' => true,
            'sort_order' => 0,
        ]);
        Choice::factory()->create(['question_id' => $question->id, 'body' => '赤', 'sort_order' => 0]);
        Choice::factory()->create(['question_id' => $question->id, 'body' => '青', 'sort_order' => 1]);
        Choice::factory()->create(['question_id' => $question->id, 'body' => '緑', 'sort_order' => 2]);

        $response = $this->get("/company/surveys/{$survey->id}");

        $response->assertSee('好きな色は？');
        $response->assertSee('単一選択');
        $response->assertSee('必須');
        $response->assertSee('赤');
        $response->assertSee('青');
        $response->assertSee('緑');
    }

    /**
     * PU-054-dsp: 段階評価設問(両端ラベル未設定)を持つアンケートの詳細を取得するとラベルが表示されず数値1〜5のみ表示される
     */
    public function test_show_scale_question_without_labels_shows_only_numbers(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);
        $survey = Survey::factory()->create(['company_id' => $company->id]);
        Question::factory()->scale()->create([
            'survey_id' => $survey->id,
            'sort_order' => 0,
            'scale_min_label' => null,
            'scale_max_label' => null,
        ]);

        $response = $this->get("/company/surveys/{$survey->id}");

        $response->assertSee('1〜5');
        $response->assertDontSee('data-testid="survey-scale-min-label"', false);
        $response->assertDontSee('data-testid="survey-scale-max-label"', false);
    }

    /**
     * PU-055-dsp: 段階評価設問(1側「全くそう思わない」・5側「とてもそう思う」)の詳細を取得すると入力したラベルが表示される
     */
    public function test_show_scale_question_with_labels_displays_labels(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);
        $survey = Survey::factory()->create(['company_id' => $company->id]);
        Question::factory()->scale()->create([
            'survey_id' => $survey->id,
            'sort_order' => 0,
            'scale_min_label' => '全くそう思わない',
            'scale_max_label' => 'とてもそう思う',
        ]);

        $response = $this->get("/company/surveys/{$survey->id}");

        $response->assertSee('全くそう思わない');
        $response->assertSee('とてもそう思う');
    }

    /**
     * PU-056-dsp: スーパーユーザーが個別企業画面切替で代理作成したアンケートの一覧・詳細に作成者を特定できる情報が含まれない
     */
    public function test_index_and_show_do_not_expose_creator_information(): void
    {
        $company = Company::factory()->create();
        $superUser = User::factory()->superUser()->create(['name' => 'スーパー太郎', 'email' => 'super-taro@example.com']);
        $this->actingAs($superUser)->withSession(['acting_company_id' => $company->id]);

        $this->post('/company/surveys', $this->validSurveyPayload(['title' => '代理作成アンケート']));
        $survey = Survey::query()->latest('id')->first();

        $indexResponse = $this->get('/company/surveys');
        $indexResponse->assertDontSee('スーパー太郎');
        $indexResponse->assertDontSee('super-taro@example.com');

        $showResponse = $this->get("/company/surveys/{$survey->id}");
        $showResponse->assertDontSee('スーパー太郎');
        $showResponse->assertDontSee('super-taro@example.com');
    }

    /**
     * PU-057-dsp: アンケートの一覧・詳細を取得すると回答数・回答率がレスポンスに含まれない
     */
    public function test_index_and_show_do_not_expose_answer_counts(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);
        $survey = Survey::factory()->create(['company_id' => $company->id, 'title' => '集計非表示確認アンケート']);

        $indexResponse = $this->get('/company/surveys');
        $indexResponse->assertDontSee('回答数');
        $indexResponse->assertDontSee('回答率');

        $showResponse = $this->get("/company/surveys/{$survey->id}");
        $showResponse->assertDontSee('回答数');
        $showResponse->assertDontSee('回答率');
    }

    /**
     * PU-058-evt: 既に存在するタイトルと同じタイトルで新しいアンケートを作成すると登録が成功し一覧に2件とも表示される
     */
    public function test_duplicate_title_is_allowed(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);
        Survey::factory()->create(['company_id' => $company->id, 'title' => '2026年度 従業員満足度調査']);

        $response = $this->post('/company/surveys', $this->validSurveyPayload(['title' => '2026年度 従業員満足度調査']));

        $response->assertRedirect(route('company.surveys.index'));
        $this->assertSame(2, Survey::query()->where('title', '2026年度 従業員満足度調査')->count());
    }

    /**
     * PU-059-dsp: 下書きのアンケートAと公開済みのアンケートBの詳細を取得するとAには編集/削除/公開ボタンが表示されBには表示されない
     */
    public function test_show_displays_edit_delete_publish_links_only_for_draft(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);
        $draftSurvey = Survey::factory()->create(['company_id' => $company->id]);
        $publishedSurvey = Survey::factory()->published()->create(['company_id' => $company->id]);

        $draftResponse = $this->get("/company/surveys/{$draftSurvey->id}");
        $draftResponse->assertSee('data-testid="survey-edit-link"', false);
        $draftResponse->assertSee('data-testid="survey-delete-button"', false);
        $draftResponse->assertSee('data-testid="survey-publish-button"', false);

        $publishedResponse = $this->get("/company/surveys/{$publishedSurvey->id}");
        $publishedResponse->assertDontSee('data-testid="survey-edit-link"', false);
        $publishedResponse->assertDontSee('data-testid="survey-delete-button"', false);
        $publishedResponse->assertDontSee('data-testid="survey-publish-button"', false);
    }
}
