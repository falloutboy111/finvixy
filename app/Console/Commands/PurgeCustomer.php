<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PurgeCustomer extends Command
{
    protected $signature = 'app:purge-customer
        {email : The user email address}
        {--force : Skip confirmation prompt}';

    protected $description = 'Permanently delete a customer and all their data including S3 files';

    public function handle(): int
    {
        $user = User::query()
            ->with(['organisation', 'expenses', 'connectedAccounts'])
            ->where('email', $this->argument('email'))
            ->first();

        if (! $user) {
            $this->error("User not found: {$this->argument('email')}");

            return self::FAILURE;
        }

        $organisation = $user->organisation;
        $expenseCount = $user->expenses->count();
        $s3Files = $user->expenses->pluck('receipt_path')->filter()->values();

        $this->warn('This will permanently delete:');
        $this->line("  User: {$user->name} ({$user->email})");
        $this->line("  Organisation: ".($organisation?->name ?? 'None'));
        $this->line("  Expenses: {$expenseCount}");
        $this->line("  S3 files: {$s3Files->count()}");
        $this->line("  Connected accounts: {$user->connectedAccounts->count()}");

        if (! $this->option('force') && ! $this->confirm('Are you sure you want to purge this customer? This cannot be undone.')) {
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        DB::beginTransaction();

        try {
            // Delete S3 receipt files
            if ($s3Files->isNotEmpty()) {
                $this->info("Deleting {$s3Files->count()} files from S3...");
                $deleted = 0;
                foreach ($s3Files as $path) {
                    try {
                        Storage::disk('s3')->delete($path);
                        $deleted++;
                    } catch (\Throwable $e) {
                        $this->warn("  Skipped S3 file (not found or error): {$path}");
                    }
                }
                $this->info("  Deleted {$deleted}/{$s3Files->count()} S3 files.");
            }

            // Delete expense items (line items)
            $expenseIds = $user->expenses->pluck('id');
            if ($expenseIds->isNotEmpty()) {
                \App\Models\ExpenseItem::query()->whereIn('expense_id', $expenseIds)->delete();
            }

            // Delete AI usage logs
            \App\Models\AiUsageLog::query()->where('user_id', $user->id)->delete();

            // Delete WhatsApp webhooks
            \App\Models\WhatsappWebhook::query()->where('user_id', $user->id)->delete();

            // Delete expenses
            $user->expenses()->delete();

            // Delete connected accounts
            $user->connectedAccounts()->delete();

            // Delete email OTPs
            \App\Models\EmailOtp::query()->where('user_id', $user->id)->delete();

            // Delete the user
            $user->delete();

            // If they were the last user in the organisation, delete the org too
            if ($organisation) {
                $remainingUsers = User::query()->where('organisation_id', $organisation->id)->count();

                if ($remainingUsers === 0) {
                    $this->info("Deleting organisation: {$organisation->name} (no remaining users)");
                    $organisation->expenseCategories()->delete();
                    $organisation->forceDelete();
                }
            }

            DB::commit();

            Log::info('Customer purged', [
                'email' => $this->argument('email'),
                'expenses_deleted' => $expenseCount,
                's3_files_deleted' => $s3Files->count(),
            ]);

            $this->info('Customer purged successfully.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("Purge failed: {$e->getMessage()}");
            Log::error('Customer purge failed', [
                'email' => $this->argument('email'),
                'error' => $e->getMessage(),
            ]);

            return self::FAILURE;
        }
    }
}
