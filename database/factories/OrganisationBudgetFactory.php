<?php

namespace Database\Factories;

use App\Models\Organisation;
use App\Models\OrganisationBudget;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrganisationBudget>
 */
class OrganisationBudgetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organisation_id' => Organisation::factory(),
            'vendor_name' => $this->faker->company(),
            'expense_category' => $this->faker->word(),
            'budget_limit' => $this->faker->randomFloat(2, 100, 5000),
            'monthly_reset_day' => $this->faker->numberBetween(1, 28),
            'current_month_spent' => 0,
            'last_reset_at' => now(),
            'send_alerts' => true,
        ];
    }
}
