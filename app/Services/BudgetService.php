<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\OrganisationBudget;

class BudgetService
{
    /**
     * Check if an expense exceeds any budget and return alert data if it does.
     *
     * @return array{exceeded: bool, vendor_name: string, category: string, budget_limit: float, current_month_spent: float, overage: float, expense_amount: float}|null
     */
    public function checkExpenseBudget(Expense $expense): ?array
    {
        $vendor = $expense->name ?? 'Unknown Vendor';
        $category = $expense->category;
        $amount = (float) $expense->amount;

        if (! $expense->organisation_id) {
            return null;
        }

        // Find matching budget
        $budget = OrganisationBudget::query()
            ->where('organisation_id', $expense->organisation_id)
            ->where(function ($query) use ($vendor) {
                $query->where('vendor_name', $vendor)
                    ->orWhereNull('vendor_name');
            })
            ->where(function ($query) use ($category) {
                $query->where('expense_category', $category)
                    ->orWhereNull('expense_category');
            })
            ->first();

        if (! $budget) {
            return null;
        }

        // Reset if needed
        $budget->resetIfNeeded();

        // Add this expense to current month spending
        $newSpent = (float) $budget->current_month_spent + $amount;
        $budgetLimit = (float) $budget->budget_limit;

        // Check if exceeded
        if ($newSpent > $budgetLimit && ! ($budget->current_month_spent > $budgetLimit)) {
            // Budget was not exceeded before, but is now
            $budget->update(['current_month_spent' => $newSpent]);

            return [
                'exceeded' => true,
                'vendor_name' => $vendor,
                'category' => $category,
                'budget_limit' => $budgetLimit,
                'current_month_spent' => $newSpent,
                'overage' => $newSpent - $budgetLimit,
                'expense_amount' => $amount,
            ];
        } else {
            // Just update the spending tracker
            $budget->update(['current_month_spent' => $newSpent]);
        }

        return null;
    }

    /**
     * Get all budgets for an organisation.
     *
     * @return array<int, OrganisationBudget>
     */
    public function getOrganisationBudgets(int $organisationId): array
    {
        return OrganisationBudget::query()
            ->where('organisation_id', $organisationId)
            ->get()
            ->toArray();
    }

    /**
     * Set a budget for an organisation, vendor, and category.
     */
    public function setBudget(
        int $organisationId,
        ?string $vendor,
        ?string $category,
        float $budgetLimit,
        int $resetDay = 1,
        bool $sendAlerts = true
    ): OrganisationBudget {
        // Find or create
        $budget = OrganisationBudget::query()
            ->where('organisation_id', $organisationId)
            ->where('vendor_name', $vendor)
            ->where('expense_category', $category)
            ->firstOrNew();

        $budget->fill([
            'organisation_id' => $organisationId,
            'vendor_name' => $vendor,
            'expense_category' => $category,
            'budget_limit' => $budgetLimit,
            'monthly_reset_day' => $resetDay,
            'send_alerts' => $sendAlerts,
        ]);

        $budget->save();

        return $budget;
    }
}
