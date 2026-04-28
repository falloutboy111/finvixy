<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_billing_page(): void
    {
        $response = $this->get(route('billing'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_billing_page(): void
    {
        $plan = Plan::factory()->free()->create();
        $user = User::factory()->create(['plan_id' => $plan->id]);

        $this->actingAs($user);

        $response = $this->get(route('billing'));
        $response->assertOk();
    }

    public function test_billing_page_shows_current_plan(): void
    {
        $plan = Plan::factory()->create([
            'code' => 'starter',
            'name' => 'Starter',
            'price_monthly' => 99,
            'receipts_limit' => 50,
        ]);

        $user = User::factory()->create(['plan_id' => $plan->id]);
        $this->actingAs($user);

        $response = $this->get(route('billing'));
        $response->assertOk();
        $response->assertSee('Starter');
    }

    public function test_billing_page_shows_available_plans(): void
    {
        $free = Plan::factory()->free()->create();
        Plan::factory()->create([
            'code' => 'starter',
            'name' => 'Starter',
            'price_monthly' => 99,
        ]);
        Plan::factory()->create([
            'code' => 'professional',
            'name' => 'Professional',
            'price_monthly' => 189,
        ]);

        $user = User::factory()->create(['plan_id' => $free->id]);
        $this->actingAs($user);

        $response = $this->get(route('billing'));
        $response->assertOk();
        $response->assertSee('Starter');
        $response->assertSee('Professional');
    }

    public function test_subscription_middleware_redirects_non_subscribers(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        \Illuminate\Support\Facades\Route::middleware(['auth', 'subscribed'])
            ->get('/test-subscribed', fn () => 'ok');

        $response = $this->get('/test-subscribed');
        $response->assertRedirect(route('billing'));
    }

    public function test_user_model_has_billable_trait(): void
    {
        $user = new User;

        $this->assertTrue(
            method_exists($user, 'subscribed'),
            'User model should have the Billable trait with subscribed() method'
        );
    }

    public function test_plan_model_has_paddle_price_id_attribute(): void
    {
        $plan = Plan::factory()->create([
            'paddle_price_id' => 'pri_test_123',
        ]);

        $this->assertEquals('pri_test_123', $plan->paddle_price_id);
    }
}
