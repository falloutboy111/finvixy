<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RateLimitLog extends Model
{
    protected $fillable = [
        'organisation_id',
        'user_id',
        'resource_type',
        'quota_limit',
        'usage_count',
        'remaining',
        'was_throttled',
        'reset_at',
    ];

    protected function casts(): array
    {
        return [
            'was_throttled' => 'boolean',
            'reset_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
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
}
