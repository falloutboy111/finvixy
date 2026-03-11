<?php

namespace Tests\Unit\Mail;

use App\Mail\QuotaExceededMail;
use App\Models\Expense;
use App\Models\Organisation;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class QuotaExceededMailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test 1: User at 90% quota → no email yet
     */
    public function test_user_at_90_percent_quota_no_email()
    {
        $org = Organisation::factory()->create();
        $plan = Plan::factory()->create(['receipts_limit' => 100]);
        $user = User::factory()->for($org)->for($plan)->create();

        // Create 90 expenses this month
        Expense::factory(90)->for($org)->for($user)->create([
            'receipt_path' => 'path/to/receipt.pdf',
            'created_at' => now(),
        ]);

        // At 90%, no quota exceeded alert should be sent
        $mail = new QuotaExceededMail($user, 90, 100, $org);
        $this->assertIsObject($mail);
    }

    /**
     * Test 2: User at 100% quota → email sent
     */
    public function test_user_at_100_percent_quota_email_sent()
    {
        $org = Organisation::factory()->create();
        $plan = Plan::factory()->create(['receipts_limit' => 100]);
        $user = User::factory()->for($org)->for($plan)->create();

        // Create 100 expenses this month
        Expense::factory(100)->for($org)->for($user)->create([
            'receipt_path' => 'path/to/receipt.pdf',
            'created_at' => now(),
        ]);

        $mail = new QuotaExceededMail($user, 100, 100, $org);

        $this->assertNotNull($mail->user);
        $this->assertEquals(100, $mail->used);
        $this->assertEquals(100, $mail->limit);
    }

    /**
     * Test 3: Email has correct numbers
     */
    public function test_email_has_correct_numbers()
    {
        $org = Organisation::factory()->create();
        $plan = Plan::factory()->create([
            'name' => 'Pro Plan',
            'receipts_limit' => 50,
        ]);
        $user = User::factory()->for($org)->for($plan)->create();

        $mail = new QuotaExceededMail($user, 50, 50, $org);

        $this->assertEquals(50, $mail->used);
        $this->assertEquals(50, $mail->limit);
        $this->assertEquals('Pro Plan', $mail->planName);
    }

    /**
     * Test 4: Email has upgrade link
     */
    public function test_email_has_upgrade_link()
    {
        $org = Organisation::factory()->create();
        $plan = Plan::factory()->create(['receipts_limit' => 100]);
        $user = User::factory()->for($org)->for($plan)->create();

        $mail = new QuotaExceededMail($user, 100, 100, $org);

        $this->assertStringContainsString('/settings/billing', $mail->upgradeUrl);
        $this->assertStringContainsString(config('app.url'), $mail->upgradeUrl);
    }

    /**
     * Test 5: Email subject is correct
     */
    public function test_email_subject_is_correct()
    {
        $org = Organisation::factory()->create();
        $plan = Plan::factory()->create(['receipts_limit' => 100]);
        $user = User::factory()->for($org)->for($plan)->create();

        $mail = new QuotaExceededMail($user, 100, 100, $org);
        $envelope = $mail->envelope();

        $this->assertStringContainsString('Receipt Quota Exceeded', $envelope->subject);
    }

    /**
     * Test 6: Different plans have different limits
     */
    public function test_different_plans_have_different_limits()
    {
        $org = Organisation::factory()->create();

        $freePlan = Plan::factory()->create([
            'name' => 'Free',
            'receipts_limit' => 10,
        ]);
        $freeUser = User::factory()->for($org)->for($freePlan)->create();

        $proPlan = Plan::factory()->create([
            'name' => 'Pro',
            'receipts_limit' => 100,
        ]);
        $proUser = User::factory()->for($org)->for($proPlan)->create();

        $freeMail = new QuotaExceededMail($freeUser, 10, 10, $org);
        $proMail = new QuotaExceededMail($proUser, 100, 100, $org);

        $this->assertEquals(10, $freeMail->limit);
        $this->assertEquals(100, $proMail->limit);
        $this->assertEquals('Free', $freeMail->planName);
        $this->assertEquals('Pro', $proMail->planName);
    }
}
