<?php

namespace Tests\Unit\Models;

use App\Enums\AnswerVisibility;
use App\Enums\QuestionType;
use App\Enums\SurveyStatus;
use App\Models\Company;
use App\Models\Question;
use App\Models\Survey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurveyModelEnumTest extends TestCase
{
    use RefreshDatabase;

    /**
     * PU-060-other: SurveyStatus::label()がDraft・Publishedそれぞれ「下書き」「公開」を返す
     */
    public function test_survey_status_label(): void
    {
        $this->assertSame('下書き', SurveyStatus::Draft->label());
        $this->assertSame('公開', SurveyStatus::Published->label());
    }

    /**
     * PU-061-other: AnswerVisibility::label()がNamed・Anonymousそれぞれ「記名」「匿名」を返す
     */
    public function test_answer_visibility_label(): void
    {
        $this->assertSame('記名', AnswerVisibility::Named->label());
        $this->assertSame('匿名', AnswerVisibility::Anonymous->label());
    }

    /**
     * PU-062-other: QuestionType::label()が4種類それぞれ日本語ラベルを返す
     */
    public function test_question_type_label(): void
    {
        $this->assertSame('単一選択', QuestionType::SingleChoice->label());
        $this->assertSame('複数選択', QuestionType::MultipleChoice->label());
        $this->assertSame('自由記述', QuestionType::FreeText->label());
        $this->assertSame('段階評価', QuestionType::Scale->label());
    }

    /**
     * PU-063-other: Question::hasChoices()が単一選択・複数選択でtrue、自由記述・段階評価でfalseを返す
     */
    public function test_question_has_choices(): void
    {
        $survey = Survey::factory()->create();
        $singleChoice = Question::factory()->singleChoice()->create(['survey_id' => $survey->id, 'sort_order' => 0]);
        $multipleChoice = Question::factory()->multipleChoice()->create(['survey_id' => $survey->id, 'sort_order' => 1]);
        $freeText = Question::factory()->create(['survey_id' => $survey->id, 'sort_order' => 2]);
        $scale = Question::factory()->scale()->create(['survey_id' => $survey->id, 'sort_order' => 3]);

        $this->assertTrue($singleChoice->hasChoices());
        $this->assertTrue($multipleChoice->hasChoices());
        $this->assertFalse($freeText->hasChoices());
        $this->assertFalse($scale->hasChoices());
    }

    /**
     * PU-064-other: Question::ANSWER_MAX_LENGTH定数が2000を返す
     */
    public function test_question_answer_max_length_constant(): void
    {
        $this->assertSame(2000, Question::ANSWER_MAX_LENGTH);
    }

    /**
     * PU-065-other: Company::surveys()が企業Aの2件のSurveyコレクションを返す
     */
    public function test_company_surveys_relation(): void
    {
        $company = Company::factory()->create();
        Survey::factory()->count(2)->create(['company_id' => $company->id]);

        $this->assertSame(2, $company->surveys()->count());
    }
}
