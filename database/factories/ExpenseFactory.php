<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\Organisation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organisation_id' => Organisation::factory(),
            'user_id' => User::factory(),
            'name' => fake()->company().' - '.fake()->word(),
            'category' => fake()->randomElement(['meals-entertainment', 'travel', 'supplies', 'software', 'utilities', 'other']),
            'amount' => fake()->randomFloat(2, 10, 5000),
            'tax' => fake()->randomElement(['15%', '0%', null]),
            'date' => fake()->dateTimeBetween('-6 months', 'now'),
            'status' => 'pending',
            'is_duplicate' => false,
        ];
    }

    public function processed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processed',
        ]);
    }

    public function withDriveSync(): static
    {
        return $this->state(fn (array $attributes) => [
            'drive_file_id' => fake()->uuid(),
            'drive_web_link' => 'https://drive.google.com/file/d/'.fake()->uuid(),
        ]);
    }
}
