<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-user monthly agent-invocation counter (mirrors price_lookup_usage).
 * Backs the pre-emptive MONTHLY_INVOCATION_CAP check that runs BEFORE any
 * Bedrock call. Only real agent runs are counted, never cap rejections.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_invocation_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organisation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('month');                         // e.g. 2026-07-01
            $table->unsignedInteger('count')->default(0);
            $table->unique(['organisation_id', 'user_id', 'month']);
            $table->index(['user_id', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_invocation_usage');
    }
};
