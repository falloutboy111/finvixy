<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Per-user monthly agent-invocation counter. Mirrors PriceLookupUsage:
 * getCount() backs the pre-emptive cap check, recordInvocation() is called
 * only for real agent runs (never for cap rejections).
 */
class AgentInvocationUsage extends Model
{
    protected $table = 'agent_invocation_usage';

    public $timestamps = false;

    protected $fillable = ['organisation_id', 'user_id', 'month', 'count'];

    protected function casts(): array
    {
        return ['month' => 'date', 'count' => 'integer'];
    }

    public static function getCount(int $orgId, int $userId): int
    {
        $month = Carbon::now()->startOfMonth()->toDateString();

        return (int) (static::where('organisation_id', $orgId)
            ->where('user_id', $userId)
            ->where('month', $month)
            ->value('count') ?? 0);
    }

    public static function recordInvocation(int $orgId, int $userId): void
    {
        $month = Carbon::now()->startOfMonth()->toDateString();

        $row = static::firstOrCreate(
            ['organisation_id' => $orgId, 'user_id' => $userId, 'month' => $month],
            ['count' => 0]
        );

        $row->increment('count');
    }
}
