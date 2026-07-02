<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Allow whatsapp_webhooks.payload to be NULL so retention pruning
 * (finvixy:prune-receipts) can clear stored raw payloads while keeping the row
 * for message_id idempotency and status auditing. Requires doctrine/dbal for
 * change() on some drivers.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_webhooks', function (Blueprint $table) {
            $table->json('payload')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_webhooks', function (Blueprint $table) {
            $table->json('payload')->nullable(false)->change();
        });
    }
};
