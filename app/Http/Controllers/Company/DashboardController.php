<?php

namespace App\Http\Controllers\Company;

use App\Enums\QuestionType;
use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\Survey;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * 回答・集計機能(survey-answer/survey-dashboard)は未実装のため、対象者数・設問数以外は
     * サーベイIDから決定的に導出したモック値を表示する。実データ実装後に置き換える。
     */
    public function index(Request $request): View
    {
        $company = app('currentCompany');

        $surveys = Survey::query()->where('company_id', $company->id)->orderByDesc('id')->get();

        $selectedSurveyId = $request->integer('survey_id') ?: (int) optional($surveys->first())->id;
        $survey = $surveys->firstWhere('id', $selectedSurveyId);
        $survey?->load('questions.choices');

        $targetUserCount = $company->users()->active()->count();

        return view('company.dashboard', [
            'surveys' => $surveys,
            'survey' => $survey,
            'targetUserCount' => $targetUserCount,
            'mock' => $survey ? $this->buildMockStats($survey, $targetUserCount) : null,
        ]);
    }

    private function buildMockStats(Survey $survey, int $targetUserCount): array
    {
        $responseRate = 40 + ($survey->id * 17) % 56;
        $respondentCount = $targetUserCount > 0 ? (int) round($targetUserCount * $responseRate / 100) : 0;

        $questionStats = $survey->questions->map(fn (Question $question) => [
            'question' => $question,
            'choiceStats' => $question->hasChoices() ? $this->distributeChoices($question->choices, $respondentCount) : null,
            'scaleStats' => $question->question_type === QuestionType::Scale ? $this->distributeScale($respondentCount) : null,
            'samples' => $question->question_type === QuestionType::FreeText ? [
                '（モック回答例）業務内容にはおおむね満足しています。',
                '（モック回答例）もう少し裁量が欲しいと感じます。',
            ] : null,
        ]);

        return [
            'responseRate' => $responseRate,
            'respondentCount' => $respondentCount,
            'questionStats' => $questionStats,
        ];
    }

    private function distributeChoices(Collection $choices, int $respondentCount): array
    {
        $count = $choices->count();
        if ($count === 0 || $respondentCount === 0) {
            return $choices->map(fn ($choice) => ['choice' => $choice, 'count' => 0, 'percent' => 0])->all();
        }

        $weights = $choices->values()->map(fn ($choice, $index) => $count - $index);
        $totalWeight = $weights->sum();

        return $choices->values()->map(function ($choice, $index) use ($weights, $totalWeight, $respondentCount) {
            $choiceCount = (int) round($respondentCount * $weights[$index] / $totalWeight);
            $percent = $respondentCount > 0 ? (int) round($choiceCount / $respondentCount * 100) : 0;

            return ['choice' => $choice, 'count' => $choiceCount, 'percent' => $percent];
        })->all();
    }

    private function distributeScale(int $respondentCount): array
    {
        $weights = [1, 2, 3, 3, 2];
        $totalWeight = array_sum($weights);
        $buckets = [];
        $weightedSum = 0;
        $totalCount = 0;

        foreach ($weights as $index => $weight) {
            $value = $index + 1;
            $bucketCount = (int) round($respondentCount * $weight / $totalWeight);
            $buckets[] = ['value' => $value, 'count' => $bucketCount];
            $weightedSum += $value * $bucketCount;
            $totalCount += $bucketCount;
        }

        return [
            'buckets' => $buckets,
            'average' => $totalCount > 0 ? round($weightedSum / $totalCount, 1) : 0,
            'total' => $totalCount,
        ];
    }
}
