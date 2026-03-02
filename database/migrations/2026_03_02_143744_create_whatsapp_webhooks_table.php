<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('whatsapp_webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('from')->index();
            $table->string('message_id')->unique();
            $table->enum('type', ['text', 'image', 'video', 'audio', 'document', 'location', 'contacts', 'unknown'])->default('unknown');
            $table->json('payload');
            $table->foreignId('expense_id')->nullable()->index();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('organisation_id')->nullable();
            $table->enum('status', ['received', 'processing', 'processed', 'failed', 'ignored'])->default('received')->index();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_webhooks');
    }
};
