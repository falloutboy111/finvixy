<?php

namespace App\Jobs;

use App\Models\AiUsageLog;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ClearAgentSessions implements ShouldQueue
{
    use Queueable;

    public function handle(WhatsAppService $whatsApp): void
    {
        // Find users who had a successful agent call in the PREVIOUS 10-minute window
        // (10–20 min ago). Their session has just been cleared by the bucket rollover.
        $windowEnd   = now()->subMinutes(10);
        $windowStart = now()->subMinutes(20);

        $userIds = AiUsageLog::where('service_type', 'bedrock_agent')
            ->where('success', true)
            ->whereBetween('created_at', [$windowStart, $windowEnd])
            ->distinct()
            ->pluck('user_id');

        if ($userIds->isEmpty()) {
            return;
        }

        $users = User::whereIn('id', $userIds)
            ->where('whatsapp_enabled', true)
            ->whereNotNull('whatsapp_number')
            ->get(['id', 'whatsapp_number']);

        foreach ($users as $user) {
            $to = ltrim($user->whatsapp_number, '+');

            $sent = $whatsApp->sendText(
                $to,
                "🔄 Your Finvixy conversation has been cleared. Start fresh whenever you're ready!",
            );

            Log::info('ClearAgentSessions: session-cleared notification sent', [
                'user_id' => $user->id,
                'sent'    => $sent,
            ]);
        }
    }
}
