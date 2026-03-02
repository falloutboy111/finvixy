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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('organisation_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('plan_id')->nullable()->after('organisation_id')->constrained()->nullOnDelete();
            $table->string('phone')->nullable()->after('email');
            $table->string('whatsapp_number')->nullable()->after('phone');
            $table->boolean('whatsapp_enabled')->default(false)->after('whatsapp_number');
            $table->string('avatar')->nullable()->after('whatsapp_enabled');
            $table->timestamp('email_2fa_enabled_at')->nullable()->after('two_factor_confirmed_at');
            $table->boolean('first_time_login')->default(true)->after('email_2fa_enabled_at');
            $table->timestamp('last_login_at')->nullable()->after('first_time_login');
            $table->string('last_login_ip')->nullable()->after('last_login_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organisation_id');
            $table->dropConstrainedForeignId('plan_id');
            $table->dropColumn([
                'phone',
                'whatsapp_number',
                'whatsapp_enabled',
                'avatar',
                'email_2fa_enabled_at',
                'first_time_login',
                'last_login_at',
                'last_login_ip',
            ]);
        });
    }
};
