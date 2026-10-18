<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('achievements', function (Blueprint $table) {
            if (!Schema::hasColumn('achievements', 'criteria_type')) {
                $table->string('criteria_type')->default('custom')->after('category');
            }
            if (!Schema::hasColumn('achievements', 'criteria_threshold')) {
                $table->integer('criteria_threshold')->default(1)->after('criteria_type');
            }
            if (!Schema::hasColumn('achievements', 'xp_bonus')) {
                $table->integer('xp_bonus')->default(0)->after('criteria_threshold');
            }
            if (!Schema::hasColumn('achievements', 'points_bonus')) {
                $table->integer('points_bonus')->default(0)->after('xp_bonus');
            }
            if (!Schema::hasColumn('achievements', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('points_bonus');
            }
        });
    }

    public function down(): void
    {
        Schema::table('achievements', function (Blueprint $table) {
            $table->dropColumn(['criteria_type', 'criteria_threshold', 'xp_bonus', 'points_bonus', 'is_active']);
        });
    }
};
