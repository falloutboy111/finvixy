<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class OrganisationBudget extends Model
{
    /** @use HasFactory<\Database\Factories\OrganisationBudgetFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'organisation_id',
        'vendor_name',
        'expense_category',
        'budget_limit',
        'monthly_reset_day',
        'current_month_spent',
        'last_reset_at',
        'send_alerts',
    ];

    protected function casts(): array
    {
        return [
            'budget_limit' => 'decimal:2',
            'current_month_spent' => 'decimal:2',
            'last_reset_at' => 'datetime',
            'send_alerts' => 'boolean',
        ];
    }

    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

    /**
     * Check if budget is exceeded based on current month spending.
     */
    public function isExceeded(): bool
    {
        $this->resetIfNeeded();

        return (float) $this->current_month_spent > (float) $this->budget_limit;
    }

    /**
     * Get remaining budget amount.
     */
    public function getRemainingAttribute(): float
    {
        $this->resetIfNeeded();

        $remaining = (float) $this->budget_limit - (float) $this->current_month_spent;

        return max(0, $remaining);
    }

    /**
     * Get percentage of budget used.
     */
    public function getPercentageUsedAttribute(): float
    {
        $this->resetIfNeeded();

        if ((float) $this->budget_limit === 0) {
            return 0;
        }

        return ((float) $this->current_month_spent / (float) $this->budget_limit) * 100;
    }

    /**
     * Reset spending if the month has changed and reset day has passed.
     */
    public function resetIfNeeded(): void
    {
        if ($this->last_reset_at === null) {
            $this->last_reset_at = now();
            $this->save();

            return;
        }

        $lastReset = Carbon::parse($this->last_reset_at);
        $today = Carbon::today();
        $resetDay = $this->monthly_reset_day;

        // Calculate the next reset date
        $currentMonth = $today->month;
        $currentYear = $today->year;

        $nextResetDate = Carbon::createFromDate($currentYear, $currentMonth, $resetDay);

        // If we're past the reset day this month but haven't reset yet, check
        if ($today->day >= $resetDay && $lastReset->month !== $today->month) {
            // Reset the spending
            $this->update([
                'current_month_spent' => 0,
                'last_reset_at' => $nextResetDate,
            ]);
        }
    }
}
