<?php

namespace App\Http\Requests\Concerns;

use App\Enums\AnswerVisibility;
use App\Enums\QuestionType;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

trait SurveyRequestRules
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:500'],
            'answer_start_date' => ['required', 'date_format:Y-m-d'],
            'answer_end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:answer_start_date'],
            'answer_visibility' => ['required', Rule::enum(AnswerVisibility::class)],
            'questions' => ['array', 'max:100'],
            'questions.*.body' => ['required', 'string', 'max:500'],
            'questions.*.question_type' => ['required', Rule::enum(QuestionType::class)],
            'questions.*.is_required' => ['nullable', 'boolean'],
            'questions.*.scale_min_label' => ['nullable', 'string', 'max:500'],
            'questions.*.scale_max_label' => ['nullable', 'string', 'max:500'],
            'questions.*.choices' => ['array'],
            'questions.*.choices.*.body' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'タイトルを入力してください',
            'title.max' => 'タイトルは500文字以内で入力してください',
            'answer_start_date.required' => '回答期間（開始日・終了日）を入力してください',
            'answer_end_date.required' => '回答期間（開始日・終了日）を入力してください',
            'answer_end_date.after_or_equal' => '回答終了日は開始日以降の日付を入力してください',
            'answer_visibility.required' => '記名または匿名を選択してください',
            'questions.max' => '設問は100件まで登録できます',
            'questions.*.body.required' => '設問文を入力してください',
            'questions.*.body.max' => '設問文は500文字以内で入力してください',
            'questions.*.question_type.required' => '設問形式を選択してください',
            'questions.*.scale_min_label.max' => 'ラベルは500文字以内で入力してください',
            'questions.*.scale_max_label.max' => 'ラベルは500文字以内で入力してください',
            'questions.*.choices.*.body.max' => '選択肢は500文字以内で入力してください',
        ];
    }

    /**
     * VAL-09/10/17: 単一選択・複数選択設問の選択肢件数(2〜10件)と個々の選択肢文言の必須を検証する。
     * 兄弟フィールド(question_type)に応じた条件付き件数チェックは標準の配列ルールでは表現できないため、
     * withValidator() のクロージャで実装する。
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $questions = $this->input('questions', []);

            foreach ($questions as $i => $question) {
                $type = $question['question_type'] ?? null;

                if (! in_array($type, [QuestionType::SingleChoice->value, QuestionType::MultipleChoice->value], true)) {
                    continue;
                }

                $choices = $question['choices'] ?? [];

                if (count($choices) > 10) {
                    $validator->errors()->add("questions.{$i}.choices", '選択肢は10件まで登録できます');
                } elseif (count($choices) < 2) {
                    $validator->errors()->add("questions.{$i}.choices", '選択肢は2件以上登録してください');
                }

                foreach ($choices as $j => $choice) {
                    if (trim((string) ($choice['body'] ?? '')) === '') {
                        $validator->errors()->add("questions.{$i}.choices.{$j}.body", '選択肢を入力してください');
                    }
                }
            }
        });
    }
}
