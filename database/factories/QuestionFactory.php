<?php

namespace Database\Factories;

use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\Survey;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    protected $model = Question::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'survey_id' => Survey::factory(),
            'question_type' => QuestionType::FreeText->value,
            'body' => fake()->sentence(),
            'is_required' => false,
            'sort_order' => 0,
            'scale_min_label' => null,
            'scale_max_label' => null,
        ];
    }

    public function singleChoice(): static
    {
        return $this->state(fn (array $attributes) => [
            'question_type' => QuestionType::SingleChoice->value,
        ]);
    }

    public function multipleChoice(): static
    {
        return $this->state(fn (array $attributes) => [
            'question_type' => QuestionType::MultipleChoice->value,
        ]);
    }

    public function scale(): static
    {
        return $this->state(fn (array $attributes) => [
            'question_type' => QuestionType::Scale->value,
        ]);
    }
}
