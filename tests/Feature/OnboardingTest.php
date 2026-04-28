<?php

namespace Tests\Feature;

use App\Models\Organisation;
use App\Models\Plan;
use App\Models\User;
use App\Services\OrgStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    protected function createUserWithOrg(array $userOverrides = []): User
    {
        $org = Organisation::factory()->create();
        $plan = Plan::factory()->create(['code' => 'free', 'price_monthly' => 0, 'is_active' => true]);

        return User::factory()->create(array_merge([
            'organisation_id' => $org->id,
            'plan_id' => $plan->id,
            'onboarding_completed_at' => null,
        ], $userOverrides));
    }

    public function test_guest_cannot_access_onboarding(): void
    {
        $response = $this->get(route('onboarding'));

        $response->assertRedirect(route('login'));
    }

    public function test_new_user_is_redirected_to_onboarding(): void
    {
        $user = $this->createUserWithOrg();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertRedirect(route('onboarding'));
    }

    public function test_onboarded_user_goes_to_dashboard(): void
    {
        $user = $this->createUserWithOrg([
            'onboarding_completed_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
    }

    public function test_onboarding_page_renders_for_new_user(): void
    {
        $user = $this->createUserWithOrg();

        $response = $this->actingAs($user)->get(route('onboarding'));

        $response->assertOk();
        $response->assertSee('Welcome,');
        $response->assertSee($user->name);
    }

    public function test_completed_user_redirected_from_onboarding_to_dashboard(): void
    {
        $user = $this->createUserWithOrg([
            'onboarding_completed_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('onboarding'));

        $response->assertRedirect(route('dashboard'));
    }

    public function test_onboarding_middleware_allows_completed_user(): void
    {
        $user = $this->createUserWithOrg([
            'onboarding_completed_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
    }

    public function test_onboarding_middleware_blocks_incomplete_user(): void
    {
        $user = $this->createUserWithOrg();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertRedirect(route('onboarding'));
    }

    public function test_organisation_has_storage_fields(): void
    {
        $org = Organisation::factory()->create();
        $org->refresh();

        $this->assertEquals('none', $org->storage_type);
        $this->assertEquals(0, $org->storage_used_bytes);
        $this->assertEquals(1073741824, $org->storage_limit_bytes); // 1 GB
    }

    public function test_user_has_onboarding_completed_at_field(): void
    {
        $user = $this->createUserWithOrg();

        $this->assertNull($user->onboarding_completed_at);

        $user->update(['onboarding_completed_at' => now()]);
        $user->refresh();

        $this->assertNotNull($user->onboarding_completed_at);
    }

    public function test_org_storage_service_format_bytes(): void
    {
        $this->assertEquals('0.0 KB', OrgStorageService::formatBytes(0));
        $this->assertEquals('1.0 KB', OrgStorageService::formatBytes(1024));
        $this->assertEquals('1.0 MB', OrgStorageService::formatBytes(1048576));
        $this->assertEquals('1.0 GB', OrgStorageService::formatBytes(1073741824));
    }

    public function test_register_response_redirects_to_onboarding(): void
    {
        Plan::factory()->create(['code' => 'free', 'price_monthly' => 0, 'is_active' => true]);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'onboard@test.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'organisation_name' => 'Test Org',
            'whatsapp_number' => '+27123456789',
        ]);

        $response->assertRedirect(route('onboarding'));
    }

    public function test_finvixy_storage_sets_org_storage_type(): void
    {
        $user = $this->createUserWithOrg();
        $org = $user->organisation;

        $this->assertEquals('none', $org->storage_type);

        $org->update(['storage_type' => 's3']);
        $org->refresh();

        $this->assertEquals('s3', $org->storage_type);
    }

    public function test_drive_storage_sets_org_storage_type(): void
    {
        $user = $this->createUserWithOrg();
        $org = $user->organisation;

        $org->update(['storage_type' => 'drive']);
        $org->refresh();

        $this->assertEquals('drive', $org->storage_type);
    }
}
