<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pending_confirmations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('expense_id')->unique(); // one active prompt per expense
            $table->unsignedBigInteger('user_id');
            $table->enum('kind', ['category', 'project']);
            $table->boolean('awaiting_type_reply')->default(false);
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->foreign('expense_id')->references('id')->on('expenses')->onDelete('cascade');
            $table->index('user_id');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_confirmations');
    }
};
