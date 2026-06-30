<?php

namespace App\Jobs;

use App\Models\Expense;
use App\Services\AgentCoreService;
use App\Services\ConfirmationService;
use App\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendWhatsAppExpenseResult implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 30;

    public function __construct(
        public Expense $expense,
    ) {
        $this->queue = 'default';
    }

    public function handle(WhatsAppService $whatsApp, AgentCoreService $agentCore): void
    {
        $fields = $this->expense->additional_fields ?? [];

        // Only send for WhatsApp-sourced expenses
        if (($fields['source'] ?? '') !== 'whatsapp') {
            return;
        }

        $to = $fields['whatsapp_from'] ?? null;

        if (! $to) {
            return;
        }

        $expense = $this->expense;

        if ($expense->status === 'processed') {
            $currency = $fields['currency'] ?? 'R';
            $amount = number_format($expense->amount, 2);
            $date = $expense->date?->format('d M Y') ?? 'N/A';
            $category = $expense->category ? ucwords(str_replace('-', ' ', $expense->category)) : 'Uncategorised';

            $lines = [
                "🧾 *{$expense->name}*",
                '',
                "💰 Amount: {$currency}{$amount}",
                "📅 Date: {$date}",
                "🏷️ Category: {$category}",
            ];

            if ($expense->tax && $expense->tax > 0) {
                $lines[] = "🧮 Tax: {$currency}".number_format($expense->tax, 2);
            }

            if (! empty($fields['invoice_number'])) {
                $lines[] = "📄 Invoice: {$fields['invoice_number']}";
            }

            $itemCount = $expense->expenseItems()->count();
            if ($itemCount > 0) {
                $lines[] = "📋 {$itemCount} line item(s)";
            }

            if ($expense->is_duplicate) {
                $lines[] = '';
                $lines[] = '⚠️ Possible duplicate detected';
            }

            $lines[] = '';
            $lines[] = 'View details at '.config('app.url').'/expenses';

            $whatsApp->sendMessage($to, implode("\n", $lines));

            $user = $expense->user;

            if ($user && $user->crm_sync_enabled) {
                // Seed the agent session silently, then send a deterministic
                // interactive project picker — no LLM round-trip needed here.
                $this->seedAgentSession($expense, $agentCore);

                try {
                    app(ConfirmationService::class)->sendProactiveProjectPicker($expense, $to, $whatsApp);
                } catch (\Throwable $e) {
                    Log::warning('SendWhatsAppExpenseResult: project picker failed', [
                        'expense_id' => $expense->id,
                        'error'      => $e->getMessage(),
                    ]);
                }
            } else {
                // Standard seed — response discarded, just primes the session.
                $this->seedAgentSession($expense, $agentCore);
            }

        } elseif ($expense->status === 'failed') {
            $error = $fields['error'] ?? 'Unknown error';
            $whatsApp->sendMessage(
                $to,
                "❌ Sorry, I couldn't read that receipt.\n\n_{$error}_\n\nPlease try sending a clearer photo."
            );
        }

        Log::info('WhatsApp expense result sent', [
            'expense_id' => $expense->id,
            'to' => $to,
            'status' => $expense->status,
        ]);
    }

    /**
     * Inject the processed receipt into the agent's conversation session.
     * The agent's response is discarded — only the exchange history matters,
     * so that follow-up questions ("what did I just buy?") work correctly.
     */
    private function seedAgentSession(Expense $expense, AgentCoreService $agentCore): void
    {
        $user = $expense->user;

        if (! $user || ! $expense->organisation_id) {
            return;
        }

        try {
            $agentCore->invoke(
                implode("\n", $this->buildReceiptSeedLines($expense)),
                $expense->organisation_id,
                $user->id,
                $expense->id,
                true, // pin so follow-up questions have receipt context
            );

            Log::info('Agent session seeded with receipt', [
                'expense_id' => $expense->id,
                'user_id'    => $user->id,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to seed agent session with receipt', [
                'expense_id' => $expense->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    /** @return list<string> */
    private function buildReceiptSeedLines(Expense $expense): array
    {
        $lines = [
            '[Receipt scanned] Your receipt has been processed:',
            'Store: '.$expense->name,
            'Amount: R'.number_format((float) $expense->amount, 2),
            'Date: '.($expense->date?->format('d M Y') ?? 'unknown'),
            'Category: '.($expense->category ?? 'uncategorised'),
            'Expense ID: '.$expense->id,
        ];

        $items = $expense->expenseItems()->get();
        if ($items->isNotEmpty()) {
            $lines[] = 'Items:';
            foreach ($items as $item) {
                $lines[] = '  - '.$item->name.' (×'.((float) $item->qty).') R'.number_format((float) $item->total, 2);
            }
        }

        if ($expense->is_duplicate) {
            $lines[] = 'Note: possible duplicate of an existing expense.';
        }

        return $lines;
    }
}
