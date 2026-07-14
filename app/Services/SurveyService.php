<?php

namespace App\Services;

use App\Enums\SurveyStatus;
use App\Models\Company;
use App\Models\Question;
use App\Models\Survey;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SurveyService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Company $company, User $creator, array $data): Survey
    {
        return DB::transaction(function () use ($company, $creator, $data) {
            $survey = Survey::query()->create([
                'company_id' => $company->id,
                'created_by' => $creator->id,
                'title' => $data['title'],
                'answer_start_date' => $data['answer_start_date'],
                'answer_end_date' => $data['answer_end_date'],
                'answer_visibility' => $data['answer_visibility'],
                'status' => SurveyStatus::Draft,
            ]);

            $this->syncQuestions($survey, $data['questions'] ?? []);

            return $survey;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Survey $survey, array $data): Survey
    {
        return DB::transaction(function () use ($survey, $data) {
            $survey->update([
                'title' => $data['title'],
                'answer_start_date' => $data['answer_start_date'],
                'answer_end_date' => $data['answer_end_date'],
                'answer_visibility' => $data['answer_visibility'],
            ]);

            $survey->questions()->delete();

            $this->syncQuestions($survey, $data['questions'] ?? []);

            return $survey;
        });
    }

    public function delete(Survey $survey): void
    {
        $survey->delete();
    }

    public function publish(Survey $survey): void
    {
        $survey->update(['status' => SurveyStatus::Published]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $questionsData
     */
    private function syncQuestions(Survey $survey, array $questionsData): void
    {
        foreach (array_values($questionsData) as $index => $questionData) {
            $question = Question::query()->create([
                'survey_id' => $survey->id,
                'question_type' => $questionData['question_type'],
                'body' => $questionData['body'],
                'is_required' => (bool) ($questionData['is_required'] ?? false),
                'sort_order' => $index,
                'scale_min_label' => ($questionData['scale_min_label'] ?? null) ?: null,
                'scale_max_label' => ($questionData['scale_max_label'] ?? null) ?: null,
            ]);

            if (! $question->hasChoices()) {
                continue;
            }

            foreach (array_values($questionData['choices'] ?? []) as $choiceIndex => $choiceData) {
                $question->choices()->create([
                    'body' => $choiceData['body'],
                    'sort_order' => $choiceIndex,
                ]);
            }
        }
    }
}
