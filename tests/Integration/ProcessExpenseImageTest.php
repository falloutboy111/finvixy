<?php

namespace Tests\Integration;

use App\Jobs\ProcessExpenseImage;
use App\Models\Expense;
use App\Models\Organisation;
use App\Services\BedrockAgentService;
use App\Services\TextractService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProcessExpenseImageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test 17: ProcessExpenseImage job creates expense items from parsed data
     */
    public function test_process_expense_image_creates_line_items()
    {
        Queue::fake();

        $org = Organisation::factory()->create();
        $expense = Expense::factory()->for($org)->create([
            'status' => 'pending',
            'receipt_path' => 'receipts/test.png',
        ]);

        // Mock the services
        $mockTextractService = \Mockery::mock(TextractService::class);
        $mockTextractService->shouldReceive('detectText')->andReturn(
            "Woolworths\nBread: 1 x 15.99 = 15.99\nMilk: 1 x 25.50 = 25.50\nTotal: 41.49"
        );

        $mockBedrockService = \Mockery::mock(BedrockAgentService::class);
        $mockBedrockService->shouldReceive('parseExpenseDocument')->andReturn([
            'vendor_name' => 'Woolworths',
            'invoice_number' => 'INV-001',
            'date' => '2024-03-10',
            'total_amount' => 41.49,
            'currency' => 'ZAR',
            'category' => 'Groceries',
            'tax_amount' => 4.15,
            'line_items' => [
                ['item_name' => 'Bread', 'quantity' => 1, 'unit_price' => 15.99, 'total_price' => 15.99],
                ['item_name' => 'Milk', 'quantity' => 1, 'unit_price' => 25.50, 'total_price' => 25.50],
            ],
        ]);

        // Mock Storage to return file contents
        Storage::fake('org-storage');
        Storage::disk('org-storage')->put('receipts/test.png', 'fake image data');

        $this->app->bind(TextractService::class, fn () => $mockTextractService);
        $this->app->bind(BedrockAgentService::class, fn () => $mockBedrockService);

        // Execute the job
        (new ProcessExpenseImage($expense))->handle($mockTextractService, $mockBedrockService);

        // Verify expense was updated
        $expense->refresh();
        $this->assertEquals('processed', $expense->status);
        $this->assertEquals('Woolworths', $expense->name);
        $this->assertEquals(41.49, (float) $expense->amount);
        $this->assertEquals('2024-03-10', $expense->date->format('Y-m-d'));

        // Verify line items were created
        $this->assertCount(2, $expense->expenseItems);
        $this->assertEquals('Bread', $expense->expenseItems[0]->name);
        $this->assertEquals('Milk', $expense->expenseItems[1]->name);
    }

    /**
     * Test 18: ProcessExpenseImage handles duplicate detection
     */
    public function test_process_expense_image_detects_duplicates()
    {
        Queue::fake();

        $org = Organisation::factory()->create();

        // Create original expense
        $original = Expense::factory()->for($org)->create([
            'name' => 'Woolworths',
            'amount' => 41.49,
            'date' => '2024-03-10',
            'additional_fields' => ['invoice_number' => 'INV-001'],
            'is_duplicate' => false,
        ]);

        // Create expense to be processed
        $duplicate = Expense::factory()->for($org)->create([
            'status' => 'pending',
            'receipt_path' => 'receipts/test2.png',
        ]);

        $mockTextractService = \Mockery::mock(TextractService::class);
        $mockTextractService->shouldReceive('detectText')->andReturn('Woolworths receipt');

        $mockBedrockService = \Mockery::mock(BedrockAgentService::class);
        $mockBedrockService->shouldReceive('parseExpenseDocument')->andReturn([
            'vendor_name' => 'Woolworths',
            'invoice_number' => 'INV-001',
            'date' => '2024-03-10',
            'total_amount' => 41.49,
            'currency' => 'ZAR',
            'category' => 'Groceries',
            'tax_amount' => null,
            'line_items' => [],
        ]);

        Storage::fake('org-storage');
        Storage::disk('org-storage')->put('receipts/test2.png', 'fake image data');

        $this->app->bind(TextractService::class, fn () => $mockTextractService);
        $this->app->bind(BedrockAgentService::class, fn () => $mockBedrockService);

        (new ProcessExpenseImage($duplicate))->handle($mockTextractService, $mockBedrockService);

        // Verify duplicate was marked
        $duplicate->refresh();
        $this->assertTrue($duplicate->is_duplicate);
        $this->assertEquals($original->id, $duplicate->duplicate_of);
    }

    /**
     * Test 19: ProcessExpenseImage handles empty OCR text
     */
    public function test_process_expense_image_handles_empty_ocr_text()
    {
        Queue::fake();

        $org = Organisation::factory()->create();
        $expense = Expense::factory()->for($org)->create([
            'status' => 'pending',
            'receipt_path' => 'receipts/blank.png',
        ]);

        $mockTextractService = \Mockery::mock(TextractService::class);
        $mockTextractService->shouldReceive('detectText')->andReturn('   ');

        Storage::fake('org-storage');
        Storage::disk('org-storage')->put('receipts/blank.png', 'fake blank image');

        $this->app->bind(TextractService::class, fn () => $mockTextractService);

        (new ProcessExpenseImage($expense))->handle($mockTextractService, \Mockery::mock(BedrockAgentService::class));

        // Verify failure status
        $expense->refresh();
        $this->assertEquals('failed', $expense->status);
        $this->assertStringContainsString('No text could be extracted', $expense->additional_fields['error'] ?? '');
    }

    /**
     * Test 20: ProcessExpenseImage status transitions
     */
    public function test_process_expense_image_status_transitions()
    {
        Queue::fake();

        $org = Organisation::factory()->create();
        $expense = Expense::factory()->for($org)->create([
            'status' => 'pending',
            'receipt_path' => 'receipts/test.png',
        ]);

        $mockTextractService = \Mockery::mock(TextractService::class);
        $mockTextractService->shouldReceive('detectText')->andReturn('Shop\nItem: 100');

        $mockBedrockService = \Mockery::mock(BedrockAgentService::class);
        $mockBedrockService->shouldReceive('parseExpenseDocument')->andReturn([
            'vendor_name' => 'Shop',
            'invoice_number' => null,
            'date' => null,
            'total_amount' => 100,
            'currency' => 'ZAR',
            'category' => null,
            'tax_amount' => null,
            'line_items' => [],
        ]);

        Storage::fake('org-storage');
        Storage::disk('org-storage')->put('receipts/test.png', 'fake data');

        $this->app->bind(TextractService::class, fn () => $mockTextractService);
        $this->app->bind(BedrockAgentService::class, fn () => $mockBedrockService);

        // Initial status
        $this->assertEquals('pending', $expense->status);

        (new ProcessExpenseImage($expense))->handle($mockTextractService, $mockBedrockService);

        // Final status
        $expense->refresh();
        $this->assertEquals('processed', $expense->status);
    }
}
