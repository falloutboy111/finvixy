<?php

namespace Tests\Feature\Expenses;

use App\Models\Expense;
use App\Models\Organisation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ReceiptAccessTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithOrg(): array
    {
        $org = Organisation::factory()->create([
            'storage_limit_bytes' => 1073741824,
            'storage_used_bytes' => 0,
        ]);

        $user = User::factory()->create([
            'organisation_id' => $org->id,
        ]);

        return [$user, $org];
    }

    public function test_user_can_get_temporary_url_for_own_receipt(): void
    {
        Storage::fake('org-storage');

        [$user, $org] = $this->createUserWithOrg();

        Storage::disk('org-storage')->put('org-'.$org->id.'/receipts/test.jpg', 'fake-image-data');

        $expense = Expense::factory()->create([
            'organisation_id' => $org->id,
            'user_id' => $user->id,
            'receipt_path' => 'org-'.$org->id.'/receipts/test.jpg',
            'status' => 'processed',
        ]);

        $this->actingAs($user);

        $component = Livewire::test('pages::expenses.index');
        $result = $component->instance()->getReceiptUrl($expense->id);

        $this->assertNotNull($result);
        $this->assertStringContainsString('org-'.$org->id.'/receipts/test.jpg', $result);
    }

    public function test_user_cannot_access_receipt_from_another_org(): void
    {
        Storage::fake('org-storage');

        [$user, $org] = $this->createUserWithOrg();

        $otherOrg = Organisation::factory()->create();
        $otherUser = User::factory()->create([
            'organisation_id' => $otherOrg->id,
        ]);

        Storage::disk('org-storage')->put('org-'.$otherOrg->id.'/receipts/secret.jpg', 'secret-data');

        $otherExpense = Expense::factory()->create([
            'organisation_id' => $otherOrg->id,
            'user_id' => $otherUser->id,
            'receipt_path' => 'org-'.$otherOrg->id.'/receipts/secret.jpg',
            'status' => 'processed',
        ]);

        $this->actingAs($user);

        $component = Livewire::test('pages::expenses.index');
        $result = $component->instance()->getReceiptUrl($otherExpense->id);

        $this->assertNull($result);
    }

    public function test_user_cannot_download_receipt_from_another_org(): void
    {
        Storage::fake('org-storage');

        [$user, $org] = $this->createUserWithOrg();

        $otherOrg = Organisation::factory()->create();

        $otherExpense = Expense::factory()->create([
            'organisation_id' => $otherOrg->id,
            'user_id' => User::factory()->create(['organisation_id' => $otherOrg->id])->id,
            'receipt_path' => 'org-'.$otherOrg->id.'/receipts/secret.jpg',
            'status' => 'processed',
        ]);

        $this->actingAs($user);

        $component = Livewire::test('pages::expenses.index');
        $result = $component->instance()->getReceiptUrl($otherExpense->id);

        $this->assertNull($result);
    }

    public function test_viewing_expense_detail_is_scoped_to_own_org(): void
    {
        Storage::fake('org-storage');

        [$user, $org] = $this->createUserWithOrg();

        $otherOrg = Organisation::factory()->create();
        $otherExpense = Expense::factory()->create([
            'organisation_id' => $otherOrg->id,
            'user_id' => User::factory()->create(['organisation_id' => $otherOrg->id])->id,
        ]);

        $this->actingAs($user);

        $component = Livewire::test('pages::expenses.index')
            ->call('viewExpense', $otherExpense->id);

        $this->assertNull($component->instance()->getViewingExpenseProperty());
    }

    public function test_receipt_url_returns_null_when_no_receipt_path(): void
    {
        Storage::fake('org-storage');

        [$user, $org] = $this->createUserWithOrg();

        $expense = Expense::factory()->create([
            'organisation_id' => $org->id,
            'user_id' => $user->id,
            'receipt_path' => null,
            'status' => 'processed',
        ]);

        $this->actingAs($user);

        $component = Livewire::test('pages::expenses.index');
        $result = $component->instance()->getReceiptUrl($expense->id);

        $this->assertNull($result);
    }
}
