<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_usage_logs', function (Blueprint $table) {
            $table->unsignedInteger('cache_read_tokens')->default(0)->after('total_tokens');
            $table->unsignedInteger('cache_write_tokens')->default(0)->after('cache_read_tokens');
        });
    }

    public function down(): void
    {
        Schema::table('ai_usage_logs', function (Blueprint $table) {
            $table->dropColumn(['cache_read_tokens', 'cache_write_tokens']);
        });
    }
};
