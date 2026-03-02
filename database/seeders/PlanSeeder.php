<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'code' => 'free',
                'name' => 'Free',
                'price_monthly' => 0,
                'receipts_limit' => 10,
                'is_unlimited' => false,
                'description' => 'Perfect for trying out Finvixy.',
            ],
            [
                'code' => 'starter',
                'name' => 'Starter',
                'price_monthly' => 99,
                'receipts_limit' => 50,
                'is_unlimited' => false,
                'description' => 'For individuals and freelancers.',
            ],
            [
                'code' => 'professional',
                'name' => 'Professional',
                'price_monthly' => 189,
                'receipts_limit' => 150,
                'is_unlimited' => false,
                'description' => 'For growing businesses.',
            ],
            [
                'code' => 'business',
                'name' => 'Business',
                'price_monthly' => 349,
                'receipts_limit' => 500,
                'is_unlimited' => false,
                'description' => 'For teams and businesses.',
            ],
            [
                'code' => 'enterprise',
                'name' => 'Enterprise',
                'price_monthly' => 599,
                'receipts_limit' => null,
                'is_unlimited' => true,
                'description' => 'Unlimited everything.',
            ],
        ];

        foreach ($plans as $plan) {
            Plan::query()->updateOrCreate(
                ['code' => $plan['code']],
                $plan
            );
        }
    }
}
