<?php

namespace App\Console\Commands;

use App\Models\Expense;
use App\Models\Organisation;
use App\Models\WhatsappWebhook;
use App\Services\OrgStorageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Retention pruning of raw source artifacts.
 *
 * Removes raw receipt images from S3 and clears stored WhatsApp webhook
 * payloads older than the retention window. The derived expense records are
 * KEPT — only the raw source artifacts and webhook payloads are pruned.
 *
 * Scheduleable but NOT scheduled by default; run manually or wire into the
 * scheduler once the owner has confirmed the retention window.
 */
class PruneReceipts extends Command
{
    protected $signature = 'finvixy:prune-receipts
        {--days= : Override RETENTION_DAYS (config services.retention.days)}
        {--dry-run : Report what would be pruned without deleting}
        {--force : Skip the confirmation prompt}';

    protected $description = 'Prune raw receipt images (S3) and WhatsApp webhook payloads older than the retention window; keeps expense records';

    public function handle(): int
    {
        $days   = (int) ($this->option('days') ?: config('services.retention.days', 90));
        $dryRun = (bool) $this->option('dry-run');
        $cutoff = now()->subDays($days);

        if ($days < 1) {
            $this->error('Retention window must be at least 1 day.');

            return self::FAILURE;
        }

        $expenseQuery = Expense::withTrashed()
            ->where('created_at', '<', $cutoff)
            ->where(function ($q) {
                $q->whereNotNull('receipt_path')->orWhereNotNull('image_path');
            });

        $webhookQuery = WhatsappWebhook::where('created_at', '<', $cutoff)
            ->whereNotNull('payload');

        $expenseCount = (clone $expenseQuery)->count();
        $webhookCount = (clone $webhookQuery)->count();

        $this->line("Retention window: {$days} days (cutoff {$cutoff->toDateString()})");
        $this->line("  Expenses with raw receipts to prune: {$expenseCount}");
        $this->line("  WhatsApp webhook payloads to clear:   {$webhookCount}");

        if ($dryRun) {
            $this->info('Dry run — nothing deleted.');

            return self::SUCCESS;
        }

        if (! $this->option('force')
            && ! $this->confirm('Prune these raw artifacts? Expense records are kept.')) {
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        $s3Deleted = 0;

        // Group by organisation so each org's storage disk/prefix is used correctly.
        $expenseQuery->select(['id', 'organisation_id', 'receipt_path', 'image_path'])
            ->chunkById(200, function ($expenses) use (&$s3Deleted) {
                $orgs = Organisation::whereIn('id', $expenses->pluck('organisation_id')->unique())
                    ->get()->keyBy('id');

                foreach ($expenses as $expense) {
                    $org = $orgs->get($expense->organisation_id);
                    if (! $org) {
                        continue;
                    }
                    $storage = new OrgStorageService($org);

                    foreach (array_filter([$expense->receipt_path, $expense->image_path]) as $path) {
                        try {
                            $storage->delete($path);
                            $s3Deleted++;
                        } catch (\Throwable $e) {
                            Log::warning('PruneReceipts: S3 delete failed', [
                                'expense_id' => $expense->id,
                                'error'      => $e->getMessage(),
                            ]);
                        }
                    }

                    // Keep the expense; drop only the raw-artifact pointers.
                    Expense::withTrashed()->whereKey($expense->id)
                        ->update(['receipt_path' => null, 'image_path' => null]);
                }
            });

        // Clear webhook payloads but keep the row (message_id dedup + status audit).
        $webhooksCleared = $webhookQuery->update(['payload' => null]);

        Log::info('PruneReceipts: completed', [
            'days'             => $days,
            's3_deleted'       => $s3Deleted,
            'webhooks_cleared' => $webhooksCleared,
        ]);

        $this->info("Pruned {$s3Deleted} S3 objects; cleared {$webhooksCleared} webhook payloads.");

        return self::SUCCESS;
    }
}
