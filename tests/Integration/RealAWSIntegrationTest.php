<?php

namespace Tests\Integration;

use App\Models\AiUsageLog;
use App\Models\Expense;
use App\Models\ExpenseItem;
use App\Models\Organisation;
use App\Services\BedrockAgentService;
use App\Services\TextractService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\Filesystem;
use Tests\TestCase;

/**
 * PART B: Real AWS Integration Tests
 *
 * These tests use REAL Textract and Bedrock APIs with test images.
 * Cost-aware: ~$0.03 per image for both services combined.
 */
class RealAWSIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected TextractService $textractService;
    protected BedrockAgentService $bedrockService;
    protected Organisation $organisation;
    protected array $testResults = [];
    protected float $totalCost = 0;
    protected array $textractCalls = [];
    protected array $bedrockCalls = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->textractService = new TextractService();
        $this->bedrockService = new BedrockAgentService();
        $this->organisation = Organisation::factory()->create([
            'name' => 'Test Organisation - Real AWS',
        ]);

        Log::channel('stack')->info('🚀 Starting Real AWS Integration Tests', [
            'organisation_id' => $this->organisation->id,
        ]);
    }

    /**
     * TEST 1: Real Textract on high-res image (image001.png)
     */
    public function test_real_textract_on_high_res_image()
    {
        $imagePath = '/home/enclivix/Downloads/image001.png';
        
        $this->assertTrue(file_exists($imagePath), "Test image not found: $imagePath");

        $startTime = microtime(true);
        $fileContents = file_get_contents($imagePath);
        $fileSize = filesize($imagePath);

        try {
            $ocrText = $this->textractService->detectText($fileContents);

            $responseTime = (int) round((microtime(true) - $startTime) * 1000);
            $textLength = strlen($ocrText);

            $this->textractCalls[] = [
                'image' => 'image001.png',
                'dimensions' => '6653x2413',
                'file_size_bytes' => $fileSize,
                'response_time_ms' => $responseTime,
                'ocr_text_length' => $textLength,
                'status' => 'success',
                'cost_estimate' => 0.015, // $0.015 per image
            ];

            $this->totalCost += 0.015;

            Log::channel('stack')->info('✅ Textract Test 1: High-res image success', [
                'image' => 'image001.png',
                'file_size_mb' => round($fileSize / 1024 / 1024, 2),
                'response_time_ms' => $responseTime,
                'ocr_text_length' => $textLength,
                'ocr_preview' => substr($ocrText, 0, 200),
            ]);

            // Assert we got some text
            $this->assertGreaterThan(0, $textLength, 'Textract should extract text from image');
            $this->assertLessThan(10000, $responseTime, 'Textract should respond in < 10s');

        } catch (\Throwable $e) {
            $this->textractCalls[] = [
                'image' => 'image001.png',
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];

            Log::channel('stack')->error('❌ Textract Test 1 failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * TEST 2: Real Textract on moderate-res image (image.png)
     */
    public function test_real_textract_on_moderate_res_image()
    {
        $imagePath = '/home/enclivix/Downloads/image.png';
        
        $this->assertTrue(file_exists($imagePath), "Test image not found: $imagePath");

        $startTime = microtime(true);
        $fileContents = file_get_contents($imagePath);
        $fileSize = filesize($imagePath);

        try {
            $ocrText = $this->textractService->detectText($fileContents);

            $responseTime = (int) round((microtime(true) - $startTime) * 1000);
            $textLength = strlen($ocrText);

            $this->textractCalls[] = [
                'image' => 'image.png',
                'dimensions' => '1512x474',
                'file_size_bytes' => $fileSize,
                'response_time_ms' => $responseTime,
                'ocr_text_length' => $textLength,
                'status' => 'success',
                'cost_estimate' => 0.015,
            ];

            $this->totalCost += 0.015;

            Log::channel('stack')->info('✅ Textract Test 2: Moderate-res image success', [
                'image' => 'image.png',
                'file_size_kb' => round($fileSize / 1024, 2),
                'response_time_ms' => $responseTime,
                'ocr_text_length' => $textLength,
                'ocr_preview' => substr($ocrText, 0, 200),
            ]);

            $this->assertGreaterThan(0, $textLength);
            $this->assertLessThan(10000, $responseTime);

        } catch (\Throwable $e) {
            $this->textractCalls[] = [
                'image' => 'image.png',
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];

            Log::channel('stack')->error('❌ Textract Test 2 failed', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * TEST 3: Real Bedrock parsing with sample expense text
     */
    public function test_real_bedrock_expense_parsing()
    {
        $sampleReceiptText = <<<'TEXT'
        WOOLWORTHS SUPERMARKET
        Receipt No: 12345
        Date: 2024-03-10
        
        Items:
        Bread White x1        15.99
        Milk 1L x2            25.50
        Eggs x1               28.00
        Butter x1             35.50
        Cheese x1             45.00
        
        Subtotal:            149.99
        Tax (15%):            22.50
        Total:               172.49
        
        Payment: CARD
        TEXT;

        $startTime = microtime(true);

        try {
            $parsed = $this->bedrockService->parseExpenseDocument(
                $sampleReceiptText,
                $this->organisation->id
            );

            $responseTime = (int) round((microtime(true) - $startTime) * 1000);
            $tokenEstimate = (int) ceil((strlen($sampleReceiptText) + 1000) / 4);

            $this->bedrockCalls[] = [
                'test' => 'sample_receipt',
                'response_time_ms' => $responseTime,
                'token_estimate' => $tokenEstimate,
                'parsed_vendor' => $parsed['vendor_name'] ?? 'N/A',
                'parsed_amount' => $parsed['total_amount'] ?? 0,
                'parsed_date' => $parsed['date'] ?? 'N/A',
                'status' => 'success',
                'cost_estimate' => 0.0008 * ($tokenEstimate / 1000), // ~$0.0008 per 1K tokens
            ];

            $this->totalCost += $this->bedrockCalls[0]['cost_estimate'];

            Log::channel('stack')->info('✅ Bedrock Test 1: Sample receipt parsing success', [
                'response_time_ms' => $responseTime,
                'vendor' => $parsed['vendor_name'],
                'amount' => $parsed['total_amount'],
                'date' => $parsed['date'],
                'items_count' => count($parsed['line_items'] ?? []),
                'parsed_data' => $parsed,
            ]);

            $this->assertEquals('WOOLWORTHS SUPERMARKET', $parsed['vendor_name']);
            $this->assertGreaterThan(0, $parsed['total_amount']);

        } catch (\Throwable $e) {
            $this->bedrockCalls[] = [
                'test' => 'sample_receipt',
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];

            Log::channel('stack')->error('❌ Bedrock Test 1 failed', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * TEST 4: End-to-end pipeline with real APIs
     */
    public function test_end_to_end_pipeline_with_real_apis()
    {
        $imagePath = '/home/enclivix/Downloads/image.png';
        
        // 1. Create Expense record
        $expense = Expense::factory()->for($this->organisation)->create([
            'status' => 'pending',
            'receipt_path' => 's3://client-mangement-invoices-1/test-receipts/image.png',
            'image_path' => 's3://client-mangement-invoices-1/test-images/image.png',
        ]);

        Log::channel('stack')->info('📝 Expense created', [
            'expense_id' => $expense->id,
            'status' => $expense->status,
        ]);

        // 2. Read actual image and run Textract
        $fileContents = file_get_contents($imagePath);
        $ocrText = $this->textractService->detectText($fileContents);

        Log::channel('stack')->info('🔍 Textract extracted text', [
            'expense_id' => $expense->id,
            'text_length' => strlen($ocrText),
            'text_preview' => substr($ocrText, 0, 300),
        ]);

        // 3. Parse with Bedrock
        $parsed = $this->bedrockService->parseExpenseDocument(
            $ocrText,
            $this->organisation->id,
            null
        );

        Log::channel('stack')->info('🤖 Bedrock parsed', [
            'expense_id' => $expense->id,
            'vendor' => $parsed['vendor_name'],
            'amount' => $parsed['total_amount'],
        ]);

        // 4. Update Expense with parsed data
        $expense->update([
            'name' => $parsed['vendor_name'] ?: $expense->name,
            'amount' => $parsed['total_amount'] > 0 ? $parsed['total_amount'] : $expense->amount,
            'tax' => $parsed['tax_amount'],
            'category' => $parsed['category'],
            'status' => 'processed',
            'extracted_data' => [
                'ocr_text' => $ocrText,
                'ai_response' => $parsed,
                'processed_at' => now()->toIso8601String(),
            ],
        ]);

        // 5. Create ExpenseItems
        foreach ($parsed['line_items'] as $item) {
            ExpenseItem::create([
                'expense_id' => $expense->id,
                'name' => $item['item_name'] ?? $item['name'] ?? 'Item',
                'qty' => (float) ($item['quantity'] ?? 1),
                'price' => (float) ($item['unit_price'] ?? 0),
                'total' => (float) ($item['total_price'] ?? 0),
            ]);
        }

        Log::channel('stack')->info('✅ Expense pipeline completed', [
            'expense_id' => $expense->id,
            'final_status' => $expense->fresh()->status,
            'items_created' => count($parsed['line_items']),
        ]);

        // 6. Verify database state
        $expense->refresh();
        $this->assertEquals('processed', $expense->status);
        $this->assertNotNull($expense->name);
        $this->assertGreaterThan(0, $expense->amount);
        $this->assertNotNull($expense->extracted_data);
    }

    /**
     * TEST 5: Error handling - Invalid credentials (should gracefully fail)
     */
    public function test_error_handling_invalid_image()
    {
        try {
            $invalidData = "This is not an image";
            $this->textractService->detectText($invalidData);

            // Should have thrown an exception
            $this->fail('Expected exception for invalid image data');
        } catch (\Throwable $e) {
            Log::channel('stack')->info('✅ Error handling works: Invalid image rejected', [
                'error_type' => get_class($e),
                'error_message' => $e->getMessage(),
            ]);

            $this->textractCalls[] = [
                'test' => 'invalid_image_handling',
                'status' => 'handled_correctly',
                'error' => $e->getMessage(),
            ];

            // Assert it's an AWS error
            $this->assertStringContainsString('error', strtolower($e->getMessage()));
        }
    }

    /**
     * TEST 6: Rate limiting behavior (test multiple consecutive calls)
     */
    public function test_rate_limiting_multiple_calls()
    {
        $imagePath = '/home/enclivix/Downloads/image.png';
        $fileContents = file_get_contents($imagePath);

        $timings = [];

        try {
            for ($i = 0; $i < 3; $i++) {
                $start = microtime(true);
                $this->textractService->detectText($fileContents);
                $elapsed = (int) round((microtime(true) - $start) * 1000);
                $timings[] = $elapsed;

                Log::channel('stack')->info("📊 Rate limit test call $i", [
                    'response_time_ms' => $elapsed,
                ]);
            }

            Log::channel('stack')->info('✅ Rate limiting test completed', [
                'calls' => 3,
                'timings_ms' => $timings,
                'avg_ms' => (int) (array_sum($timings) / count($timings)),
            ]);
        } catch (\Throwable $e) {
            Log::channel('stack')->warning('⚠️ Rate limiting test: Service throttled', [
                'error' => $e->getMessage(),
                'completed_calls' => count($timings),
            ]);
        }
    }

    /**
     * Teardown: Log summary and verify database state
     */
    protected function tearDown(): void
    {
        // Log final summary
        Log::channel('stack')->info('📋 REAL AWS INTEGRATION TEST SUMMARY', [
            'textract_calls' => count($this->textractCalls),
            'bedrock_calls' => count($this->bedrockCalls),
            'total_cost_estimate' => '$' . number_format($this->totalCost, 4),
            'textract_details' => $this->textractCalls,
            'bedrock_details' => $this->bedrockCalls,
        ]);

        // Verify database state
        $expensesCreated = Expense::where('organisation_id', $this->organisation->id)->count();
        $itemsCreated = ExpenseItem::whereHas('expense', function ($q) {
            $q->where('organisation_id', $this->organisation->id);
        })->count();

        Log::channel('stack')->info('📊 DATABASE VERIFICATION', [
            'expenses_created' => $expensesCreated,
            'expense_items_created' => $itemsCreated,
            'organisation_id' => $this->organisation->id,
        ]);

        parent::tearDown();
    }
}
