<?php

namespace Tests\Feature;

use App\Models\Organisation;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsagePageTest extends TestCase
{
    use RefreshDatabase;

    protected function createOnboardedUser(): User
    {
        $org = Organisation::factory()->create();
        $plan = Plan::factory()->free()->create();

        return User::factory()->create([
            'organisation_id' => $org->id,
            'plan_id' => $plan->id,
            'onboarding_completed_at' => now(),
        ]);
    }

    public function test_guest_cannot_access_usage_page(): void
    {
        $response = $this->get(route('usage'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_usage_page(): void
    {
        $user = $this->createOnboardedUser();

        $response = $this->actingAs($user)->get(route('usage'));

        $response->assertOk();
        $response->assertSee('Receipt Usage');
        $response->assertSee('Storage');
    }

    public function test_usage_page_shows_receipt_count(): void
    {
        $user = $this->createOnboardedUser();

        $response = $this->actingAs($user)->get(route('usage'));

        $response->assertOk();
        $response->assertSee('0');
        $response->assertSee('/ 10');
    }

    public function test_usage_page_appears_in_settings_nav(): void
    {
        $user = $this->createOnboardedUser();

        $response = $this->actingAs($user)->get(route('usage'));

        $response->assertOk();
        $response->assertSee('Usage');
    }
}
