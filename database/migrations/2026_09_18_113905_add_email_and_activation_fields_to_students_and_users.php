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
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'email')) {
                $table->string('email')->nullable()->after('year_level');
                $table->index('email');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'is_activated')) {
                $table->boolean('is_activated')->default(true)->after('is_active');
            }
            if (!Schema::hasColumn('users', 'activation_otp')) {
                $table->string('activation_otp')->nullable()->after('is_activated');
            }
            if (!Schema::hasColumn('users', 'activation_otp_expires_at')) {
                $table->timestamp('activation_otp_expires_at')->nullable()->after('activation_otp');
            }
            if (!Schema::hasColumn('users', 'activation_token')) {
                $table->string('activation_token', 64)->nullable()->after('activation_otp_expires_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'email')) {
                $table->dropIndex(['email']);
                $table->dropColumn('email');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'is_activated',
                'activation_otp',
                'activation_otp_expires_at',
                'activation_token',
            ]);
        });
    }
};
