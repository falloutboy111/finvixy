<?php

namespace App\Jobs;

use App\Models\WhatsappWebhook;
use App\Services\AgentCoreService;
use App\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class InvokeAgentJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 45;

    public int $backoff = 10;

    public function __construct(
        public readonly string $to,
        public readonly string $prompt,
        public readonly int $orgId,
        public readonly int $userId,
        public readonly int $webhookId,
    ) {
        $this->onQueue('agent');
    }

    public function handle(AgentCoreService $agent, WhatsAppService $whatsApp): void
    {
        // Guard against double-send on retry: if a previous attempt already sent
        // the reply and only crashed after (e.g. DB update failed), bail out early.
        $webhook = WhatsappWebhook::find($this->webhookId);
        if ($webhook?->status === 'processed') {
            return;
        }

        try {
            $reply = $agent->invoke($this->prompt, $this->orgId, $this->userId);

            $whatsApp->sendText($this->to, $reply);

            $webhook?->update(['status' => 'processed']);
        } catch (\Throwable $e) {
            Log::error('InvokeAgentJob failed', [
                'webhook_id' => $this->webhookId,
                'user_id'    => $this->userId,
                'error'      => $e->getMessage(),
            ]);

            $whatsApp->sendText(
                $this->to,
                "Sorry, I couldn't process that right now. Please try again."
            );

            $webhook?->update([
                'status'        => 'failed',
                'error_message' => substr($e->getMessage(), 0, 500),
            ]);

            throw $e;
        }
    }
}
