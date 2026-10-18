<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('season_leaderboard_snapshots')) {
            Schema::create('season_leaderboard_snapshots', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('season_id')->constrained('seasons')->cascadeOnDelete();
                $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
                $table->integer('final_rank');
                $table->integer('final_xp');
                $table->integer('final_points');
                $table->integer('final_level');
                $table->timestamp('snapshot_at');
                $table->timestamps();

                $table->unique(['season_id', 'user_id']);
                $table->index(['season_id', 'final_rank']);
            });
        }

        if (!Schema::hasTable('season_resets')) {
            Schema::create('season_resets', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('closing_season_id')->constrained('seasons')->cascadeOnDelete();
                $table->foreignUuid('next_season_id')->nullable()->constrained('seasons')->nullOnDelete();
                $table->foreignUuid('executed_by')->constrained('users')->cascadeOnDelete();
                $table->integer('members_affected_count')->default(0);
                $table->integer('points_expired_sum')->default(0);
                $table->string('status')->default('completed');
                $table->json('details')->nullable();
                $table->timestamp('executed_at');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('season_resets');
        Schema::dropIfExists('season_leaderboard_snapshots');
    }
};
