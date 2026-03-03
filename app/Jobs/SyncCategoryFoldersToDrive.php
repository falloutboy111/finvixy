<?php

namespace App\Jobs;

use App\Models\ConnectedAccount;
use App\Models\ExpenseCategory;
use App\Services\GoogleDriveService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncCategoryFoldersToDrive implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public int $timeout = 120;

    /**
     * Create a new job instance.
     *
     * @param  int  $organisationId  The organisation whose categories to sync.
     * @param  int|null  $userId  Specific user whose Drive to sync to, or null for all org users with Drive.
     */
    public function __construct(
        public int $organisationId,
        public ?int $userId = null,
    ) {
        $this->onQueue('default');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $accountsQuery = ConnectedAccount::query()
            ->where('provider', 'google_drive')
            ->where('is_active', true);

        if ($this->userId) {
            $accountsQuery->where('user_id', $this->userId);
        } else {
            $accountsQuery->where('organisation_id', $this->organisationId);
        }

        $accounts = $accountsQuery->get();

        if ($accounts->isEmpty()) {
            return;
        }

        $categories = ExpenseCategory::query()
            ->where('organisation_id', $this->organisationId)
            ->orderBy('sort_order')
            ->pluck('name')
            ->toArray();

        if (empty($categories)) {
            return;
        }

        foreach ($accounts as $account) {
            try {
                $organisationName = $account->organisation?->name ?? 'Organisation';
                $driveService = new GoogleDriveService($account, $organisationName);

                // Create a subfolder for each category
                foreach ($categories as $categoryName) {
                    $folderName = ucfirst(str_replace(['-', '_'], ' ', $categoryName));
                    $driveService->getOrCreateSubfolder($folderName);
                }

                $account->update(['last_sync_at' => now()]);

                Log::info('SyncCategoryFoldersToDrive: created category folders', [
                    'organisation_id' => $this->organisationId,
                    'user_id' => $account->user_id,
                    'categories' => count($categories),
                ]);
            } catch (\Exception $e) {
                Log::error('SyncCategoryFoldersToDrive: failed for account', [
                    'account_id' => $account->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
