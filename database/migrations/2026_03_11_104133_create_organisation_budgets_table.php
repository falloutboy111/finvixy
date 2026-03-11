<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organisation_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organisation_id')->constrained()->onDelete('cascade');
            $table->string('vendor_name')->nullable();
            $table->string('expense_category')->nullable();
            $table->decimal('budget_limit', 12, 2);
            $table->integer('monthly_reset_day')->default(1);
            $table->decimal('current_month_spent', 12, 2)->default(0);
            $table->timestamp('last_reset_at')->nullable();
            $table->boolean('send_alerts')->default(true);
            $table->timestamps();

            // Composite index for quick lookups
            $table->index(['organisation_id', 'vendor_name', 'expense_category'], 'org_budget_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organisation_budgets');
    }
};
