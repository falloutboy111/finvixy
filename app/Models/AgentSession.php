<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Per-user conversation session used by the inactivity sweeper.
 * One open row per user (closed_at IS NULL).
 */
class AgentSession extends Model
{
    protected $fillable = [
        'user_id',
        'organisation_id',
        'last_activity_at',
        'exchange_count',
        'closed_at',
        'closed_reason',
    ];

    protected function casts(): array
    {
        return [
            'last_activity_at' => 'datetime',
            'closed_at'        => 'datetime',
            'exchange_count'   => 'integer',
        ];
    }

    /**
     * Touch (or open) the user's active session. Called on every inbound
     * WhatsApp message and every agent invocation. $isExchange marks a real
     * agent run so the sweeper can skip never-used sessions.
     */
    public static function touchActivity(int $userId, int $orgId, bool $isExchange = false): void
    {
        $session = static::firstOrCreate(
            ['user_id' => $userId, 'closed_at' => null],
            ['organisation_id' => $orgId, 'last_activity_at' => now()],
        );

        $session->last_activity_at = now();

        if ($isExchange) {
            $session->exchange_count++;
        }

        $session->save();
    }
}
