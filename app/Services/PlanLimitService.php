<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Support\Carbon;

class PlanLimitService
{
    /**
     * Check whether a user can upload more receipts this month.
     *
     * @return array{allowed: bool, used: int, limit: int|string, remaining: int|string}
     */
    public function checkReceiptLimit(User $user, int $additionalCount = 1): array
    {
        $plan = $user->plan;

        if (! $plan) {
            return [
                'allowed' => false,
                'used' => 0,
                'limit' => 0,
                'remaining' => 0,
            ];
        }

        if ($plan->is_unlimited) {
            return [
                'allowed' => true,
                'used' => 0,
                'limit' => 'unlimited',
                'remaining' => 'unlimited',
            ];
        }

        $startOfMonth = Carbon::now()->startOfMonth();

        $used = Expense::query()
            ->where('organisation_id', $user->organisation_id)
            ->whereNotNull('receipt_path')
            ->where('created_at', '>=', $startOfMonth)
            ->count();

        $limit = $plan->receipts_limit;
        $remaining = max(0, $limit - $used);
        $allowed = ($used + $additionalCount) <= $limit;

        return [
            'allowed' => $allowed,
            'used' => $used,
            'limit' => $limit,
            'remaining' => $remaining,
        ];
    }
}
