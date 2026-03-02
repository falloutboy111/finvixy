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
        'organisation_id',
        'user_id',
        'expense_id',
        'service_type',
        'model_name',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
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
            'success' => 'boolean',
            'estimated_cost' => 'decimal:4',
            'prompt_tokens' => 'integer',
            'completion_tokens' => 'integer',
            'total_tokens' => 'integer',
            'input_characters' => 'integer',
            'output_characters' => 'integer',
            'response_time_ms' => 'integer',
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
     * Calculate estimated cost based on service type and usage.
     *
     * @param  array{service_type: string, total_tokens?: int, input_characters?: int, output_characters?: int}  $data
     */
    public static function calculateCost(array $data): float
    {
        return match ($data['service_type']) {
            'ocr' => 0.0015,
            'ai_agent' => ($data['total_tokens'] ?? 0) / 1000 * 0.002,
            'bedrock_agent' => ($data['total_tokens'] ?? 0) / 1000 * 0.0015,
            'bedrock' => (($data['input_characters'] ?? 0) / 1_000_000 * 0.80) + (($data['output_characters'] ?? 0) / 1_000_000 * 3.20),
            default => 0,
        };
    }
}
