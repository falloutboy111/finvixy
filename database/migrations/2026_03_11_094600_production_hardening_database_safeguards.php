<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations - Add production hardening measures
     */
    public function up(): void
    {
        // 1. Add soft deletes to Expense for audit trail preservation
        Schema::table('expenses', function (Blueprint $table) {
            if (! Schema::hasColumn('expenses', 'deleted_at')) {
                $table->softDeletes()->comment('Soft delete timestamp for audit trail');
            }
        });

        // 2. Add audit columns to Expense
        Schema::table('expenses', function (Blueprint $table) {
            if (! Schema::hasColumn('expenses', 'last_ocr_confidence')) {
                $table->float('last_ocr_confidence')->nullable()
                    ->comment('Average OCR confidence from last processing');
            }

            if (! Schema::hasColumn('expenses', 'processing_attempts')) {
                $table->integer('processing_attempts')->default(0)
                    ->comment('Number of times this expense was processed');
            }

            if (! Schema::hasColumn('expenses', 'last_processed_at')) {
                $table->timestamp('last_processed_at')->nullable()
                    ->comment('Last time this expense was processed by OCR');
            }
        });

        // 3. Add important indexes for performance
        Schema::table('expenses', function (Blueprint $table) {
            // Index for status queries (very common)
            $table->index('status');

            // Index for organisation filtering
            $table->index('organisation_id');

            // Index for user filtering
            $table->index('user_id');

            // Composite index for common queries: org + status + date
            $table->index(['organisation_id', 'status', 'date']);

            // Index for duplicate detection
            $table->index(['organisation_id', 'is_duplicate']);

            // Index for recent expenses
            $table->index('created_at');
        });

        // 4. Ensure ExpenseItem has proper timestamps
        Schema::table('expense_items', function (Blueprint $table) {
            if (! Schema::hasColumn('expense_items', 'created_at')) {
                $table->timestamps();
            }

            if (! Schema::hasColumn('expense_items', 'deleted_at')) {
                $table->softDeletes()->nullable();
            }
        });

        // 5. Add index to ExpenseItem for queries
        Schema::table('expense_items', function (Blueprint $table) {
            $table->index('expense_id');
        });

        // 6. Create audit_logs table if it doesn't exist (for comprehensive audit trail)
        if (! Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->string('action')->comment('e.g., expense_created, expense_processed, expense_deleted');
                $table->string('model')->nullable()->comment('Model class (e.g., Expense, ExpenseItem)');
                $table->unsignedBigInteger('model_id')->nullable();
                $table->unsignedBigInteger('organisation_id')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->longText('changes')->nullable()->comment('JSON of what changed');
                $table->string('ip_address')->nullable();
                $table->string('user_agent')->nullable();
                $table->timestamps();

                $table->index('action');
                $table->index(['organisation_id', 'created_at']);
                $table->index(['model', 'model_id']);
                $table->index('user_id');
            });
        }

        // 7. Create processing_queue_log table for monitoring job processing
        if (! Schema::hasTable('processing_queue_log')) {
            Schema::create('processing_queue_log', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('expense_id');
                $table->string('job_type')->comment('e.g., ProcessExpenseImage');
                $table->string('status')->default('pending'); // pending, processing, succeeded, failed
                $table->integer('attempt_count')->default(0);
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->text('error_message')->nullable();
                $table->integer('duration_ms')->nullable()->comment('Processing duration in milliseconds');
                $table->timestamps();

                $table->index(['expense_id', 'status']);
                $table->index('status');
                $table->index('created_at');

                $table->foreign('expense_id')->references('id')->on('expenses')->onDelete('cascade');
            });
        }

        // 8. Create rate_limit_log table for monitoring rate limits
        if (! Schema::hasTable('rate_limit_log')) {
            Schema::create('rate_limit_log', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('organisation_id');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('resource_type')->comment('e.g., textract_call, bedrock_call, image_upload');
                $table->integer('quota_limit')->comment('Configured limit');
                $table->integer('usage_count')->comment('Current usage count');
                $table->integer('remaining')->comment('Remaining quota');
                $table->boolean('was_throttled')->default(false);
                $table->timestamp('reset_at')->nullable();
                $table->timestamps();

                $table->index(['organisation_id', 'resource_type', 'created_at']);
                $table->index('was_throttled');
            });
        }
    }

    /**
     * Reverse the migrations
     */
    public function down(): void
    {
        // Drop new tables
        Schema::dropIfExists('rate_limit_log');
        Schema::dropIfExists('processing_queue_log');
        Schema::dropIfExists('audit_logs');

        // Drop new columns
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropSoftDeletesIfExists();
            $table->dropColumnIfExists('last_ocr_confidence');
            $table->dropColumnIfExists('processing_attempts');
            $table->dropColumnIfExists('last_processed_at');
        });

        Schema::table('expense_items', function (Blueprint $table) {
            $table->dropSoftDeletesIfExists();
        });
    }
};
