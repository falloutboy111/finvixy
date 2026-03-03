<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ExpenseCategory extends Model
{
    /** @use HasFactory<\Database\Factories\ExpenseCategoryFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'legacy_uuid',
        'organisation_id',
        'name',
        'slug',
        'description',
        'is_default',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

    protected static function booted(): void
    {
        static::creating(function (ExpenseCategory $category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });

        static::updating(function (ExpenseCategory $category) {
            if ($category->isDirty('name') && ! $category->isDirty('slug')) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    /**
     * Get category slugs for AI prompts, scoped to an organisation.
     * Falls back to default categories if none exist for the organisation.
     *
     * @return list<string>
     */
    public static function getFormattedForAi(?int $organisationId = null): array
    {
        if ($organisationId) {
            $categories = self::where('organisation_id', $organisationId)
                ->orderBy('sort_order')
                ->pluck('slug')
                ->toArray();

            if (! empty($categories)) {
                return $categories;
            }
        }

        return collect(Organisation::$defaultCategories)
            ->pluck('slug')
            ->toArray();
    }

    /**
     * Get category slugs with descriptions for richer AI context.
     */
    public static function getFormattedWithDescriptions(?int $organisationId = null): string
    {
        if ($organisationId) {
            $categories = self::where('organisation_id', $organisationId)
                ->orderBy('sort_order')
                ->get(['slug', 'description']);

            if ($categories->isNotEmpty()) {
                return $categories->map(fn ($c) => "{$c->slug}".($c->description ? " ({$c->description})" : ''))->implode(', ');
            }
        }

        return collect(Organisation::$defaultCategories)
            ->map(fn ($c) => "{$c['slug']}".($c['description'] ? " ({$c['description']})" : ''))
            ->implode(', ');
    }
}
