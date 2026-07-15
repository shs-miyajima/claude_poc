<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Survey;
use Illuminate\View\View;

class SurveyController extends Controller
{
    /**
     * 回答機能(survey-answer)は未実装のため、本画面は回答ウィザードUIのモック表示のみ。
     * 下書き保存・送信はクライアント側のトースト表示のみで、回答の永続化は行わない。
     */
    public function answer(Survey $survey): View
    {
        $user = auth()->user();

        if ($survey->company_id !== $user->company_id || ! $survey->isPublished()) {
            abort(403);
        }

        $survey->load('questions.choices');

        return view('user.surveys.answer', ['survey' => $survey]);
    }
}
