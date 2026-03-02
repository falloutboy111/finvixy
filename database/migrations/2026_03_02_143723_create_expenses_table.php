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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organisation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('category')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('tax')->nullable();
            $table->date('date');
            $table->string('image_path')->nullable();
            $table->string('receipt_path')->nullable();
            $table->string('drive_file_id')->nullable();
            $table->string('drive_web_link')->nullable();
            $table->json('additional_fields')->nullable();
            $table->json('extracted_data')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'processed'])->default('pending');
            $table->boolean('is_duplicate')->default(false)->index();
            $table->foreignId('duplicate_of')->nullable()->constrained('expenses')->nullOnDelete();
            $table->timestamps();

            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
