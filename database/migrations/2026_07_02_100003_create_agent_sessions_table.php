<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lightweight per-user conversation-session tracker for the inactivity sweeper.
 * One open row per user at a time (closed_at IS NULL). last_activity_at is
 * touched on every inbound WhatsApp message and every agent invocation;
 * exchange_count increments only on real agent runs so empty sessions are
 * never messaged when swept.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organisation_id')->constrained()->cascadeOnDelete();
            $table->timestamp('last_activity_at')->index();
            $table->unsignedInteger('exchange_count')->default(0);
            $table->timestamp('closed_at')->nullable();
            $table->string('closed_reason')->nullable();   // 'inactivity'
            $table->timestamps();

            $table->index(['user_id', 'closed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_sessions');
    }
};
