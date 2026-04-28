<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('onboarding_completed_at')->nullable()->after('first_time_login');
        });

        Schema::table('organisations', function (Blueprint $table) {
            $table->string('storage_type')->default('none')->after('timezone');
            $table->unsignedBigInteger('storage_used_bytes')->default(0)->after('storage_type');
            $table->unsignedBigInteger('storage_limit_bytes')->default(1073741824)->after('storage_used_bytes'); // 1 GB
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('onboarding_completed_at');
        });

        Schema::table('organisations', function (Blueprint $table) {
            $table->dropColumn(['storage_type', 'storage_used_bytes', 'storage_limit_bytes']);
        });
    }
};
