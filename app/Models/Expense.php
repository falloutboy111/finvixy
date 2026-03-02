<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Expense extends Model
{
    /** @use HasFactory<\Database\Factories\ExpenseFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'legacy_uuid',
        'organisation_id',
        'user_id',
        'name',
        'category',
        'amount',
        'tax',
        'date',
        'image_path',
        'receipt_path',
        'drive_file_id',
        'drive_web_link',
        'additional_fields',
        'extracted_data',
        'notes',
        'status',
        'is_duplicate',
        'duplicate_of',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'date' => 'date',
            'additional_fields' => 'array',
            'extracted_data' => 'array',
            'is_duplicate' => 'boolean',
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

    public function expenseItems(): HasMany
    {
        return $this->hasMany(ExpenseItem::class);
    }

    public function duplicateOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'duplicate_of');
    }

    public function duplicates(): HasMany
    {
        return $this->hasMany(self::class, 'duplicate_of');
    }

    /**
     * Scope to exclude duplicate expenses.
     */
    public function scopeExcludeDuplicates($query)
    {
        return $query->where('is_duplicate', false);
    }

    /**
     * Scope to only get duplicate expenses.
     */
    public function scopeOnlyDuplicates($query)
    {
        return $query->where('is_duplicate', true);
    }

    /**
     * Check if this expense has been synced to Google Drive.
     */
    public function isSyncedToDrive(): bool
    {
        return $this->drive_file_id !== null;
    }
}
