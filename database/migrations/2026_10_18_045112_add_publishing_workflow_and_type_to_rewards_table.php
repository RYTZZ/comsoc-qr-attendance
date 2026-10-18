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
        Schema::table('rewards', function (Blueprint $table) {
            if (!Schema::hasColumn('rewards', 'status')) {
                $table->string('status', 30)->default('published')->after('is_active'); // draft, published, archived
                $table->index('status');
            }
            if (!Schema::hasColumn('rewards', 'reward_type')) {
                $table->string('reward_type', 30)->default('physical')->after('category'); // physical, digital, voucher, kit, merch
                $table->index('reward_type');
            }
            if (!Schema::hasColumn('rewards', 'published_at')) {
                $table->timestamp('published_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('rewards', 'starts_at')) {
                $table->timestamp('starts_at')->nullable()->after('published_at');
            }
            if (!Schema::hasColumn('rewards', 'ends_at')) {
                $table->timestamp('ends_at')->nullable()->after('starts_at');
            }
            if (!Schema::hasColumn('rewards', 'redemption_limit_per_member')) {
                $table->integer('redemption_limit_per_member')->nullable()->after('ends_at');
            }
        });

        // Backfill status from is_active and is_archived
        DB::table('rewards')->where('is_archived', true)->update(['status' => 'archived']);
        DB::table('rewards')->where('is_archived', false)->where('is_active', false)->update(['status' => 'draft']);
        DB::table('rewards')->where('is_archived', false)->where('is_active', true)->update([
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rewards', function (Blueprint $table) {
            $columnsToDrop = [];
            foreach (['status', 'reward_type', 'published_at', 'starts_at', 'ends_at', 'redemption_limit_per_member'] as $col) {
                if (Schema::hasColumn('rewards', $col)) {
                    $columnsToDrop[] = $col;
                }
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
