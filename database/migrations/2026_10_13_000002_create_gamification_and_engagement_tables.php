<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('seasons')) {
            Schema::create('seasons', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('academic_year_label');
                $table->date('start_date');
                $table->date('end_date');
                $table->boolean('is_active')->default(false);
                $table->string('status')->default('draft');
                $table->json('settings')->nullable();
                $table->timestamps();
                $table->index('is_active');
            });
        }

        if (!Schema::hasTable('levels')) {
            Schema::create('levels', function (Blueprint $table) {
                $table->id();
                $table->integer('level')->unique();
                $table->string('title');
                $table->integer('min_xp');
                $table->string('badge_icon')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('member_season_stats')) {
            Schema::create('member_season_stats', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignUuid('season_id')->constrained('seasons')->cascadeOnDelete();
                $table->integer('season_xp')->default(0);
                $table->integer('redeemable_points')->default(0);
                $table->integer('current_level')->default(1);
                $table->timestamps();
                $table->unique(['user_id', 'season_id']);
                $table->index(['season_id', 'season_xp']);
            });
        }

        if (!Schema::hasTable('xp_transactions')) {
            Schema::create('xp_transactions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignUuid('season_id')->nullable()->constrained('seasons')->nullOnDelete();
                $table->integer('amount');
                $table->string('activity_type');
                $table->string('source_type')->nullable();
                $table->string('source_id')->nullable();
                $table->string('description');
                $table->string('idempotency_key')->nullable()->unique();
                $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['user_id', 'created_at']);
            });
        }

        if (!Schema::hasTable('point_transactions')) {
            Schema::create('point_transactions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignUuid('season_id')->nullable()->constrained('seasons')->nullOnDelete();
                $table->integer('amount');
                $table->string('transaction_type');
                $table->string('source_type')->nullable();
                $table->string('source_id')->nullable();
                $table->string('description');
                $table->string('idempotency_key')->nullable()->unique();
                $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['user_id', 'created_at']);
            });
        }

        if (!Schema::hasTable('reward_rules')) {
            Schema::create('reward_rules', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('activity_type')->unique();
                $table->string('name');
                $table->integer('xp_amount')->default(0);
                $table->integer('points_amount')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('rewards')) {
            Schema::create('rewards', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('title');
                $table->string('category');
                $table->text('description')->nullable();
                $table->integer('points_cost');
                $table->integer('stock')->nullable();
                $table->string('image_url')->nullable();
                $table->boolean('is_active')->default(true);
                $table->text('fulfillment_instructions')->nullable();
                $table->timestamps();
                $table->index('category');
                $table->index('is_active');
            });
        }

        if (!Schema::hasTable('reward_redemptions')) {
            Schema::create('reward_redemptions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignUuid('reward_id')->constrained('rewards')->cascadeOnDelete();
                $table->foreignUuid('season_id')->nullable()->constrained('seasons')->nullOnDelete();
                $table->integer('points_spent');
                $table->string('item_title_snapshot');
                $table->string('status')->default('pending');
                $table->text('notes')->nullable();
                $table->timestamp('fulfilled_at')->nullable();
                $table->foreignUuid('fulfilled_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['user_id', 'status']);
            });
        }

        if (!Schema::hasTable('missions')) {
            Schema::create('missions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('title');
                $table->text('description');
                $table->string('category')->default('general');
                $table->integer('xp_reward')->default(0);
                $table->integer('points_reward')->default(0);
                $table->string('status')->default('published');
                $table->timestamp('due_date')->nullable();
                $table->string('submission_type')->default('text');
                $table->timestamps();
                $table->index('status');
            });
        }

        if (!Schema::hasTable('mission_submissions')) {
            Schema::create('mission_submissions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('mission_id')->constrained('missions')->cascadeOnDelete();
                $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
                $table->text('submission_text')->nullable();
                $table->string('proof_url')->nullable();
                $table->string('status')->default('pending');
                $table->text('review_notes')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->foreignUuid('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['mission_id', 'user_id', 'status']);
            });
        }

        if (!Schema::hasTable('skills')) {
            Schema::create('skills', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name')->unique();
                $table->string('category')->default('technical');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('user_skills')) {
            Schema::create('user_skills', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignUuid('skill_id')->constrained('skills')->cascadeOnDelete();
                $table->string('proficiency')->default('intermediate');
                $table->timestamps();
                $table->unique(['user_id', 'skill_id']);
            });
        }

        if (!Schema::hasTable('user_profiles')) {
            Schema::create('user_profiles', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('user_id')->unique()->constrained('users')->cascadeOnDelete();
                $table->text('bio')->nullable();
                $table->json('interests')->nullable();
                $table->string('github_url')->nullable();
                $table->string('linkedin_url')->nullable();
                $table->json('collaboration_preferences')->nullable();
                $table->boolean('is_public')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('achievements')) {
            Schema::create('achievements', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('slug')->unique();
                $table->string('title');
                $table->text('description');
                $table->string('icon')->default('award');
                $table->string('category')->default('milestone');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('user_achievements')) {
            Schema::create('user_achievements', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignUuid('achievement_id')->constrained('achievements')->cascadeOnDelete();
                $table->timestamp('unlocked_at');
                $table->timestamps();
                $table->unique(['user_id', 'achievement_id']);
            });
        }

        if (!Schema::hasTable('job_listings')) {
            Schema::create('job_listings', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('title');
                $table->string('company');
                $table->string('job_type');
                $table->string('location')->nullable();
                $table->boolean('is_remote')->default(false);
                $table->text('description');
                $table->json('required_skills')->nullable();
                $table->string('source_url')->nullable();
                $table->string('application_url');
                $table->timestamp('deadline_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index('is_active');
            });
        }

        if (!Schema::hasTable('job_bookmarks')) {
            Schema::create('job_bookmarks', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignUuid('job_listing_id')->constrained('job_listings')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['user_id', 'job_listing_id']);
            });
        }

        if (!Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('type');
                $table->string('title');
                $table->text('body');
                $table->string('target_url')->nullable();
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
                $table->index(['user_id', 'read_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('job_bookmarks');
        Schema::dropIfExists('job_listings');
        Schema::dropIfExists('user_achievements');
        Schema::dropIfExists('achievements');
        Schema::dropIfExists('user_profiles');
        Schema::dropIfExists('user_skills');
        Schema::dropIfExists('skills');
        Schema::dropIfExists('mission_submissions');
        Schema::dropIfExists('missions');
        Schema::dropIfExists('reward_redemptions');
        Schema::dropIfExists('rewards');
        Schema::dropIfExists('reward_rules');
        Schema::dropIfExists('point_transactions');
        Schema::dropIfExists('xp_transactions');
        Schema::dropIfExists('member_season_stats');
        Schema::dropIfExists('levels');
        Schema::dropIfExists('seasons');
    }
};
