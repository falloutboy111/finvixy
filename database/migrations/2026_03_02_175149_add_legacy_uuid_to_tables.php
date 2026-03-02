<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables that need legacy UUID tracking for data sync.
     *
     * @var array<string>
     */
    private array $tables = [
        'organisations',
        'users',
        'plans',
        'expenses',
        'expense_items',
        'expense_categories',
        'connected_accounts',
        'ai_usage_logs',
        'whatsapp_webhooks',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->uuid('legacy_uuid')->nullable()->unique()->after('id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn('legacy_uuid');
            });
        }
    }
};
