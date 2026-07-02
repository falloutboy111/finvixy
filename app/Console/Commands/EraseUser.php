<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\DataErasureService;
use Illuminate\Console\Command;

/**
 * Right-to-erasure (POPIA data-subject deletion) for a single user.
 *
 * Triggered manually by the owner. Deletes the user's data across every store
 * (CRM, S3, local DB) via DataErasureService, which is idempotent and logs the
 * action (not the erased data).
 */
class EraseUser extends Command
{
    protected $signature = 'finvixy:erase-user
        {email : The email address of the user to erase}
        {--force : Skip the confirmation prompt}';

    protected $description = 'Permanently erase all data for a user across CRM, S3 and the local database (POPIA erasure)';

    public function handle(DataErasureService $eraser): int
    {
        $user = User::with('organisation')
            ->where('email', $this->argument('email'))
            ->first();

        if (! $user) {
            $this->error("User not found: {$this->argument('email')}");

            return self::FAILURE;
        }

        $this->warn('This permanently erases ALL data for:');
        $this->line("  User: {$user->name} <{$user->email}> (id {$user->id})");
        $this->line('  Organisation: '.($user->organisation?->name ?? 'None'));
        $this->line('  Stores: CRM expenses, S3 receipts, expenses, conversations, webhooks, usage logs.');

        if (! $this->option('force')
            && ! $this->confirm('Erase this user? This cannot be undone.')) {
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        try {
            $summary = $eraser->eraseUser($user);
        } catch (\Throwable $e) {
            $this->error("Erasure failed: {$e->getMessage()}");

            return self::FAILURE;
        }

        foreach ($summary as $key => $count) {
            $this->line(sprintf('  %-24s %d', $key, $count));
        }

        $this->info('User erased.');

        return self::SUCCESS;
    }
}
