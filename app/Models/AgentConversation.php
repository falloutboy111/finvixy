<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentConversation extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'organisation_id',
        'role',
        'content',
        'is_pinned',
        'expense_id',
    ];

    protected function casts(): array
    {
        return ['is_pinned' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
