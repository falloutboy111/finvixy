<?php

namespace App\Services;

use App\Models\AiUsageLog;
use App\Models\ExpenseCategory;
use Aws\BedrockAgentRuntime\BedrockAgentRuntimeClient;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class BedrockAgentService
{
    use RetryableLaravelService;

    protected BedrockAgentRuntimeClient $client;

    // Constants for limits and timeouts
    private const TIMEOUT_SECONDS = 60;
    private const MAX_RETRIES = 3;
    private const MAX_TOKEN_USAGE = 4000; // Safety limit for a single request
    private const RATE_LIMIT_DELAY_MS = 500; // Delay between requests

    public function __construct()
    {
        $this->client = new BedrockAgentRuntimeClient([
            'region' => config('bedrock.region'),
            'version' => 'latest',
            'credentials' => [
                'key' => config('services.aws.key'),
                'secret' => config('services.aws.secret'),
            ],
            'http' => [
                'timeout' => self::TIMEOUT_SECONDS,
                'connect_timeout' => self::TIMEOUT_SECONDS,
            ],
        ]);

        // Set max retries for this service
        $this->maxRetries = self::MAX_RETRIES;
    }

    /**
     * Parse receipt text using Bedrock Agent and return structured data (with retry logic).
     *
     * @return array{
     *     vendor_name: string,
     *     invoice_number: string|null,
     *     date: string|null,
     *     total_amount: float,
     *     currency: string,
     *     category: string|null,
     *     tax_amount: float|null,
     *     line_items: array<int, array{item_name: string, quantity: float, unit_price: float, total_price: float}>
     * }
     */
    public function parseExpenseDocument(string $receiptText, int $organisationId, ?int $userId = null): array
    {
        $startTime = microtime(true);

        // Validate input
        $this->validateReceiptInput($receiptText);

        try {
            $response = $this->executeWithRetry(
                fn() => $this->performParsing($receiptText, $organisationId),
                'Bedrock.parseExpenseDocument'
            );

            $parsed = $this->parseResponse($response);

            $this->logUsage($startTime, $receiptText, $response, $organisationId, $userId);

            return $parsed;
        } catch (\Throwable $e) {
            Log::error('Bedrock expense parsing failed', [
                'error' => $e->getMessage(),
                'organisation_id' => $organisationId,
                'error_code' => $e->getCode(),
            ]);

            // Log failed parsing attempt for monitoring
            AiUsageLog::create([
                'organisation_id' => $organisationId,
                'user_id' => $userId,
                'service_type' => 'bedrock_agent',
                'model_name' => 'expense_parser',
                'success' => false,
                'error_message' => substr($e->getMessage(), 0, 500),
                'request_summary' => 'Expense receipt parsing (FAILED)',
            ]);

            return $this->fallbackResponse();
        }
    }

    /**
     * Validate receipt input before sending to Bedrock.
     *
     * @throws \InvalidArgumentException
     */
    protected function validateReceiptInput(string $receiptText): void
    {
        if (empty(trim($receiptText))) {
            throw new \InvalidArgumentException('Receipt text cannot be empty');
        }

        if (strlen($receiptText) < 10) {
            throw new \InvalidArgumentException('Receipt text is too short');
        }

        // Token estimate (roughly 4 chars = 1 token)
        $estimatedTokens = (int) ceil(strlen($receiptText) / 4);

        if ($estimatedTokens > self::MAX_TOKEN_USAGE) {
            throw new \InvalidArgumentException(
                "Receipt text exceeds token limit. Estimated: $estimatedTokens, Max: " . self::MAX_TOKEN_USAGE
            );
        }
    }

    /**
     * Perform the actual parsing (called by retry wrapper).
     */
    protected function performParsing(string $receiptText, int $organisationId): string
    {
        // Rate limiting - prevent hammering Bedrock
        usleep(self::RATE_LIMIT_DELAY_MS * 1000);

        $categories = ExpenseCategory::getFormattedWithDescriptions($organisationId);

        $prompt = <<<PROMPT
        Read the receipt text below and respond with JSON containing: vendor_name, invoice_number, date (YYYY-MM-DD format), total_amount (numeric, no currency symbols), currency (3-letter ISO code), category (from the list below), tax_amount (numeric if present, null otherwise), and line_items (array of objects with item_name, quantity, unit_price, total_price).

        Available categories: {$categories}

        Receipt text:
        {$receiptText}

        Respond ONLY with valid JSON. No explanation or markdown.
        PROMPT;

        return $this->invokeAgent($prompt);
    }

    /**
     * Invoke the Bedrock Agent and return the streamed response text.
     */
    protected function invokeAgent(string $prompt): string
    {
        $agentId = config('bedrock.agents.expense_parser.agent_id');
        $aliasId = config('bedrock.agents.expense_parser.alias_id');

        $result = $this->client->invokeAgent([
            'agentId' => $agentId,
            'agentAliasId' => $aliasId,
            'sessionId' => 'finvixy-'.uniqid(),
            'inputText' => $prompt,
        ]);

        $responseText = '';

        $completion = $result->get('completion');
        if ($completion) {
            foreach ($completion as $event) {
                if (isset($event['chunk']['bytes'])) {
                    $responseText .= $event['chunk']['bytes'];
                }
            }
        }

        return $responseText;
    }

    /**
     * Validate and fix date extracted from receipt.
     * Converts future dates to today, logs old dates, defaults to today for invalid dates.
     */
    protected function validateAndFixDate(?string $extractedDate): ?string
    {
        if (empty($extractedDate)) {
            return today()->toDateString();
        }

        try {
            $date = Carbon::parse($extractedDate);
            $today = Carbon::today();

            // If future date → use today
            if ($date->isFuture()) {
                Log::warning('Future date in receipt, using today', [
                    'extracted' => $extractedDate,
                    'using' => $today->toDateString(),
                ]);

                return $today->toDateString();
            }

            // If very old (>365 days) → log warning but allow
            if ($date->diffInDays($today) > 365) {
                Log::warning('Very old receipt date', [
                    'extracted' => $extractedDate,
                    'days_old' => $date->diffInDays($today),
                ]);
            }

            return $date->toDateString();
        } catch (\Throwable $e) {
            Log::warning('Failed to parse date, using today', [
                'extracted' => $extractedDate,
                'error' => $e->getMessage(),
            ]);

            return now()->toDateString();
        }
    }

    /**
     * Extract JSON from the agent response (handles markdown wrapping, etc).
     *
     * @return array<string, mixed>
     */
    protected function parseResponse(string $response): array
    {
        // Try to extract JSON from markdown code blocks
        if (preg_match('/```(?:json)?\s*([\s\S]*?)```/', $response, $matches)) {
            $response = trim($matches[1]);
        }

        // Try to find a JSON object directly
        if (preg_match('/\{[\s\S]*\}/', $response, $matches)) {
            $response = $matches[0];
        }

        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            Log::warning('Failed to parse Bedrock response as JSON', [
                'response' => substr($response, 0, 500),
            ]);

            return $this->fallbackResponse();
        }

        return [
            'vendor_name' => $decoded['vendor_name'] ?? 'Unknown Vendor',
            'invoice_number' => $decoded['invoice_number'] ?? null,
            'date' => $this->validateAndFixDate($decoded['date'] ?? null),
            'total_amount' => (float) ($decoded['total_amount'] ?? 0),
            'currency' => $decoded['currency'] ?? 'ZAR',
            'category' => $decoded['category'] ?? null,
            'tax_amount' => isset($decoded['tax_amount']) ? (float) $decoded['tax_amount'] : null,
            'line_items' => $decoded['line_items'] ?? [],
        ];
    }

    /**
     * Return a safe fallback when parsing fails.
     *
     * @return array<string, mixed>
     */
    protected function fallbackResponse(): array
    {
        return [
            'vendor_name' => 'Unknown Vendor',
            'invoice_number' => null,
            'date' => null,
            'total_amount' => 0,
            'currency' => 'ZAR',
            'category' => null,
            'tax_amount' => null,
            'line_items' => [],
        ];
    }

    /**
     * Log AI usage for cost tracking.
     */
    protected function logUsage(float $startTime, string $input, string $output, int $organisationId, ?int $userId = null): void
    {
        $responseTime = (int) round((microtime(true) - $startTime) * 1000);

        // Rough token estimation (4 chars ≈ 1 token)
        $promptTokens = (int) ceil(strlen($input) / 4);
        $completionTokens = (int) ceil(strlen($output) / 4);
        $totalTokens = $promptTokens + $completionTokens;

        AiUsageLog::query()->create([
            'organisation_id' => $organisationId,
            'user_id' => $userId,
            'service_type' => 'bedrock_agent',
            'model_name' => 'expense_parser',
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'total_tokens' => $totalTokens,
            'input_characters' => strlen($input),
            'output_characters' => strlen($output),
            'estimated_cost' => AiUsageLog::calculateCost([
                'service_type' => 'bedrock_agent',
                'total_tokens' => $totalTokens,
            ]),
            'response_time_ms' => $responseTime,
            'success' => true,
            'request_summary' => 'Expense receipt parsing',
        ]);
    }
}
