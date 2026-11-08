<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('profile_completion_rewards')) {
            Schema::create('profile_completion_rewards', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('user_id')
                    ->unique('prof_comp_rew_user_uq')
                    ->constrained('users', 'id', 'prof_comp_rew_user_fk')
                    ->cascadeOnDelete();
                $table->string('source', 50)->default('profile_completion');
                $table->string('reference_key', 120)->unique('prof_comp_rew_ref_uq');
                $table->foreignUuid('xp_transaction_id')
                    ->nullable()
                    ->constrained('xp_transactions', 'id', 'prof_comp_rew_xp_fk')
                    ->nullOnDelete();
                $table->foreignUuid('point_transaction_id')
                    ->nullable()
                    ->constrained('point_transactions', 'id', 'prof_comp_rew_pt_fk')
                    ->nullOnDelete();
                $table->unsignedInteger('exp_awarded')->default(100);
                $table->unsignedInteger('points_awarded')->default(50);
                $table->unsignedTinyInteger('completion_percentage')->default(100);
                $table->timestamp('awarded_at');
                $table->timestamps();

                $table->index(['source', 'awarded_at'], 'prof_comp_rew_src_awarded_idx');
            });
        }

        if (Schema::hasTable('reward_rules')) {
            $exists = DB::table('reward_rules')
                ->where('activity_type', 'profile_completion')
                ->exists();

            if (! $exists) {
                DB::table('reward_rules')->insert([
                    'id' => (string) Str::uuid(),
                    'activity_type' => 'profile_completion',
                    'name' => 'Profile Completion Reward',
                    'xp_amount' => 100,
                    'points_amount' => 50,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_completion_rewards');

        if (Schema::hasTable('reward_rules')) {
            DB::table('reward_rules')
                ->where('activity_type', 'profile_completion')
                ->delete();
        }
    }
};

