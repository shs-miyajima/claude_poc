<?php

namespace Database\Factories;

use App\Enums\AnswerVisibility;
use App\Enums\SurveyStatus;
use App\Models\Company;
use App\Models\Survey;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Survey>
 */
class SurveyFactory extends Factory
{
    protected $model = Survey::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'created_by' => User::factory()->admin(),
            'title' => fake()->sentence(),
            'answer_start_date' => '2026-08-01',
            'answer_end_date' => '2026-08-31',
            'answer_visibility' => AnswerVisibility::Named->value,
            'status' => SurveyStatus::Draft->value,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SurveyStatus::Published->value,
        ]);
    }
}
