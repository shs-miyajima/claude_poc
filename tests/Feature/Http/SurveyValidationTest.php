<?php

namespace Tests\Feature\Http;

use App\Models\Choice;
use App\Models\Company;
use App\Models\Question;
use App\Models\Survey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurveyValidationTest extends TestCase
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
     * @return array<string, mixed>
     */
    private function freeTextQuestion(array $overrides = []): array
    {
        return array_merge([
            'body' => '設問文',
            'question_type' => 'free_text',
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
     * @return array<string, mixed>
     */
    private function scaleQuestion(array $overrides = []): array
    {
        return array_merge([
            'body' => '設問文',
            'question_type' => 'scale',
        ], $overrides);
    }

    /**
     * PU-001-inp: タイトルを空欄のまま送信すると422で必須メッセージが返る
     */
    public function test_store_requires_title(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);

        $response = $this->postJson('/company/surveys', $this->validSurveyPayload(['title' => '']));

        $response->assertStatus(422);
        $response->assertJsonFragment(['title' => ['タイトルを入力してください']]);
    }

    /**
     * PU-002-inp: タイトルに501文字を入力すると422で最大長エラーが返る
     */
    public function test_store_rejects_title_over_500_chars(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);

        $response = $this->postJson('/company/surveys', $this->validSurveyPayload(['title' => str_repeat('あ', 501)]));

        $response->assertStatus(422);
        $response->assertJsonFragment(['title' => ['タイトルは500文字以内で入力してください']]);
    }

    /**
     * PU-003-inp: タイトルに500文字を入力すると登録が成功する(境界OK)
     */
    public function test_store_accepts_title_exactly_500_chars(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);

        $response = $this->post('/company/surveys', $this->validSurveyPayload(['title' => str_repeat('あ', 500)]));

        $response->assertRedirect(route('company.surveys.index'));
        $this->assertDatabaseHas('surveys', ['title' => str_repeat('あ', 500), 'status' => 'draft']);
    }

    /**
     * PU-004-inp: 回答期間の開始日を空欄のまま送信すると422が返る
     */
    public function test_store_requires_answer_start_date(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);

        $response = $this->postJson('/company/surveys', $this->validSurveyPayload(['answer_start_date' => '']));

        $response->assertStatus(422);
        $response->assertJsonFragment(['answer_start_date' => ['回答期間（開始日・終了日）を入力してください']]);
    }

    /**
     * PU-005-inp: 回答期間の終了日を空欄のまま送信すると422が返る
     */
    public function test_store_requires_answer_end_date(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);

        $response = $this->postJson('/company/surveys', $this->validSurveyPayload(['answer_end_date' => '']));

        $response->assertStatus(422);
        $response->assertJsonFragment(['answer_end_date' => ['回答期間（開始日・終了日）を入力してください']]);
    }

    /**
     * PU-006-inp: 終了日に開始日より前の日付を入力すると422が返る
     */
    public function test_store_rejects_end_date_before_start_date(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);

        $response = $this->postJson('/company/surveys', $this->validSurveyPayload([
            'answer_start_date' => '2026-08-01',
            'answer_end_date' => '2026-07-31',
        ]));

        $response->assertStatus(422);
        $response->assertJsonFragment(['answer_end_date' => ['回答終了日は開始日以降の日付を入力してください']]);
    }

    /**
     * PU-007-inp: 開始日と終了日に同一日付を入力すると登録が成功する(境界OK・同日)
     */
    public function test_store_accepts_same_start_and_end_date(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);

        $response = $this->post('/company/surveys', $this->validSurveyPayload([
            'answer_start_date' => '2026-08-01',
            'answer_end_date' => '2026-08-01',
        ]));

        $response->assertRedirect(route('company.surveys.index'));
    }

    /**
     * PU-008-inp: 記名/匿名を未選択のまま送信すると422が返る
     */
    public function test_store_requires_answer_visibility(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);

        $response = $this->postJson('/company/surveys', $this->validSurveyPayload(['answer_visibility' => '']));

        $response->assertStatus(422);
        $response->assertJsonFragment(['answer_visibility' => ['記名または匿名を選択してください']]);
    }

    /**
     * PU-009-inp: 設問文を空欄のまま送信すると422が返る
     */
    public function test_store_requires_question_body(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);

        $response = $this->postJson('/company/surveys', $this->validSurveyPayload([
            'questions' => [$this->freeTextQuestion(['body' => ''])],
        ]));

        $response->assertStatus(422);
        $response->assertJsonFragment(['questions.0.body' => ['設問文を入力してください']]);
    }

    /**
     * PU-010-inp: 設問文に501文字を入力すると422が返る
     */
    public function test_store_rejects_question_body_over_500_chars(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);

        $response = $this->postJson('/company/surveys', $this->validSurveyPayload([
            'questions' => [$this->freeTextQuestion(['body' => str_repeat('あ', 501)])],
        ]));

        $response->assertStatus(422);
        $response->assertJsonFragment(['questions.0.body' => ['設問文は500文字以内で入力してください']]);
    }

    /**
     * PU-011-inp: 設問文に500文字を入力すると登録が成功する(境界OK)
     */
    public function test_store_accepts_question_body_exactly_500_chars(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);

        $response = $this->post('/company/surveys', $this->validSurveyPayload([
            'questions' => [$this->freeTextQuestion(['body' => str_repeat('あ', 500)])],
        ]));

        $response->assertRedirect(route('company.surveys.index'));
    }

    /**
     * PU-012-inp: 設問を101件送信すると422が返る
     */
    public function test_store_rejects_101_questions(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);

        $questions = array_map(fn ($i) => $this->freeTextQuestion(['body' => "設問{$i}"]), range(1, 101));

        $response = $this->postJson('/company/surveys', $this->validSurveyPayload(['questions' => $questions]));

        $response->assertStatus(422);
        $response->assertJsonFragment(['questions' => ['設問は100件まで登録できます']]);
    }

    /**
     * PU-013-inp: 設問を100件送信すると登録が成功する(境界OK)
     */
    public function test_store_accepts_100_questions(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);

        $questions = array_map(fn ($i) => $this->freeTextQuestion(['body' => "設問{$i}"]), range(1, 100));

        $response = $this->post('/company/surveys', $this->validSurveyPayload(['questions' => $questions]));

        $response->assertRedirect(route('company.surveys.index'));
    }

    /**
     * PU-014-inp: 単一選択設問に選択肢を11件送信すると422が返る
     */
    public function test_store_rejects_single_choice_with_11_choices(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);

        $choices = array_map(fn ($i) => "選択肢{$i}", range(1, 11));

        $response = $this->postJson('/company/surveys', $this->validSurveyPayload([
            'questions' => [$this->choiceQuestion('single_choice', $choices)],
        ]));

        $response->assertStatus(422);
        $response->assertJsonFragment(['questions.0.choices' => ['選択肢は10件まで登録できます']]);
    }

    /**
     * PU-015-inp: 複数選択設問に選択肢を11件送信すると422が返る
     */
    public function test_store_rejects_multiple_choice_with_11_choices(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);

        $choices = array_map(fn ($i) => "選択肢{$i}", range(1, 11));

        $response = $this->postJson('/company/surveys', $this->validSurveyPayload([
            'questions' => [$this->choiceQuestion('multiple_choice', $choices)],
        ]));

        $response->assertStatus(422);
        $response->assertJsonFragment(['questions.0.choices' => ['選択肢は10件まで登録できます']]);
    }

    /**
     * PU-016-inp: 単一選択設問に選択肢を10件送信すると登録が成功する(境界OK)
     */
    public function test_store_accepts_single_choice_with_10_choices(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);

        $choices = array_map(fn ($i) => "選択肢{$i}", range(1, 10));

        $response = $this->post('/company/surveys', $this->validSurveyPayload([
            'questions' => [$this->choiceQuestion('single_choice', $choices)],
        ]));

        $response->assertRedirect(route('company.surveys.index'));
    }

    /**
     * PU-017-inp: 単一選択設問に選択肢を1件のみ送信すると422が返る
     */
    public function test_store_rejects_single_choice_with_1_choice(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);

        $response = $this->postJson('/company/surveys', $this->validSurveyPayload([
            'questions' => [$this->choiceQuestion('single_choice', ['選択肢1'])],
        ]));

        $response->assertStatus(422);
        $response->assertJsonFragment(['questions.0.choices' => ['選択肢は2件以上登録してください']]);
    }

    /**
     * PU-018-inp: 複数選択設問に選択肢を1件のみ送信すると422が返る
     */
    public function test_store_rejects_multiple_choice_with_1_choice(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);

        $response = $this->postJson('/company/surveys', $this->validSurveyPayload([
            'questions' => [$this->choiceQuestion('multiple_choice', ['選択肢1'])],
        ]));

        $response->assertStatus(422);
        $response->assertJsonFragment(['questions.0.choices' => ['選択肢は2件以上登録してください']]);
    }

    /**
     * PU-019-inp: 単一選択設問に選択肢を2件送信すると登録が成功する(境界OK)
     */
    public function test_store_accepts_single_choice_with_2_choices(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);

        $response = $this->post('/company/surveys', $this->validSurveyPayload([
            'questions' => [$this->choiceQuestion('single_choice', ['選択肢1', '選択肢2'])],
        ]));

        $response->assertRedirect(route('company.surveys.index'));
    }

    /**
     * PU-020-inp: 選択肢文言に501文字を入力すると422が返る
     */
    public function test_store_rejects_choice_body_over_500_chars(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);

        $response = $this->postJson('/company/surveys', $this->validSurveyPayload([
            'questions' => [$this->choiceQuestion('single_choice', [str_repeat('あ', 501), '選択肢2'])],
        ]));

        $response->assertStatus(422);
        $response->assertJsonFragment(['questions.0.choices.0.body' => ['選択肢は500文字以内で入力してください']]);
    }

    /**
     * PU-021-inp: 選択肢文言に500文字を入力すると登録が成功する(境界OK)
     */
    public function test_store_accepts_choice_body_exactly_500_chars(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);

        $response = $this->post('/company/surveys', $this->validSurveyPayload([
            'questions' => [$this->choiceQuestion('single_choice', [str_repeat('あ', 500), '選択肢2'])],
        ]));

        $response->assertRedirect(route('company.surveys.index'));
    }

    /**
     * PU-022-inp: 段階評価設問の1側ラベルに501文字を入力すると422が返る
     */
    public function test_store_rejects_scale_min_label_over_500_chars(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);

        $response = $this->postJson('/company/surveys', $this->validSurveyPayload([
            'questions' => [$this->scaleQuestion(['scale_min_label' => str_repeat('あ', 501)])],
        ]));

        $response->assertStatus(422);
        $response->assertJsonFragment(['questions.0.scale_min_label' => ['ラベルは500文字以内で入力してください']]);
    }

    /**
     * PU-023-inp: 段階評価設問の5側ラベルに501文字を入力すると422が返る
     */
    public function test_store_rejects_scale_max_label_over_500_chars(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);

        $response = $this->postJson('/company/surveys', $this->validSurveyPayload([
            'questions' => [$this->scaleQuestion(['scale_max_label' => str_repeat('あ', 501)])],
        ]));

        $response->assertStatus(422);
        $response->assertJsonFragment(['questions.0.scale_max_label' => ['ラベルは500文字以内で入力してください']]);
    }

    /**
     * PU-024-inp: 段階評価設問の両端ラベルに500文字を入力すると登録が成功する(境界OK)
     */
    public function test_store_accepts_scale_labels_exactly_500_chars(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);

        $response = $this->post('/company/surveys', $this->validSurveyPayload([
            'questions' => [$this->scaleQuestion([
                'scale_min_label' => str_repeat('あ', 500),
                'scale_max_label' => str_repeat('い', 500),
            ])],
        ]));

        $response->assertRedirect(route('company.surveys.index'));
    }

    /**
     * PU-025-evt: 段階評価設問の両端ラベルを未入力のまま送信すると登録が成功しDBがnullで保存される
     */
    public function test_store_scale_question_without_labels_saves_null(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);

        $response = $this->post('/company/surveys', $this->validSurveyPayload([
            'questions' => [$this->scaleQuestion()],
        ]));

        $response->assertRedirect(route('company.surveys.index'));
        $this->assertDatabaseHas('questions', [
            'question_type' => 'scale',
            'scale_min_label' => null,
            'scale_max_label' => null,
        ]);
    }

    /**
     * PU-026-inp: 設問形式を未選択のまま送信すると422が返る
     */
    public function test_store_requires_question_type(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);

        $response = $this->postJson('/company/surveys', $this->validSurveyPayload([
            'questions' => [$this->freeTextQuestion(['question_type' => ''])],
        ]));

        $response->assertStatus(422);
        $response->assertJsonFragment(['questions.0.question_type' => ['設問形式を選択してください']]);
    }

    /**
     * PU-027-inp: 単一選択設問の選択肢の1件を空欄のまま送信すると422が返る
     */
    public function test_store_rejects_single_choice_with_empty_choice_body(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);

        $response = $this->postJson('/company/surveys', $this->validSurveyPayload([
            'questions' => [$this->choiceQuestion('single_choice', ['選択肢1', ''])],
        ]));

        $response->assertStatus(422);
        $response->assertJsonFragment(['questions.0.choices.1.body' => ['選択肢を入力してください']]);
    }

    /**
     * PU-028-inp: 複数選択設問の選択肢の1件を空欄のまま送信すると422が返る
     */
    public function test_store_rejects_multiple_choice_with_empty_choice_body(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);

        $response = $this->postJson('/company/surveys', $this->validSurveyPayload([
            'questions' => [$this->choiceQuestion('multiple_choice', ['選択肢1', ''])],
        ]));

        $response->assertStatus(422);
        $response->assertJsonFragment(['questions.0.choices.1.body' => ['選択肢を入力してください']]);
    }

    /**
     * PU-084-inp: 複数選択設問に選択肢を10件送信すると登録が成功する(境界OK・複数選択)
     */
    public function test_store_accepts_multiple_choice_with_10_choices(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);

        $choices = array_map(fn ($i) => "選択肢{$i}", range(1, 10));

        $response = $this->post('/company/surveys', $this->validSurveyPayload([
            'questions' => [$this->choiceQuestion('multiple_choice', $choices)],
        ]));

        $response->assertRedirect(route('company.surveys.index'));
    }

    /**
     * PU-085-inp: 複数選択設問に選択肢を2件送信すると登録が成功する(境界OK・複数選択)
     */
    public function test_store_accepts_multiple_choice_with_2_choices(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);

        $response = $this->post('/company/surveys', $this->validSurveyPayload([
            'questions' => [$this->choiceQuestion('multiple_choice', ['選択肢1', '選択肢2'])],
        ]));

        $response->assertRedirect(route('company.surveys.index'));
    }

    /**
     * @return array{survey: Survey, question: Question, choice: Choice}
     */
    private function createDraftSurveyWithSingleChoiceQuestion(Company $company): array
    {
        $survey = Survey::factory()->create([
            'company_id' => $company->id,
            'title' => '維持されるタイトル',
            'answer_start_date' => '2026-08-01',
            'answer_end_date' => '2026-08-31',
            'answer_visibility' => 'named',
        ]);
        $question = Question::factory()->singleChoice()->create([
            'survey_id' => $survey->id,
            'body' => '維持される設問文',
            'sort_order' => 0,
        ]);
        $choice = Choice::factory()->create([
            'question_id' => $question->id,
            'body' => '維持される選択肢',
            'sort_order' => 0,
        ]);
        Choice::factory()->create([
            'question_id' => $question->id,
            'body' => '維持される選択肢2',
            'sort_order' => 1,
        ]);

        return ['survey' => $survey, 'question' => $question, 'choice' => $choice];
    }

    /**
     * PU-029-inp: 更新時にタイトルを空欄にして送信すると422が返りDBのタイトルが維持される
     */
    public function test_update_requires_title_and_keeps_original_value(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);
        $fixture = $this->createDraftSurveyWithSingleChoiceQuestion($company);

        $response = $this->putJson("/company/surveys/{$fixture['survey']->id}", $this->validSurveyPayload([
            'title' => '',
            'questions' => [$this->choiceQuestion('single_choice', ['選択肢1', '選択肢2'])],
        ]));

        $response->assertStatus(422);
        $response->assertJsonFragment(['title' => ['タイトルを入力してください']]);
        $this->assertSame('維持されるタイトル', $fixture['survey']->fresh()->title);
    }

    /**
     * PU-030-inp: 更新時に開始日を空欄にして送信すると422が返りDBの開始日が維持される
     */
    public function test_update_requires_answer_start_date_and_keeps_original_value(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);
        $fixture = $this->createDraftSurveyWithSingleChoiceQuestion($company);

        $response = $this->putJson("/company/surveys/{$fixture['survey']->id}", $this->validSurveyPayload([
            'answer_start_date' => '',
            'questions' => [$this->choiceQuestion('single_choice', ['選択肢1', '選択肢2'])],
        ]));

        $response->assertStatus(422);
        $response->assertJsonFragment(['answer_start_date' => ['回答期間（開始日・終了日）を入力してください']]);
        $this->assertSame('2026-08-01', $fixture['survey']->fresh()->answer_start_date->format('Y-m-d'));
    }

    /**
     * PU-031-inp: 更新時に終了日を空欄にして送信すると422が返りDBの終了日が維持される
     */
    public function test_update_requires_answer_end_date_and_keeps_original_value(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);
        $fixture = $this->createDraftSurveyWithSingleChoiceQuestion($company);

        $response = $this->putJson("/company/surveys/{$fixture['survey']->id}", $this->validSurveyPayload([
            'answer_end_date' => '',
            'questions' => [$this->choiceQuestion('single_choice', ['選択肢1', '選択肢2'])],
        ]));

        $response->assertStatus(422);
        $response->assertJsonFragment(['answer_end_date' => ['回答期間（開始日・終了日）を入力してください']]);
        $this->assertSame('2026-08-31', $fixture['survey']->fresh()->answer_end_date->format('Y-m-d'));
    }

    /**
     * PU-032-inp: 更新時に記名/匿名を未選択にして送信すると422が返りDBの記名/匿名が維持される
     */
    public function test_update_requires_answer_visibility_and_keeps_original_value(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);
        $fixture = $this->createDraftSurveyWithSingleChoiceQuestion($company);

        $response = $this->putJson("/company/surveys/{$fixture['survey']->id}", $this->validSurveyPayload([
            'answer_visibility' => '',
            'questions' => [$this->choiceQuestion('single_choice', ['選択肢1', '選択肢2'])],
        ]));

        $response->assertStatus(422);
        $response->assertJsonFragment(['answer_visibility' => ['記名または匿名を選択してください']]);
        $this->assertSame('named', $fixture['survey']->fresh()->answer_visibility->value);
    }

    /**
     * PU-033-inp: 更新時に設問文を空欄にして送信すると422が返りDBの設問構成が維持される
     */
    public function test_update_requires_question_body_and_keeps_original_value(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);
        $fixture = $this->createDraftSurveyWithSingleChoiceQuestion($company);

        $response = $this->putJson("/company/surveys/{$fixture['survey']->id}", $this->validSurveyPayload([
            'questions' => [$this->choiceQuestion('single_choice', ['選択肢1', '選択肢2'], ['body' => ''])],
        ]));

        $response->assertStatus(422);
        $response->assertJsonFragment(['questions.0.body' => ['設問文を入力してください']]);
        $this->assertSame(1, $fixture['survey']->fresh()->questions()->count());
        $this->assertSame('維持される設問文', $fixture['survey']->fresh()->questions()->first()->body);
    }

    /**
     * PU-034-inp: 更新時に設問形式を未選択にして送信すると422が返りDBの設問構成が維持される
     */
    public function test_update_requires_question_type_and_keeps_original_value(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);
        $fixture = $this->createDraftSurveyWithSingleChoiceQuestion($company);

        $response = $this->putJson("/company/surveys/{$fixture['survey']->id}", $this->validSurveyPayload([
            'questions' => [$this->choiceQuestion('single_choice', ['選択肢1', '選択肢2'], ['question_type' => ''])],
        ]));

        $response->assertStatus(422);
        $response->assertJsonFragment(['questions.0.question_type' => ['設問形式を選択してください']]);
        $this->assertSame('single_choice', $fixture['survey']->fresh()->questions()->first()->question_type->value);
    }

    /**
     * PU-035-inp: 更新時に単一選択設問の選択肢の1件を空欄にして送信すると422が返りDBの選択肢が維持される
     */
    public function test_update_requires_choice_body_and_keeps_original_value(): void
    {
        $company = Company::factory()->create();
        $this->actingAdminInCompany($company);
        $fixture = $this->createDraftSurveyWithSingleChoiceQuestion($company);

        $response = $this->putJson("/company/surveys/{$fixture['survey']->id}", $this->validSurveyPayload([
            'questions' => [$this->choiceQuestion('single_choice', ['選択肢1', ''])],
        ]));

        $response->assertStatus(422);
        $response->assertJsonFragment(['questions.0.choices.1.body' => ['選択肢を入力してください']]);
        $this->assertSame(2, $fixture['question']->fresh()->choices()->count());
        $this->assertSame('維持される選択肢', $fixture['question']->fresh()->choices()->first()->body);
    }

    /**
     * PU-036-evt: 更新時に段階評価の両端ラベルを空欄にして送信すると更新が成功しDBがnullで上書きされる
     */
    public function test_update_scale_labels_are_overwritten_with_null_when_blank(): void
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

        $response = $this->put("/company/surveys/{$survey->id}", $this->validSurveyPayload([
            'questions' => [$this->scaleQuestion(['scale_min_label' => '', 'scale_max_label' => ''])],
        ]));

        $response->assertRedirect(route('company.surveys.index'));
        $this->assertDatabaseHas('questions', [
            'survey_id' => $survey->id,
            'scale_min_label' => null,
            'scale_max_label' => null,
        ]);
    }
}
