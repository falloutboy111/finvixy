<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    /** @use HasFactory<\Database\Factories\PlanFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'legacy_uuid',
        'code',
        'name',
        'price_monthly',
        'currency',
        'receipts_limit',
        'is_unlimited',
        'is_active',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'price_monthly' => 'decimal:2',
            'receipts_limit' => 'integer',
            'is_unlimited' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Check if the plan has a receipt limit.
     */
    public function hasReceiptLimit(): bool
    {
        return ! $this->is_unlimited && $this->receipts_limit !== null;
    }
}
