<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rewards', function (Blueprint $table) {
            if (!Schema::hasColumn('rewards', 'min_level')) {
                $table->integer('min_level')->default(1)->after('points_cost');
            }
            if (!Schema::hasColumn('rewards', 'eligibility_notes')) {
                $table->text('eligibility_notes')->nullable()->after('min_level');
            }
            if (!Schema::hasColumn('rewards', 'image_path')) {
                $table->string('image_path')->nullable()->after('image_url');
            }
        });

        Schema::table('reward_redemptions', function (Blueprint $table) {
            if (!Schema::hasColumn('reward_redemptions', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rewards', function (Blueprint $table) {
            $table->dropColumn(['min_level', 'eligibility_notes', 'image_path']);
        });

        Schema::table('reward_redemptions', function (Blueprint $table) {
            $table->dropColumn('rejection_reason');
        });
    }
};
