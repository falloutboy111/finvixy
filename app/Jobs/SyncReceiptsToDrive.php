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
     * @param  bool  $force  When true, re-uploads every receipt regardless of existing
     *                       drive_file_id. Use after changing the folder name / path so
     *                       all receipts land in the new location.
     */
    public function __construct(
        public int $connectedAccountId,
        public int $userId,
        public bool $force = false,
    ) {
        $this->onQueue('default');
    }

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
                'error'      => $e->getMessage(),
                'account_id' => $this->connectedAccountId,
            ]);

            return;
        }

        $query = Expense::query()
            ->where('user_id', $this->userId)
            ->whereNotNull('receipt_path')
            ->where('receipt_path', '!=', '')
            ->orderBy('date');

        if (! $this->force) {
            $query->whereNull('drive_file_id');
        }

        $expenses = $query->get();

        Log::info('SyncReceiptsToDrive: starting sync', [
            'user_id'          => $this->userId,
            'force'            => $this->force,
            'expenses_to_sync' => $expenses->count(),
        ]);

        // Resolve root → custom path ONCE before the loop.
        // Month subfolders are cached per unique month to avoid redundant Drive API calls.
        $customFolderId = $account->settings['drive_folder_id'] ?? null;
        $customPath     = $account->settings['drive_folder_path'] ?? null;
        $rootFolderId   = $driveService->getOrCreateFolder($customFolderId);
        $pathRootId     = $customPath
            ? $driveService->navigatePath($rootFolderId, $customPath)
            : $rootFolderId;

        /** @var array<string, string> $monthFolderCache */
        $monthFolderCache = [];

        $synced = 0;
        $failed = 0;

        foreach ($expenses as $expense) {
            try {
                $fileContents = Storage::disk('org-storage')->get($expense->receipt_path);

                if (! $fileContents) {
                    Log::warning('SyncReceiptsToDrive: file not found in org-storage', [
                        'expense_id' => $expense->id,
                        'path'       => $expense->receipt_path,
                    ]);
                    $failed++;

                    continue;
                }

                $extension  = pathinfo($expense->receipt_path, PATHINFO_EXTENSION) ?: 'jpg';
                $vendorName = preg_replace('/[^a-zA-Z0-9]+/', '_', strtolower($expense->name ?? 'receipt'));
                $vendorName = trim($vendorName, '_');
                $dateStr    = $expense->date?->format('Y-m-d') ?? now()->format('Y-m-d');
                $filename   = "{$vendorName}_{$dateStr}.{$extension}";

                $mimeType = match (strtolower($extension)) {
                    'pdf'  => 'application/pdf',
                    'png'  => 'image/png',
                    'gif'  => 'image/gif',
                    default => 'image/jpeg',
                };

                $monthKey = ($expense->date ?? now())->format('Y-m');
                if (! isset($monthFolderCache[$monthKey])) {
                    $monthFolderCache[$monthKey] = $driveService->navigatePath($pathRootId, $monthKey);
                }
                $targetFolderId = $monthFolderCache[$monthKey];

                $result = $driveService->uploadFileToFolder($filename, $fileContents, $mimeType, $targetFolderId);

                $expense->update([
                    'drive_file_id'   => $result['id'],
                    'drive_web_link'  => $result['webViewLink'],
                ]);

                $synced++;
            } catch (\Exception $e) {
                Log::error('SyncReceiptsToDrive: failed to upload expense', [
                    'expense_id' => $expense->id,
                    'error'      => $e->getMessage(),
                ]);
                $failed++;
            }
        }

        $account->update(['last_sync_at' => now()]);

        Log::info('SyncReceiptsToDrive: completed', [
            'user_id' => $this->userId,
            'synced'  => $synced,
            'failed'  => $failed,
            'total'   => $expenses->count(),
        ]);
    }
}
