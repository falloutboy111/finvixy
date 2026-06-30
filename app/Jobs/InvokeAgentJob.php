<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\WhatsappWebhook;
use App\Services\AgentCoreService;
use App\Services\AgentToolService;
use App\Services\ConfirmationService;
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
        public readonly bool $injectFullProjectList = false,
        public readonly bool $sendHoldingMessage = false,
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

        // Fire the typing indicator immediately — before invoking the agent —
        // so the user sees "typing…" within ~1s of sending their message.
        // Uses a 3s timeout and swallows all failures; never delays the reply.
        if ($webhook?->message_id) {
            $whatsApp->sendTypingIndicator($this->to, $webhook->message_id);
        }

        // For operations that may approach the 25s indicator limit (e.g. full
        // project-list fetch + CRM-name matching), send a short holding text
        // so the user has visible feedback if the indicator expires.
        if ($this->sendHoldingMessage) {
            $whatsApp->sendText($this->to, 'Working on it, give me a sec ⏳');
        }

        try {
            $prompt = $this->injectFullProjectList
                ? $this->withProjectList($this->prompt)
                : $this->prompt;

            $reply = $agent->invoke($prompt, $this->orgId, $this->userId);

            $confirmation = app(ConfirmationService::class);
            $action       = $confirmation->parseAction($reply);

            if ($action !== null) {
                $confirmation->sendActionMessage($this->to, $action, $whatsApp);
            } else {
                $whatsApp->sendText($this->to, $reply);
            }

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

    /**
     * Append the user's full CRM project list to the prompt so the agent can match
     * a typed project name without needing an extra get_projects tool call.
     * Falls back silently to the original prompt if the CRM is unavailable.
     */
    private function withProjectList(string $prompt): string
    {
        $user = User::find($this->userId);

        if (! $user?->crm_sync_enabled) {
            return $prompt;
        }

        try {
            $projects = app(AgentToolService::class)->allProjects($this->userId);
        } catch (\Throwable $e) {
            Log::warning('InvokeAgentJob: project list fetch failed — agent will call get_projects', [
                'user_id' => $this->userId,
                'error'   => $e->getMessage(),
            ]);

            return $prompt;
        }

        if (empty($projects)) {
            return $prompt;
        }

        $list = implode('; ', array_map(
            fn ($p) => "{$p['name']} (id:{$p['id']})",
            $projects,
        ));

        return $prompt." All available projects: {$list}.";
    }
}
