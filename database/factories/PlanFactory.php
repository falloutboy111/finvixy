<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->slug(2),
            'name' => fake()->word(),
            'price_monthly' => fake()->randomFloat(2, 0, 599),
            'currency' => 'ZAR',
            'receipts_limit' => fake()->numberBetween(10, 500),
            'is_unlimited' => false,
            'is_active' => true,
        ];
    }

    public function free(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'free',
            'name' => 'Free',
            'price_monthly' => 0,
            'receipts_limit' => 10,
        ]);
    }

    public function unlimited(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_unlimited' => true,
            'receipts_limit' => null,
        ]);
    }
}
