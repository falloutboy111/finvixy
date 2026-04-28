<?php

namespace App\Listeners;

use App\Models\Plan;
use Laravel\Paddle\Events\SubscriptionCanceled;
use Laravel\Paddle\Events\SubscriptionCreated;
use Laravel\Paddle\Events\SubscriptionUpdated;

class SyncSubscriptionPlan
{
    public function handleCreated(SubscriptionCreated $event): void
    {
        $this->syncPlan($event->subscription);
    }

    public function handleUpdated(SubscriptionUpdated $event): void
    {
        $this->syncPlan($event->subscription);
    }

    public function handleCanceled(SubscriptionCanceled $event): void
    {
        $subscription = $event->subscription;
        $user = $subscription->billable;

        if (! $user) {
            return;
        }

        // If grace period ended, revert to free
        if (! $subscription->onGracePeriod()) {
            $freePlan = Plan::query()->where('code', 'free')->first();

            if ($freePlan) {
                $user->update(['plan_id' => $freePlan->id]);
            }
        }
    }

    private function syncPlan(\Laravel\Paddle\Subscription $subscription): void
    {
        $user = $subscription->billable;

        if (! $user) {
            return;
        }

        if ($subscription->valid()) {
            $priceId = $subscription->items->first()?->price_id;

            if ($priceId) {
                $plan = Plan::query()->where('paddle_price_id', $priceId)->first();

                if ($plan) {
                    $user->update(['plan_id' => $plan->id]);
                }
            }
        }
    }
}
