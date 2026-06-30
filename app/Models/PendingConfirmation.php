<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PendingConfirmation extends Model
{
    protected $fillable = [
        'expense_id',
        'user_id',
        'kind',
        'awaiting_type_reply',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'awaiting_type_reply' => 'boolean',
            'expires_at'          => 'datetime',
        ];
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
