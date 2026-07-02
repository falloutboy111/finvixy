<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiUsageLog extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'legacy_uuid',
        'organisation_id',
        'user_id',
        'expense_id',
        'service_type',
        'model_name',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'cache_read_tokens',
        'cache_write_tokens',
        'input_characters',
        'output_characters',
        'estimated_cost',
        'currency',
        'request_summary',
        'response_time_ms',
        'success',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'success'            => 'boolean',
            'estimated_cost'     => 'decimal:8',
            'prompt_tokens'      => 'integer',
            'completion_tokens'  => 'integer',
            'total_tokens'       => 'integer',
            'cache_read_tokens'  => 'integer',
            'cache_write_tokens' => 'integer',
            'input_characters'   => 'integer',
            'output_characters'  => 'integer',
            'response_time_ms'   => 'integer',
        ];
    }

    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    /**
     * Per-million-token USD input/output rates by model. Cache multipliers are
     * uniform across Claude models: write = 1.25× input, read = 0.10× input.
     * Output is 5× input on both Haiku and Sonnet tiers.
     *
     * @var array<string, array{in: float, out: float}>
     */
    private const MODEL_RATES = [
        'claude-sonnet-4-6' => ['in' => 3.00, 'out' => 15.00],
        'claude-haiku-4-5'  => ['in' => 1.00, 'out' => 5.00],
    ];

    /**
     * Calculate estimated cost in USD.
     *
     * New callers pass real token counts (input_tokens, output_tokens,
     * cache_read_tokens, cache_write_tokens) plus a model_name and get that
     * model's rates (Haiku 4.5 if omitted/unknown). Legacy callers
     * (BedrockAgentService, OCR) pass service_type and are handled by the
     * fallback match below.
     */
    public static function calculateCost(array $data): float
    {
        // New path: real token counts from the agent usage envelope
        if (isset($data['input_tokens']) || isset($data['cache_read_tokens'])) {
            $input      = (int) ($data['input_tokens'] ?? 0);
            $output     = (int) ($data['output_tokens'] ?? 0);
            $cacheRead  = (int) ($data['cache_read_tokens'] ?? 0);
            $cacheWrite = (int) ($data['cache_write_tokens'] ?? 0);

            $rates = self::ratesFor((string) ($data['model_name'] ?? ''));

            return round(
                ($input      / 1_000_000) * $rates['in'] +
                ($cacheRead  / 1_000_000) * ($rates['in'] * 0.10) +
                ($cacheWrite / 1_000_000) * ($rates['in'] * 1.25) +
                ($output     / 1_000_000) * $rates['out'],
                8
            );
        }

        // Legacy path: rough estimates keyed by service_type
        return match ($data['service_type'] ?? '') {
            'ocr'          => 0.0015,
            'ai_agent'     => ($data['total_tokens'] ?? 0) / 1_000 * 0.002,
            'bedrock_agent' => ($data['total_tokens'] ?? 0) / 1_000 * 0.0015,
            'bedrock'      => (($data['input_characters'] ?? 0) / 1_000_000 * 0.80)
                            + (($data['output_characters'] ?? 0) / 1_000_000 * 3.20),
            default        => 0.0,
        };
    }

    /**
     * Resolve the rate row for a model name. Substring match so both bare names
     * ("claude-sonnet-4-6") and Bedrock profile ids
     * ("eu.anthropic.claude-sonnet-4-6") resolve. Unknown → Haiku 4.5 rates.
     *
     * @return array{in: float, out: float}
     */
    private static function ratesFor(string $modelName): array
    {
        foreach (self::MODEL_RATES as $key => $rates) {
            if ($modelName !== '' && str_contains($modelName, $key)) {
                return $rates;
            }
        }

        return self::MODEL_RATES['claude-haiku-4-5'];
    }
}
