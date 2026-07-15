<?php

namespace App\Http\Controllers\User;

use App\Enums\SurveyStatus;
use App\Http\Controllers\Controller;
use App\Models\Survey;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * 回答機能(survey-answer)は未実装のため、配布アンケート一覧は状態を「未回答」または
     * 「期限切れ」(回答終了日で判定・実データ)のみで表示する。下書き保存済み/回答済みの
     * 状態は回答機能の実装後に追加する。
     */
    public function index(): View
    {
        $user = auth()->user();
        $today = Carbon::today();

        $surveys = Survey::query()
            ->where('company_id', $user->company_id)
            ->where('status', SurveyStatus::Published)
            ->withCount('questions')
            ->orderByDesc('answer_start_date')
            ->get()
            ->map(function (Survey $survey) use ($today) {
                $isExpired = $today->gt($survey->answer_end_date);

                return [
                    'survey' => $survey,
                    'isExpired' => $isExpired,
                    'remainingDays' => $isExpired ? 0 : (int) $today->diffInDays($survey->answer_end_date),
                ];
            });

        return view('user.home', ['surveyItems' => $surveys]);
    }
}
