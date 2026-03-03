<?php

namespace App\Jobs;

use App\Models\Expense;
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

    public function handle(WhatsAppService $whatsApp): void
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
}
