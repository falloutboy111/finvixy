<?php

namespace Tests\Feature;

use App\Listeners\SyncSubscriptionPlan;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Paddle\Events\SubscriptionCanceled;
use Laravel\Paddle\Events\SubscriptionCreated;
use Laravel\Paddle\Subscription;
use Tests\TestCase;

class SyncSubscriptionPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_listener_syncs_plan_on_subscription_created(): void
    {
        $freePlan = Plan::factory()->free()->create();
        $starterPlan = Plan::factory()->create([
            'code' => 'starter',
            'name' => 'Starter',
            'paddle_price_id' => 'pri_starter_test',
            'price_monthly' => 99,
        ]);

        $user = User::factory()->create(['plan_id' => $freePlan->id]);

        // Create a Cashier subscription record directly
        $subscription = $user->subscriptions()->create([
            'type' => 'default',
            'paddle_id' => 'sub_test_123',
            'status' => 'active',
        ]);

        $subscription->items()->create([
            'product_id' => 'pro_test',
            'price_id' => 'pri_starter_test',
            'status' => 'active',
            'quantity' => 1,
        ]);

        $subscription->load('items');

        $event = new SubscriptionCreated($user, $subscription, []);

        $listener = new SyncSubscriptionPlan;
        $listener->handleCreated($event);

        $user->refresh();
        $this->assertEquals($starterPlan->id, $user->plan_id);
    }

    public function test_listener_does_not_change_plan_if_price_id_not_found(): void
    {
        $freePlan = Plan::factory()->free()->create();
        $user = User::factory()->create(['plan_id' => $freePlan->id]);

        $subscription = $user->subscriptions()->create([
            'type' => 'default',
            'paddle_id' => 'sub_test_456',
            'status' => 'active',
        ]);

        $subscription->items()->create([
            'product_id' => 'pro_test',
            'price_id' => 'pri_unknown',
            'status' => 'active',
            'quantity' => 1,
        ]);

        $subscription->load('items');

        $event = new SubscriptionCreated($user, $subscription, []);

        $listener = new SyncSubscriptionPlan;
        $listener->handleCreated($event);

        $user->refresh();
        $this->assertEquals($freePlan->id, $user->plan_id);
    }

    public function test_events_are_registered_in_service_provider(): void
    {
        $this->assertTrue(
            collect(\Illuminate\Support\Facades\Event::getListeners(SubscriptionCreated::class))
                ->isNotEmpty(),
            'SubscriptionCreated event should have listeners registered'
        );

        $this->assertTrue(
            collect(\Illuminate\Support\Facades\Event::getListeners(SubscriptionCanceled::class))
                ->isNotEmpty(),
            'SubscriptionCanceled event should have listeners registered'
        );
    }
}
