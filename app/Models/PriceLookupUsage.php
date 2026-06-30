<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class PriceLookupUsage extends Model
{
    protected $table = 'price_lookup_usage';

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

    public static function recordLookup(int $orgId, int $userId): void
    {
        $month = Carbon::now()->startOfMonth()->toDateString();

        $row = static::firstOrCreate(
            ['organisation_id' => $orgId, 'user_id' => $userId, 'month' => $month],
            ['count' => 0]
        );

        $row->increment('count');
    }
}
