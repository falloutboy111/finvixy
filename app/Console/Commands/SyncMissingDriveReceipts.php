<?php

namespace App\Console\Commands;

use App\Jobs\SyncExpenseToDrive;
use App\Models\Expense;
use Illuminate\Console\Command;

class SyncMissingDriveReceipts extends Command
{
    protected $signature = 'app:sync-missing-drive-receipts
        {--org= : Limit to a specific organisation ID}';

    protected $description = 'Re-dispatch Drive sync for expenses that have a receipt but no drive_file_id';

    public function handle(): int
    {
        $query = Expense::query()
            ->whereNotNull('receipt_path')
            ->whereNull('drive_file_id');

        if ($orgId = $this->option('org')) {
            $query->where('organisation_id', $orgId);
        }

        $expenses = $query->get();

        if ($expenses->isEmpty()) {
            $this->info('No expenses found that need Drive sync.');

            return self::SUCCESS;
        }

        $this->info("Dispatching Drive sync for {$expenses->count()} expense(s)...");

        foreach ($expenses as $expense) {
            SyncExpenseToDrive::dispatch($expense);
        }

        $this->info('Done. Jobs have been queued.');

        return self::SUCCESS;
    }
}
