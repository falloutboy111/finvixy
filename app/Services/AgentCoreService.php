<?php

namespace App\Services;

use App\Models\AiUsageLog;
use Aws\BedrockAgentCore\BedrockAgentCoreClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AgentCoreService
{
    private const TIMEOUT = 60;

    /**
     * Invoke the AgentCore runtime and return the agent's reply text.
     *
     * Dispatches to the local dev server or the deployed prod runtime depending
     * on AGENTCORE_MODE. Both paths return the same {"reply","usage"} envelope.
     *
     * organisation_id and user_id are resolved by the Laravel webhook from the
     * verified sender phone — never trust these from the WhatsApp message content.
     */
    public function invoke(string $prompt, int $orgId, int $userId): string
    {
        $startTime = microtime(true);
        $bucket    = (int) floor(now()->timestamp / 600); // rolls over every 10 minutes
        $sessionId = str_pad('wa-'.$userId.'-'.$bucket, 33, '0'); // AgentCore requires >= 33 chars
        $payload   = [
            'organisation_id' => (string) $orgId,
            'user_id'         => (string) $userId,
            'session_id'      => $sessionId,
            'prompt'          => $prompt,
        ];

        try {
            $rawBody = $this->sendRequest($sessionId, $payload);

            // Response envelope: {"reply":"<text>","usage":{input_tokens,output_tokens,
            // cache_read_input_tokens,cache_write_input_tokens}}
            $decoded = json_decode($rawBody, true);
            $text    = is_array($decoded) && isset($decoded['reply'])
                ? trim((string) $decoded['reply'])
                : trim($rawBody);

            $usage = is_array($decoded) && isset($decoded['usage']) ? (array) $decoded['usage'] : [];

            Log::info('AgentCore response received', [
                'org_id'        => $orgId,
                'user_id'       => $userId,
                'mode'          => config('services.agentcore.mode', 'local'),
                'length'        => strlen($text),
                'input_tokens'  => $usage['input_tokens'] ?? null,
                'output_tokens' => $usage['output_tokens'] ?? null,
            ]);

            $this->logUsage($startTime, $orgId, $userId, true, null, $usage);

            return $text ?: "Sorry, I couldn't process that right now. Please try again.";
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->logUsage($startTime, $orgId, $userId, false, $e->getMessage());
            throw $e;
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

        // Use explicit credentials when configured; otherwise fall through to
        // the SDK's default provider chain (IAM role, env vars, etc.).
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
        array $usage = []
    ): void {
        $responseTimeMs   = (int) round((microtime(true) - $startTime) * 1000);
        $inputTokens      = (int) ($usage['input_tokens'] ?? 0);
        $outputTokens     = (int) ($usage['output_tokens'] ?? 0);
        $cacheReadTokens  = (int) ($usage['cache_read_input_tokens'] ?? 0);
        $cacheWriteTokens = (int) ($usage['cache_write_input_tokens'] ?? 0);
        $totalTokens      = $inputTokens + $outputTokens + $cacheReadTokens + $cacheWriteTokens;

        try {
            AiUsageLog::create([
                'organisation_id'    => $orgId,
                'user_id'            => $userId,
                'service_type'       => 'bedrock_agent',
                'model_name'         => 'claude-haiku-4-5',
                'prompt_tokens'      => $inputTokens,
                'completion_tokens'  => $outputTokens,
                'total_tokens'       => $totalTokens,
                'cache_read_tokens'  => $cacheReadTokens,
                'cache_write_tokens' => $cacheWriteTokens,
                'estimated_cost'     => AiUsageLog::calculateCost([
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
