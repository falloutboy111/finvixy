<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->string('crm_expense_id')->nullable()->after('duplicate_of');
            $table->timestamp('crm_synced_at')->nullable()->after('crm_expense_id');
            $table->string('crm_project_id', 36)->nullable()->after('crm_synced_at');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn(['crm_expense_id', 'crm_synced_at', 'crm_project_id']);
        });
    }
};
