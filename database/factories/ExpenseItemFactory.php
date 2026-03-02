<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\ExpenseItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExpenseItem>
 */
class ExpenseItemFactory extends Factory
{
    public function definition(): array
    {
        $qty = fake()->randomFloat(2, 1, 10);
        $price = fake()->randomFloat(2, 5, 500);

        return [
            'expense_id' => Expense::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'qty' => $qty,
            'price' => $price,
            'total' => round($qty * $price, 2),
        ];
    }
}
