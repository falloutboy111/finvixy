<?php

namespace App\Jobs;

use App\Mail\QuotaExceededMail;
use App\Models\Expense;
use App\Models\ExpenseItem;
use App\Services\BedrockAgentService;
use App\Services\BudgetService;
use App\Services\PlanLimitService;
use App\Services\TextractService;
use App\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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

            // Notify via WhatsApp if applicable
            SendWhatsAppExpenseResult::dispatch($this->expense->fresh());

            throw $e;
        }
    }

    /**
     * Process an image file (JPEG, PNG, WEBP) synchronously via Textract.
     */
    protected function processImage(TextractService $textractService, BedrockAgentService $bedrockService): void
    {
        $fileContents = Storage::disk('org-storage')->get($this->expense->receipt_path);

        if (! $fileContents) {
            throw new \RuntimeException("Could not read file from S3: {$this->expense->receipt_path}");
        }

        $ocrText = $textractService->detectText($fileContents);

        Log::info('Textract OCR output', [
            'expense_id' => $this->expense->id,
            'text_length' => strlen($ocrText),
            'ocr_text' => $ocrText,
        ]);

        $this->parseAndUpdateExpense($ocrText, $bedrockService);
    }

    /**
     * Process a PDF document via async Textract.
     */
    protected function processPdf(TextractService $textractService, BedrockAgentService $bedrockService): void
    {
        $bucket = config('filesystems.disks.org-storage.bucket');
        $root = rtrim(config('filesystems.disks.org-storage.root', ''), '/');
        $key = $root ? $root.'/'.$this->expense->receipt_path : $this->expense->receipt_path;

        $jobId = $textractService->startAsyncDetection($bucket, $key);

        // Poll for result (max 10 minutes with 30s intervals)
        $maxAttempts = 20;
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            sleep(30);
            $attempt++;

            $result = $textractService->getAsyncResult($jobId);

            if ($result['status'] === 'SUCCEEDED') {
                Log::info('Textract PDF OCR output', [
                    'expense_id' => $this->expense->id,
                    'text_length' => strlen($result['text'] ?? ''),
                    'ocr_text' => $result['text'] ?? '',
                ]);

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

        $parsed = $bedrockService->parseExpenseDocument($ocrText, $this->expense->organisation_id, $this->expense->user_id);

        Log::info('Bedrock AI parsed response', [
            'expense_id' => $this->expense->id,
            'parsed_data' => $parsed,
        ]);

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

        // Check budget limits and send alerts if exceeded
        $this->checkBudgetAndAlert();

        // Check receipt quota and send notification if exceeded
        $this->checkQuotaAndNotify();

        // Auto-sync to Google Drive if connected
        SyncExpenseToDrive::dispatch($this->expense);

        // Notify via WhatsApp if the receipt came from WhatsApp
        SendWhatsAppExpenseResult::dispatch($this->expense->fresh());

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

    /**
     * Check budget and send WhatsApp alert if exceeded.
     */
    protected function checkBudgetAndAlert(): void
    {
        if (! $this->expense->organisation_id || ! $this->expense->user_id) {
            return;
        }

        $budgetService = app(BudgetService::class);
        $alert = $budgetService->checkExpenseBudget($this->expense);

        if ($alert && $alert['exceeded']) {
            $user = $this->expense->user;
            if ($user && $user->whatsapp_number && $user->whatsapp_enabled) {
                $this->sendBudgetAlert($user, $alert);
            }
        }
    }

    /**
     * Send budget alert via WhatsApp.
     */
    protected function sendBudgetAlert($user, array $alert): void
    {
        $message = sprintf(
            "⚠️ Budget Alert!\n".
            "Your %s - %s budget exceeded.\n\n".
            "Budget: R%.2f/month\n".
            "Used: R%.2f (+R%.2f over)\n".
            'This receipt: R%.2f',
            $alert['vendor_name'],
            $alert['category'] ?? 'General',
            $alert['budget_limit'],
            $alert['current_month_spent'],
            $alert['overage'],
            $alert['expense_amount']
        );

        try {
            app(WhatsAppService::class)->sendMessage($user->whatsapp_number, $message);
        } catch (\Throwable $e) {
            Log::warning('Failed to send budget alert via WhatsApp', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check quota and send notification if exceeded.
     */
    protected function checkQuotaAndNotify(): void
    {
        if (! $this->expense->user_id) {
            return;
        }

        $user = $this->expense->user;
        if (! $user) {
            return;
        }

        $limitService = app(PlanLimitService::class);
        $limit = $limitService->checkReceiptLimit($user, 0); // Don't count this one again

        // Only send alert if user is now at 100% of quota
        if ($limit['allowed'] === false && is_numeric($limit['limit'])) {
            // Send email
            try {
                Mail::to($user->email)->send(new QuotaExceededMail(
                    $user,
                    $limit['used'],
                    $limit['limit'],
                    $user->organisation
                ));

                Log::info('Quota exceeded email sent', [
                    'user_id' => $user->id,
                    'used' => $limit['used'],
                    'limit' => $limit['limit'],
                ]);
            } catch (\Throwable $e) {
                Log::warning('Failed to send quota exceeded email', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Send WhatsApp alert (optional)
            if ($user->whatsapp_enabled && $user->whatsapp_number) {
                $this->sendQuotaAlert($user, $limit);
            }
        }
    }

    /**
     * Send quota exceeded alert via WhatsApp.
     */
    protected function sendQuotaAlert($user, array $limit): void
    {
        $message = sprintf(
            "⚠️ Monthly receipt limit reached (%d/%d).\n".
            'Upgrade: '.config('app.url').'/settings/billing',
            $limit['used'],
            $limit['limit']
        );

        try {
            app(WhatsAppService::class)->sendMessage($user->whatsapp_number, $message);
        } catch (\Throwable $e) {
            Log::warning('Failed to send quota alert via WhatsApp', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
