<?php

namespace App\Services;

use App\Mail\UnlimitedScanMilestone;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

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

        $startOfMonth = Carbon::now()->startOfMonth();

        $used = Expense::query()
            ->where('organisation_id', $user->organisation_id)
            ->whereNotNull('receipt_path')
            ->where('created_at', '>=', $startOfMonth)
            ->count();

        // Organisation-level unlimited override
        if ($user->organisation->unlimited_receipts) {
            $this->checkUnlimitedMilestone($user, $used + $additionalCount);

            return [
                'allowed' => true,
                'used' => $used,
                'limit' => 'unlimited',
                'remaining' => 'unlimited',
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

    /**
     * Send a milestone notification to the admin when an unlimited org hits a multiple of 50 receipts.
     */
    private function checkUnlimitedMilestone(User $user, int $newTotal): void
    {
        if ($newTotal > 0 && $newTotal % 50 === 0) {
            Mail::to('keegan@enclivix.com')->queue(
                new UnlimitedScanMilestone($user->organisation->name, $newTotal)
            );
        }
    }
}
