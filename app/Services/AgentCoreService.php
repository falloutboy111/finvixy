<?php

namespace App\Services;

use App\Models\AgentConversation;
use App\Models\AgentInvocationUsage;
use App\Models\AgentSession;
use App\Models\AiUsageLog;
use Aws\BedrockAgentCore\BedrockAgentCoreClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AgentCoreService
{
    private const TIMEOUT = 60;

    /**
     * Invoke the AgentCore runtime and return the agent's reply text.
     *
     * Every call gets a fresh Python session ID so the agent never accumulates
     * conversation history server-side. Instead, Laravel manages a windowed
     * history that is prepended to the prompt before each invocation — giving
     * the agent context without unbounded token growth.
     *
     * @param  ?int  $expenseId  When set, both turns are stored as pinned so
     *                           they survive the idle-reset window (receipt follow-ups).
     * @param  bool  $isPinned   Callers that seed a receipt context pass true.
     */
    public function invoke(
        string $prompt,
        int $orgId,
        int $userId,
        ?int $expenseId = null,
        bool $isPinned = false,
    ): string {
        $startTime = microtime(true);
        $sessionId = $this->freshSessionId($userId);

        $agentPrompt = $this->buildWindowedPrompt($prompt, $userId);

        $payload = [
            'organisation_id' => (string) $orgId,
            'user_id'         => (string) $userId,
            'session_id'      => $sessionId,
            'prompt'          => $agentPrompt,
            // Stable SA retailer/product knowledge, appended to the agent's
            // system prompt behind the Bedrock prompt-cache checkpoint. Bytes
            // only change when the DB block's version changes, so it caches.
            'knowledge'       => app(RetailKnowledgeService::class)->promptBlock(),
        ];

        try {
            $rawBody = $this->sendRequest($sessionId, $payload);

            $decoded = json_decode($rawBody, true);
            $text    = is_array($decoded) && isset($decoded['reply'])
                ? trim((string) $decoded['reply'])
                : trim($rawBody);

            $usage = is_array($decoded) && isset($decoded['usage']) ? (array) $decoded['usage'] : [];

            // Prefer the model id the agent actually ran (returned in the
            // envelope since the Sonnet-switch build). AGENT_MODEL_NAME is
            // only the fallback for older agent deploys that don't report it.
            $modelName = is_array($decoded) && ! empty($decoded['model'])
                ? (string) $decoded['model']
                : null;

            Log::info('AgentCore response received', [
                'org_id'        => $orgId,
                'user_id'       => $userId,
                'mode'          => config('services.agentcore.mode', 'local'),
                'length'        => strlen($text),
                'input_tokens'  => $usage['input_tokens'] ?? null,
                'output_tokens' => $usage['output_tokens'] ?? null,
            ]);

            // Store both turns with the RAW prompt (not the augmented version),
            // so rebuilt windows stay clean with no nested history.
            $this->storeMessage($userId, $orgId, 'user',      $prompt, $isPinned, $expenseId);
            $this->storeMessage($userId, $orgId, 'assistant', $text,   $isPinned, $expenseId);

            $this->logUsage($startTime, $orgId, $userId, true, null, $usage, $modelName);

            // Count this real run against the monthly invocation cap and mark
            // the session active with a completed exchange (sweeper skips
            // sessions that never had one). Cap rejections never reach here.
            AgentInvocationUsage::recordInvocation($orgId, $userId);
            AgentSession::touchActivity($userId, $orgId, isExchange: true);

            return $text ?: "Sorry, I couldn't process that right now. Please try again.";
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->logUsage($startTime, $orgId, $userId, false, $e->getMessage());
            throw $e;
        }
    }

    // -------------------------------------------------------------------------
    // Session ID — one unique ID per call keeps the agent stateless (Python)
    // -------------------------------------------------------------------------

    private function freshSessionId(int $userId): string
    {
        // Strip hyphens from UUID to keep the ID compact; pad to the ≥33 char minimum.
        $unique = str_replace('-', '', (string) Str::uuid());

        return str_pad('wa-'.$userId.'-'.$unique, 33, '0');
    }

    // -------------------------------------------------------------------------
    // Windowed history — Task 1 / Task 3
    // -------------------------------------------------------------------------

    /**
     * Prepend the last N turns from the conversation history to $prompt.
     *
     * Rules:
     *  - If there has been no activity for > AGENT_SESSION_IDLE_RESET hours,
     *    the window is empty (fresh start).
     *  - Pinned messages (receipt seeds) are fetched back an extra 24 h so a
     *    receipt context is still present even if the user was slow to reply.
     *  - Last AGENT_HISTORY_TURNS turn-pairs (user+assistant) fill the window.
     */
    private function buildWindowedPrompt(string $prompt, int $userId): string
    {
        $turns     = (int) config('services.agentcore.history_turns', 8);
        $idleHours = (int) config('services.agentcore.idle_reset_hours', 2);
        $idleCutoff = now()->subHours($idleHours);

        // Idle-reset check: if the most recent message is older than the idle
        // threshold, start fresh — stale context shouldn't ride along.
        $lastAt = AgentConversation::where('user_id', $userId)
            ->latest('created_at')
            ->value('created_at');

        if (! $lastAt || $lastAt < $idleCutoff) {
            return $prompt;
        }

        // Fetch recent unpinned messages within the idle window, plus any
        // pinned messages within the last 24 h (to capture receipt seeds).
        $pinnedCutoff = now()->subHours(24);

        $history = AgentConversation::where('user_id', $userId)
            ->where(function ($q) use ($idleCutoff, $pinnedCutoff) {
                $q->where(function ($q2) use ($pinnedCutoff) {
                    $q2->where('is_pinned', true)
                       ->where('created_at', '>=', $pinnedCutoff);
                })->orWhere('created_at', '>=', $idleCutoff);
            })
            ->orderBy('created_at', 'desc')
            ->limit($turns * 2)
            ->get()
            ->sortBy('created_at')
            ->values();

        if ($history->isEmpty()) {
            return $prompt;
        }

        $formatted = $history->map(fn ($m) =>
            ($m->role === 'user' ? 'User' : 'Assistant').': '.rtrim($m->content)
        )->join("\n");

        return "[Conversation history]\n{$formatted}\n\n{$prompt}";
    }

    // -------------------------------------------------------------------------
    // Conversation storage
    // -------------------------------------------------------------------------

    private function storeMessage(
        int $userId,
        int $orgId,
        string $role,
        string $content,
        bool $isPinned,
        ?int $expenseId,
    ): void {
        try {
            AgentConversation::create([
                'user_id'         => $userId,
                'organisation_id' => $orgId,
                'role'            => $role,
                'content'         => $content,
                'is_pinned'       => $isPinned,
                'expense_id'      => $expenseId,
            ]);
        } catch (\Throwable $e) {
            Log::warning('AgentCoreService: failed to store conversation message', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // Transport layer — swap by changing AGENTCORE_MODE in .env only
    // -------------------------------------------------------------------------

    private function sendRequest(string $sessionId, array $payload): string
    {
        return match (config('services.agentcore.mode', 'local')) {
            'prod'  => $this->invokeViaSDK($sessionId, $payload),
            default => $this->invokeViaHttp($payload),
        };
    }

    /**
     * Local dev mode: POST JSON to the agentcore dev server.
     */
    private function invokeViaHttp(array $payload): string
    {
        $endpoint = rtrim(config('services.agentcore.endpoint'), '/').'/invocations';

        $response = Http::timeout(self::TIMEOUT)
            ->withHeaders(['X-Agentcore-Local' => '1'])
            ->post($endpoint, $payload);

        if (! $response->successful()) {
            throw new \RuntimeException(
                "AgentCore returned HTTP {$response->status()}: ".$response->body()
            );
        }

        return $response->body();
    }

    /**
     * Prod mode: invoke the deployed runtime ARN via the AWS SDK (SigV4-signed).
     * No public HTTP URL — this is an SDK call only.
     */
    private function invokeViaSDK(string $sessionId, array $payload): string
    {
        $arn = config('services.agentcore.runtime_arn');

        if (empty($arn)) {
            throw new \RuntimeException(
                'AGENTCORE_RUNTIME_ARN must be set when AGENTCORE_MODE=prod.'
            );
        }

        $clientConfig = [
            'region'  => config('services.agentcore.region', 'eu-central-1'),
            'version' => 'latest',
        ];

        $key = config('services.aws.key');
        if ($key) {
            $clientConfig['credentials'] = [
                'key'    => $key,
                'secret' => config('services.aws.secret'),
            ];
        }

        $client = new BedrockAgentCoreClient($clientConfig);

        $result = $client->invokeAgentRuntime([
            'agentRuntimeArn'  => $arn,
            'runtimeSessionId' => $sessionId,
            'contentType'      => 'application/json',
            'accept'           => 'application/json',
            'payload'          => json_encode($payload),
        ]);

        return (string) $result['response'];
    }

    // -------------------------------------------------------------------------
    // Usage logging
    // -------------------------------------------------------------------------

    private function logUsage(
        float $startTime,
        int $orgId,
        int $userId,
        bool $success,
        ?string $errorMessage = null,
        array $usage = [],
        ?string $modelName = null,
    ): void {
        $responseTimeMs   = (int) round((microtime(true) - $startTime) * 1000);
        $inputTokens      = (int) ($usage['input_tokens'] ?? 0);
        $outputTokens     = (int) ($usage['output_tokens'] ?? 0);
        $cacheReadTokens  = (int) ($usage['cache_read_input_tokens'] ?? 0);
        $cacheWriteTokens = (int) ($usage['cache_write_input_tokens'] ?? 0);
        $totalTokens      = $inputTokens + $outputTokens + $cacheReadTokens + $cacheWriteTokens;

        $modelName = $modelName ?: (string) config('services.agentcore.model_name', 'claude-haiku-4-5');

        try {
            AiUsageLog::create([
                'organisation_id'    => $orgId,
                'user_id'            => $userId,
                'service_type'       => 'bedrock_agent',
                'model_name'         => $modelName,
                'prompt_tokens'      => $inputTokens,
                'completion_tokens'  => $outputTokens,
                'total_tokens'       => $totalTokens,
                'cache_read_tokens'  => $cacheReadTokens,
                'cache_write_tokens' => $cacheWriteTokens,
                'estimated_cost'     => AiUsageLog::calculateCost([
                    'model_name'         => $modelName,
                    'input_tokens'       => $inputTokens,
                    'output_tokens'      => $outputTokens,
                    'cache_read_tokens'  => $cacheReadTokens,
                    'cache_write_tokens' => $cacheWriteTokens,
                ]),
                'currency'           => 'USD',
                'response_time_ms'   => $responseTimeMs,
                'success'            => $success,
                'error_message'      => $errorMessage ? substr($errorMessage, 0, 500) : null,
                'request_summary'    => 'WhatsApp assistant',
            ]);
        } catch (\Throwable $e) {
            Log::warning('AgentCoreService: failed to write usage log', ['error' => $e->getMessage()]);
        }
    }
}
