<?php

namespace App\Services;

use App\Models\AgentConversation;
use App\Models\AiUsageLog;
use App\Models\EmailOtp;
use App\Models\Expense;
use App\Models\ExpenseItem;
use App\Models\PendingConfirmation;
use App\Models\PriceLookupUsage;
use App\Models\User;
use App\Models\WhatsappWebhook;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Complete, POPIA-aligned right-to-erasure for a single user.
 *
 * Deletes every store that holds the user's personal data:
 *  - CRM finvixy_expenses (via the Finvixy → CRM API)
 *  - raw receipt objects in S3
 *  - local expenses + line items (force-deleted past SoftDeletes)
 *  - agent conversation history, WhatsApp webhook rows, AI usage logs,
 *    price-lookup counters, pending confirmations, email OTPs
 *  - connected Google/Xero accounts
 *  - the user record itself, and the organisation if now empty
 *
 * Idempotent (safe to re-run) and logged — the log records the action and
 * counts, never the erased content.
 */
class DataErasureService
{
    public function __construct(
        private readonly EnclivixCrmService $crm,
    ) {}

    /**
     * Erase all data for the given user. Returns a summary of what was removed.
     *
     * @return array<string, int>
     */
    public function eraseUser(User $user): array
    {
        // Include soft-deleted expenses so erasure is truly complete.
        $expenses = Expense::withTrashed()
            ->where('user_id', $user->id)
            ->get(['id', 'receipt_path', 'image_path', 'crm_expense_id']);

        $expenseIds  = $expenses->pluck('id');
        $summary = [
            'crm_expenses_deleted' => 0,
            's3_objects_deleted'   => 0,
            'expenses_deleted'     => $expenses->count(),
            'items_deleted'        => 0,
            'conversations_deleted'=> 0,
            'webhooks_deleted'     => 0,
            'usage_logs_deleted'   => 0,
            'price_lookups_deleted'=> 0,
        ];

        // 1. CRM records (outside the DB transaction — external system).
        foreach ($expenses as $expense) {
            if (! empty($expense->crm_expense_id)) {
                try {
                    $this->crm->deleteExpense((string) $expense->crm_expense_id);
                    $summary['crm_expenses_deleted']++;
                } catch (\Throwable $e) {
                    Log::warning('DataErasure: CRM expense delete failed', [
                        'user_id'        => $user->id,
                        'crm_expense_id' => $expense->crm_expense_id,
                        'error'          => $e->getMessage(),
                    ]);
                    // Continue — a CRM hiccup must not block local erasure.
                }
            }
        }

        // 2. S3 receipt objects (outside the transaction — external system).
        $paths = $expenses
            ->flatMap(fn ($e) => [$e->receipt_path, $e->image_path])
            ->filter()
            ->unique();

        foreach ($paths as $path) {
            try {
                if ($user->organisation) {
                    (new OrgStorageService($user->organisation))->delete($path);
                    $summary['s3_objects_deleted']++;
                }
            } catch (\Throwable $e) {
                Log::warning('DataErasure: S3 object delete failed', [
                    'user_id' => $user->id,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        // 3. Local database — atomic.
        DB::transaction(function () use ($user, $expenseIds, &$summary) {
            if ($expenseIds->isNotEmpty()) {
                $summary['items_deleted'] = ExpenseItem::withTrashed()
                    ->whereIn('expense_id', $expenseIds)->forceDelete();
            }

            $summary['conversations_deleted'] = AgentConversation::where('user_id', $user->id)->delete();
            $summary['webhooks_deleted']      = WhatsappWebhook::where('user_id', $user->id)->delete();
            $summary['usage_logs_deleted']    = AiUsageLog::where('user_id', $user->id)->delete();
            $summary['price_lookups_deleted'] = PriceLookupUsage::where('user_id', $user->id)->delete();

            PendingConfirmation::where('user_id', $user->id)->delete();
            EmailOtp::where('user_id', $user->id)->delete();

            Expense::withTrashed()->where('user_id', $user->id)->forceDelete();
            $user->connectedAccounts()->delete();

            $organisation = $user->organisation;
            $user->forceDelete();

            // Drop the organisation only if this was its last member.
            if ($organisation
                && User::where('organisation_id', $organisation->id)->count() === 0) {
                $organisation->expenseCategories()->delete();
                $organisation->forceDelete();
            }
        });

        Log::info('DataErasure: user erased', array_merge(
            ['user_id' => $user->id, 'organisation_id' => $user->organisation_id],
            $summary,
        ));

        return $summary;
    }
}
