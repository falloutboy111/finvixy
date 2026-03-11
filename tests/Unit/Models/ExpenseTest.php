<?php

namespace Tests\Unit\Models;

use App\Models\Expense;
use App\Models\ExpenseItem;
use App\Models\Organisation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test 10: Expense-ExpenseItem relationship
     */
    public function test_expense_has_many_expense_items()
    {
        $org = Organisation::factory()->create();
        $expense = Expense::factory()->for($org)->create();

        ExpenseItem::factory(3)->for($expense)->create();

        $this->assertCount(3, $expense->expenseItems);
    }

    /**
     * Test 11: ExpenseItem belongs to Expense
     */
    public function test_expense_item_belongs_to_expense()
    {
        $org = Organisation::factory()->create();
        $expense = Expense::factory()->for($org)->create();
        $item = ExpenseItem::factory()->for($expense)->create();

        $this->assertTrue($item->expense->is($expense));
    }

    /**
     * Test 12: Duplicate detection scope - excludeDuplicates
     */
    public function test_exclude_duplicates_scope()
    {
        $org = Organisation::factory()->create();
        $original = Expense::factory()->for($org)->create(['is_duplicate' => false]);
        $duplicate = Expense::factory()->for($org)->create(['is_duplicate' => true, 'duplicate_of' => $original->id]);

        $results = Expense::excludeDuplicates()->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results[0]->is($original));
    }

    /**
     * Test 13: Duplicate detection scope - onlyDuplicates
     */
    public function test_only_duplicates_scope()
    {
        $org = Organisation::factory()->create();
        $original = Expense::factory()->for($org)->create(['is_duplicate' => false]);
        $duplicate = Expense::factory()->for($org)->create(['is_duplicate' => true, 'duplicate_of' => $original->id]);

        $results = Expense::onlyDuplicates()->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results[0]->is($duplicate));
    }

    /**
     * Test 14: Status transitions (pending -> processing -> processed)
     */
    public function test_status_transitions()
    {
        $org = Organisation::factory()->create();
        $expense = Expense::factory()->for($org)->create(['status' => 'pending']);

        $this->assertEquals('pending', $expense->status);

        $expense->update(['status' => 'processing']);
        $this->assertEquals('processing', $expense->refresh()->status);

        $expense->update(['status' => 'processed']);
        $this->assertEquals('processed', $expense->refresh()->status);
    }

    /**
     * Test 15: Expense casting - amount to decimal
     */
    public function test_expense_amount_cast_to_decimal()
    {
        $org = Organisation::factory()->create();
        $expense = Expense::factory()->for($org)->create(['amount' => 250.5]);

        $this->assertIsString($expense->amount);
        $this->assertEquals('250.50', $expense->amount);
    }

    /**
     * Test 16: Expense isSyncedToDrive helper
     */
    public function test_expense_is_synced_to_drive()
    {
        $org = Organisation::factory()->create();
        $synced = Expense::factory()->for($org)->create(['drive_file_id' => 'file_123']);
        $notSynced = Expense::factory()->for($org)->create(['drive_file_id' => null]);

        $this->assertTrue($synced->isSyncedToDrive());
        $this->assertFalse($notSynced->isSyncedToDrive());
    }
}
