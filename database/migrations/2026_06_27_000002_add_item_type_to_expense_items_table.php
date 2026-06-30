<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expense_items', function (Blueprint $table) {
            $table->string('item_type', 100)->nullable()->after('name');
            $table->string('item_group', 100)->nullable()->after('item_type');
            $table->index('item_type');
        });
    }

    public function down(): void
    {
        Schema::table('expense_items', function (Blueprint $table) {
            $table->dropIndex(['item_type']);
            $table->dropColumn(['item_type', 'item_group']);
        });
    }
};
