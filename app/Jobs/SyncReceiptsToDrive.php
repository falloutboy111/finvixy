<?php

namespace App\Jobs;

use App\Models\ConnectedAccount;
use App\Models\Expense;
use App\Services\GoogleDriveService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SyncReceiptsToDrive implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 600;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $connectedAccountId,
        public int $userId,
    ) {
        $this->onQueue('default');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $account = ConnectedAccount::find($this->connectedAccountId);

        if (! $account || ! $account->is_active) {
            Log::warning('SyncReceiptsToDrive: account not found or inactive', [
                'account_id' => $this->connectedAccountId,
            ]);

            return;
        }

        $organisationName = $account->organisation?->name ?? 'Organisation';

        try {
            $driveService = new GoogleDriveService($account, $organisationName);
        } catch (\Exception $e) {
            Log::error('SyncReceiptsToDrive: failed to init Drive service', [
                'error' => $e->getMessage(),
                'account_id' => $this->connectedAccountId,
            ]);

            return;
        }

        // Get all expenses with receipt files that haven't been synced yet
        $expenses = Expense::query()
            ->where('user_id', $this->userId)
            ->whereNotNull('receipt_path')
            ->where('receipt_path', '!=', '')
            ->whereNull('drive_file_id')
            ->orderBy('date')
            ->get();

        Log::info('SyncReceiptsToDrive: starting sync', [
            'user_id' => $this->userId,
            'expenses_to_sync' => $expenses->count(),
        ]);

        $synced = 0;
        $failed = 0;

        foreach ($expenses as $expense) {
            try {
                $fileContents = Storage::disk('s3')->get($expense->receipt_path);

                if (! $fileContents) {
                    Log::warning('SyncReceiptsToDrive: file not found in S3', [
                        'expense_id' => $expense->id,
                        'path' => $expense->receipt_path,
                    ]);
                    $failed++;

                    continue;
                }

                $extension = pathinfo($expense->receipt_path, PATHINFO_EXTENSION) ?: 'jpg';
                $filename = ($expense->name ?? 'receipt').'-'.($expense->date?->format('Y-m-d') ?? $expense->id).'.'.$extension;
                $filename = preg_replace('/[^a-zA-Z0-9._\-]/', '_', $filename);

                $mimeType = match (strtolower($extension)) {
                    'pdf' => 'application/pdf',
                    'png' => 'image/png',
                    'gif' => 'image/gif',
                    default => 'image/jpeg',
                };

                // Upload to "receipts" subfolder with date-based organization
                $dateFolder = $expense->date?->format('Y/m');
                $result = $driveService->uploadFile($filename, $fileContents, $mimeType, 'receipts');

                $expense->update([
                    'drive_file_id' => $result['id'],
                    'drive_web_link' => $result['webViewLink'],
                ]);

                $synced++;

            } catch (\Exception $e) {
                Log::error('SyncReceiptsToDrive: failed to upload expense', [
                    'expense_id' => $expense->id,
                    'error' => $e->getMessage(),
                ]);
                $failed++;
            }
        }

        // Update last_sync_at on the connected account
        $account->update(['last_sync_at' => now()]);

        Log::info('SyncReceiptsToDrive: completed', [
            'user_id' => $this->userId,
            'synced' => $synced,
            'failed' => $failed,
            'total' => $expenses->count(),
        ]);
    }
}
