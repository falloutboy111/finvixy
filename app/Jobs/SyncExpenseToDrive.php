<?php

namespace App\Jobs;

use App\Models\ConnectedAccount;
use App\Models\Expense;
use App\Services\GoogleDriveService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SyncExpenseToDrive implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Expense $expense,
    ) {
        $this->onQueue('default');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Skip if already synced or no receipt
        if ($this->expense->drive_file_id || ! $this->expense->receipt_path) {
            return;
        }

        // Find an active Google Drive connected account for this user
        $account = ConnectedAccount::query()
            ->where('user_id', $this->expense->user_id)
            ->where('provider', 'google_drive')
            ->where('is_active', true)
            ->first();

        if (! $account) {
            return;
        }

        $organisationName = $account->organisation?->name ?? 'Organisation';

        try {
            $driveService = new GoogleDriveService($account, $organisationName);

            $fileContents = Storage::disk('org-storage')->get($this->expense->receipt_path);

            if (! $fileContents) {
                Log::warning('SyncExpenseToDrive: file not found in org-storage', [
                    'expense_id' => $this->expense->id,
                    'path' => $this->expense->receipt_path,
                ]);

                return;
            }

            $extension = pathinfo($this->expense->receipt_path, PATHINFO_EXTENSION) ?: 'jpg';
            $vendorName = preg_replace('/[^a-zA-Z0-9]+/', '_', strtolower($this->expense->name ?? 'receipt'));
            $vendorName = trim($vendorName, '_');
            $dateStr = $this->expense->date?->format('Y-m-d') ?? now()->format('Y-m-d');
            $filename = "{$vendorName}_{$dateStr}.{$extension}";

            $mimeType = match (strtolower($extension)) {
                'pdf' => 'application/pdf',
                'png' => 'image/png',
                'gif' => 'image/gif',
                default => 'image/jpeg',
            };

            // File into monthly subfolder (e.g. OrgName-finvixy/2026-04/vendor_2026-04-15.jpg)
            $monthFolder = ($this->expense->date ?? now())->format('Y-m');

            $result = $driveService->uploadFile($filename, $fileContents, $mimeType, $monthFolder);

            $this->expense->update([
                'drive_file_id' => $result['id'],
                'drive_web_link' => $result['webViewLink'],
            ]);

            $account->update(['last_sync_at' => now()]);

            Log::info('SyncExpenseToDrive: uploaded to Drive', [
                'expense_id' => $this->expense->id,
                'drive_file_id' => $result['id'],
            ]);

        } catch (\Exception $e) {
            Log::error('SyncExpenseToDrive: failed', [
                'expense_id' => $this->expense->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
