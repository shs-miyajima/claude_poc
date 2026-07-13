<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    private static int $sequence = 1;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $number = self::$sequence++;

        return [
            'name' => fake()->company(),
            'code' => sprintf('C%04d', $number),
            'deactivated_at' => null,
        ];
    }

    public function deactivated(): static
    {
        return $this->state(fn (array $attributes) => [
            'deactivated_at' => now(),
        ]);
    }
}
