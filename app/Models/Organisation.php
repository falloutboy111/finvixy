<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organisation extends Model
{
    /** @use HasFactory<\Database\Factories\OrganisationFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'legacy_uuid',
        'name',
        'logo_path',
        'email',
        'phone',
        'tax_id',
        'status',
        'currency',
        'timezone',
        'storage_type',
        'storage_used_bytes',
        'storage_limit_bytes',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'string',
            'storage_used_bytes' => 'integer',
            'storage_limit_bytes' => 'integer',
        ];
    }

    /**
     * @var list<array{name: string, slug: string, description: string|null}>
     */
    public static array $defaultCategories = [
        ['name' => 'Meals & Entertainment', 'slug' => 'meals-entertainment', 'description' => 'Restaurant meals, client entertainment'],
        ['name' => 'Travel', 'slug' => 'travel', 'description' => 'Flights, accommodation, car hire'],
        ['name' => 'Supplies', 'slug' => 'supplies', 'description' => 'Office supplies and consumables'],
        ['name' => 'Software', 'slug' => 'software', 'description' => 'Software licences and subscriptions'],
        ['name' => 'Hardware', 'slug' => 'hardware', 'description' => 'Computer equipment and devices'],
        ['name' => 'Utilities', 'slug' => 'utilities', 'description' => 'Electricity, water, internet'],
        ['name' => 'Marketing', 'slug' => 'marketing', 'description' => 'Advertising and promotion'],
        ['name' => 'Professional Services', 'slug' => 'professional-services', 'description' => 'Legal, accounting, consulting'],
        ['name' => 'Office Rent', 'slug' => 'office-rent', 'description' => 'Rent and property costs'],
        ['name' => 'Insurance', 'slug' => 'insurance', 'description' => 'Business and asset insurance'],
        ['name' => 'Other', 'slug' => 'other', 'description' => 'Uncategorised expenses'],
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function expenseCategories(): HasMany
    {
        return $this->hasMany(ExpenseCategory::class);
    }

    public function connectedAccounts(): HasMany
    {
        return $this->hasMany(ConnectedAccount::class);
    }

    /**
     * Create default expense categories for new organisation.
     */
    public function createDefaultCategories(): void
    {
        foreach (self::$defaultCategories as $index => $category) {
            $this->expenseCategories()->create([
                'name' => $category['name'],
                'slug' => $category['slug'],
                'description' => $category['description'],
                'is_default' => true,
                'sort_order' => $index,
            ]);
        }
    }

    protected static function booted(): void
    {
        static::created(function (Organisation $organisation) {
            $organisation->createDefaultCategories();
        });
    }
}
