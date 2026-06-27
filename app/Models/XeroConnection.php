<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class XeroConnection extends Model
{
    protected $fillable = [
        'user_id',
        'tenant_id',
        'tenant_name',
        'access_token',
        'refresh_token',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpiringSoon(int $bufferSeconds = 60): bool
    {
        return $this->expires_at->subSeconds($bufferSeconds)->isPast();
    }
}
