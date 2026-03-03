<?php

namespace App\Console\Commands;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Console\Command;

class UpgradeUserPlan extends Command
{
    protected $signature = 'app:upgrade-plan
        {email : The user email address}
        {plan : The plan code (free, starter, professional, business, enterprise)}';

    protected $description = 'Upgrade or change a user\'s plan';

    public function handle(): int
    {
        $user = User::query()->where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error("User not found: {$this->argument('email')}");

            return self::FAILURE;
        }

        $plan = Plan::query()->where('code', $this->argument('plan'))->first();

        if (! $plan) {
            $this->error("Plan not found: {$this->argument('plan')}");
            $this->line('Available plans: '.Plan::query()->pluck('code')->implode(', '));

            return self::FAILURE;
        }

        $oldPlan = $user->plan;

        $user->update(['plan_id' => $plan->id]);

        $oldPlanName = $oldPlan?->name ?? 'None';
        $limit = $plan->is_unlimited ? 'unlimited' : $plan->receipts_limit;

        $this->info("User: {$user->name} ({$user->email})");
        $this->info('Old plan: '.$oldPlanName);
        $this->info("New plan: {$plan->name} ({$limit} receipts/month)");

        return self::SUCCESS;
    }
}
