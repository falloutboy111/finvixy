<?php

namespace App\Jobs;

use App\Models\Expense;
use App\Models\ExpenseItem;
use App\Services\BedrockAgentService;
use App\Services\TextractService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessExpenseImage implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 600;

    public int $backoff = 30;

    public function __construct(
        public Expense $expense,
    ) {
        $this->onQueue('ocr');
    }

    public function handle(TextractService $textractService, BedrockAgentService $bedrockService): void
    {
        try {
            $this->expense->update(['status' => 'processing']);

            $extension = strtolower(pathinfo($this->expense->receipt_path, PATHINFO_EXTENSION));

            if ($extension === 'pdf') {
                $this->processPdf($textractService, $bedrockService);
            } else {
                $this->processImage($textractService, $bedrockService);
            }
        } catch (\Throwable $e) {
            Log::error('ProcessExpenseImage failed', [
                'expense_id' => $this->expense->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->expense->update([
                'status' => 'failed',
                'additional_fields' => array_merge(
                    $this->expense->additional_fields ?? [],
                    ['error' => $e->getMessage()]
                ),
            ]);

            throw $e;
        }
    }

    /**
     * Process an image file (JPEG, PNG, WEBP) synchronously via Textract.
     */
    protected function processImage(TextractService $textractService, BedrockAgentService $bedrockService): void
    {
        $fileContents = Storage::disk('s3')->get($this->expense->receipt_path);

        if (! $fileContents) {
            throw new \RuntimeException("Could not read file from S3: {$this->expense->receipt_path}");
        }

        $ocrText = $textractService->detectText($fileContents);

        $this->parseAndUpdateExpense($ocrText, $bedrockService);
    }

    /**
     * Process a PDF document via async Textract.
     */
    protected function processPdf(TextractService $textractService, BedrockAgentService $bedrockService): void
    {
        $bucket = config('filesystems.disks.s3.bucket');
        $key = $this->expense->receipt_path;

        $jobId = $textractService->startAsyncDetection($bucket, $key);

        // Poll for result (max 10 minutes with 30s intervals)
        $maxAttempts = 20;
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            sleep(30);
            $attempt++;

            $result = $textractService->getAsyncResult($jobId);

            if ($result['status'] === 'SUCCEEDED') {
                $this->parseAndUpdateExpense($result['text'] ?? '', $bedrockService);

                return;
            }

            if ($result['status'] === 'FAILED') {
                throw new \RuntimeException("Textract PDF processing failed for job: {$jobId}");
            }

            // Status is IN_PROGRESS — keep polling
        }

        throw new \RuntimeException("Textract PDF processing timed out for job: {$jobId}");
    }

    /**
     * Send OCR text to Bedrock for parsing and update the expense record.
     */
    protected function parseAndUpdateExpense(string $ocrText, BedrockAgentService $bedrockService): void
    {
        if (empty(trim($ocrText))) {
            $this->expense->update([
                'status' => 'failed',
                'additional_fields' => array_merge(
                    $this->expense->additional_fields ?? [],
                    ['error' => 'No text could be extracted from the receipt']
                ),
            ]);

            return;
        }

        $parsed = $bedrockService->parseExpenseDocument($ocrText, $this->expense->organisation_id);

        // Update the expense with extracted data
        $updateData = [
            'name' => $parsed['vendor_name'] ?: ($this->expense->name ?: 'Unknown Vendor'),
            'amount' => $parsed['total_amount'] > 0 ? $parsed['total_amount'] : $this->expense->amount,
            'tax' => $parsed['tax_amount'],
            'category' => $parsed['category'],
            'status' => 'processed',
            'extracted_data' => [
                'ocr_text' => $ocrText,
                'ai_response' => $parsed,
                'processed_at' => now()->toIso8601String(),
            ],
        ];

        // Only set date if AI extracted one
        if ($parsed['date']) {
            try {
                $updateData['date'] = Carbon::parse($parsed['date'])->toDateString();
            } catch (\Throwable) {
                // Keep existing date if parse fails
            }
        }

        // Store invoice number in additional_fields
        if ($parsed['invoice_number']) {
            $updateData['additional_fields'] = array_merge(
                $this->expense->additional_fields ?? [],
                [
                    'invoice_number' => $parsed['invoice_number'],
                    'currency' => $parsed['currency'],
                ]
            );
        }

        $this->expense->update($updateData);

        // Create line items
        $this->createLineItems($parsed['line_items']);

        // Run duplicate detection
        $this->detectDuplicate($parsed);

        Log::info('Expense processed successfully', [
            'expense_id' => $this->expense->id,
            'vendor' => $parsed['vendor_name'],
            'amount' => $parsed['total_amount'],
        ]);
    }

    /**
     * Create ExpenseItem records from parsed line items.
     *
     * @param  array<int, array{item_name?: string, name?: string, quantity?: float, unit_price?: float, total_price?: float}>  $lineItems
     */
    protected function createLineItems(array $lineItems): void
    {
        foreach ($lineItems as $item) {
            $name = $item['item_name'] ?? $item['name'] ?? 'Item';
            $qty = (float) ($item['quantity'] ?? 1);
            $price = (float) ($item['unit_price'] ?? $item['total_price'] ?? 0);
            $total = (float) ($item['total_price'] ?? ($qty * $price));

            if ($total <= 0 && $price <= 0) {
                continue;
            }

            ExpenseItem::query()->create([
                'expense_id' => $this->expense->id,
                'name' => $name,
                'qty' => $qty,
                'price' => $price,
                'total' => $total,
            ]);
        }
    }

    /**
     * Check for duplicate expenses (same vendor + amount + date).
     */
    protected function detectDuplicate(array $parsed): void
    {
        if (! $this->expense->organisation_id) {
            return;
        }

        $query = Expense::query()
            ->where('organisation_id', $this->expense->organisation_id)
            ->where('id', '!=', $this->expense->id)
            ->where('is_duplicate', false);

        // Check by invoice number first (strongest match)
        if (! empty($parsed['invoice_number'])) {
            $invoiceDupe = (clone $query)
                ->whereJsonContains('additional_fields->invoice_number', $parsed['invoice_number'])
                ->first();

            if ($invoiceDupe) {
                $this->expense->update([
                    'is_duplicate' => true,
                    'duplicate_of' => $invoiceDupe->id,
                ]);

                return;
            }
        }

        // Check by vendor + amount + date
        if ($parsed['total_amount'] > 0 && $this->expense->date) {
            $vendorDupe = (clone $query)
                ->where('name', $parsed['vendor_name'])
                ->where('amount', $parsed['total_amount'])
                ->where('date', $this->expense->date)
                ->first();

            if ($vendorDupe) {
                $this->expense->update([
                    'is_duplicate' => true,
                    'duplicate_of' => $vendorDupe->id,
                ]);
            }
        }
    }
}
