<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('trivia_questions')) {
            Schema::table('trivia_questions', function (Blueprint $table) {
                if (! Schema::hasColumn('trivia_questions', 'difficulty')) {
                    $table->string('difficulty', 20)->default('medium')->after('category');
                }
                if (! Schema::hasColumn('trivia_questions', 'usage_count')) {
                    $table->unsignedInteger('usage_count')->default(0)->after('points');
                }
                if (! Schema::hasColumn('trivia_questions', 'last_used_at')) {
                    $table->timestamp('last_used_at')->nullable()->after('usage_count');
                }
                if (! Schema::hasColumn('trivia_questions', 'is_ai_generated')) {
                    $table->boolean('is_ai_generated')->default(false)->after('last_used_at');
                }
                if (! Schema::hasColumn('trivia_questions', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('is_ai_generated');
                }
            });
        }

        if (Schema::hasTable('trivia_answers') && ! Schema::hasColumn('trivia_answers', 'trivia_session_id')) {
            Schema::table('trivia_answers', function (Blueprint $table) {
                $table->uuid('trivia_session_id')->nullable()->after('trivia_question_id');
                $table->index('trivia_session_id', 'ta_session_idx');
            });
        }

        if (! Schema::hasTable('trivia_sessions')) {
            Schema::create('trivia_sessions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('week_key', 20)->unique();
                $table->string('title', 150);
                $table->string('theme', 80)->nullable();
                $table->string('status', 20)->default('active'); // scheduled | active | archived
                $table->unsignedSmallInteger('exp_reward')->default(15);
                $table->timestamp('starts_at');
                $table->timestamp('ends_at');
                $table->timestamp('activated_at')->nullable();
                $table->timestamp('archived_at')->nullable();
                $table->timestamps();

                $table->index(['status', 'starts_at'], 'ts_status_starts_idx');
            });
        }

        if (! Schema::hasTable('trivia_session_questions')) {
            Schema::create('trivia_session_questions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('trivia_session_id')->constrained('trivia_sessions', 'id', 'tsq_session_fk')->cascadeOnDelete();
                $table->foreignUuid('trivia_question_id')->constrained('trivia_questions', 'id', 'tsq_question_fk')->cascadeOnDelete();
                $table->unsignedSmallInteger('position')->default(0);
                $table->json('option_order');
                $table->timestamps();

                $table->unique(['trivia_session_id', 'trivia_question_id'], 'tsq_session_question_unique');
            });
        }

        if (! Schema::hasTable('trivia_session_completions')) {
            Schema::create('trivia_session_completions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('trivia_session_id')->constrained('trivia_sessions', 'id', 'tsc_session_fk')->cascadeOnDelete();
                $table->foreignUuid('user_id')->constrained('users', 'id', 'tsc_user_fk')->cascadeOnDelete();
                $table->unsignedSmallInteger('answers_count')->default(0);
                $table->unsignedSmallInteger('correct_count')->default(0);
                $table->unsignedSmallInteger('exp_awarded')->default(15);
                $table->uuid('xp_transaction_id')->nullable();
                $table->timestamp('completed_at');
                $table->timestamps();

                $table->unique(['trivia_session_id', 'user_id'], 'tsc_session_user_unique');
                $table->index('user_id', 'tsc_user_idx');
            });
        }

        if (! Schema::hasTable('trivia_automation_logs')) {
            Schema::create('trivia_automation_logs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('action', 60);
                $table->string('status', 20);
                $table->string('week_key', 20)->nullable();
                $table->uuid('trivia_session_id')->nullable();
                $table->text('summary');
                $table->json('metadata')->nullable();
                $table->timestamp('executed_at');
                $table->timestamps();

                $table->index(['action', 'executed_at'], 'tal_action_exec_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('trivia_automation_logs');
        Schema::dropIfExists('trivia_session_completions');
        Schema::dropIfExists('trivia_session_questions');
        Schema::dropIfExists('trivia_sessions');
    }
};
