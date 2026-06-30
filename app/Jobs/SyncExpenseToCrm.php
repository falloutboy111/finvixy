<?php

namespace App\Jobs;

use App\Models\Expense;
use App\Services\EnclivixCrmService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncExpenseToCrm implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(public Expense $expense) {}

    public function handle(EnclivixCrmService $crm): void
    {
        $expense = $this->expense->fresh();

        if (! $expense) {
            return;
        }

        $user = $expense->user;

        if (! $user || ! $user->crm_sync_enabled) {
            return;
        }

        $crmId = $crm->postExpense($expense);

        $expense->update([
            'crm_expense_id' => $crmId,
            'crm_synced_at'  => now(),
        ]);

        Log::info('SyncExpenseToCrm: pushed', [
            'expense_id'     => $expense->id,
            'crm_expense_id' => $crmId,
        ]);
    }
}
