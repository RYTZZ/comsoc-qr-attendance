<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Evaluation Templates (Managed by Admins)
        if (!Schema::hasTable('evaluation_templates')) {
            Schema::create('evaluation_templates', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('title');
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('version')->default(1);
                $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 2. Evaluation Questions (Belongs to a Template)
        if (!Schema::hasTable('evaluation_questions')) {
            Schema::create('evaluation_questions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('evaluation_template_id')->constrained('evaluation_templates')->cascadeOnDelete();
                $table->string('question_text');
                $table->string('question_type'); // single_choice, multiple_choice, rating, short_text, long_text
                $table->json('options')->nullable(); // For single_choice and multiple_choice
                $table->unsignedSmallInteger('rating_min')->default(1);
                $table->unsignedSmallInteger('rating_max')->default(5);
                $table->string('rating_min_label')->nullable();
                $table->string('rating_max_label')->nullable();
                $table->boolean('is_required')->default(true);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        // 3. Add evaluation_template_id and evaluation_snapshot to events table
        if (Schema::hasTable('events')) {
            Schema::table('events', function (Blueprint $table) {
                if (!Schema::hasColumn('events', 'evaluation_template_id')) {
                    $table->foreignUuid('evaluation_template_id')->nullable()->constrained('evaluation_templates')->nullOnDelete();
                }
                if (!Schema::hasColumn('events', 'evaluation_snapshot')) {
                    $table->json('evaluation_snapshot')->nullable();
                }
            });
        }

        // 4. Student Evaluation Submissions
        if (!Schema::hasTable('event_evaluations')) {
            Schema::create('event_evaluations', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('event_id')->constrained('events')->cascadeOnDelete();
                $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignUuid('evaluation_template_id')->nullable()->constrained('evaluation_templates')->nullOnDelete();
                $table->unsignedInteger('template_version')->default(1);
                $table->timestamp('submitted_at');
                $table->timestamps();

                $table->unique(['event_id', 'user_id'], 'event_user_eval_unique');
            });
        }

        // 5. Individual Evaluation Answers
        if (!Schema::hasTable('event_evaluation_answers')) {
            Schema::create('event_evaluation_answers', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('event_evaluation_id')->constrained('event_evaluations')->cascadeOnDelete();
                $table->uuid('evaluation_question_id')->nullable();
                $table->string('question_text');
                $table->string('question_type');
                $table->text('answer_text')->nullable();
                $table->unsignedSmallInteger('rating_value')->nullable();
                $table->json('selected_options')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('event_evaluation_answers');
        Schema::dropIfExists('event_evaluations');

        if (Schema::hasTable('events')) {
            Schema::table('events', function (Blueprint $table) {
                if (Schema::hasColumn('events', 'evaluation_template_id')) {
                    $table->dropForeign(['evaluation_template_id']);
                    $table->dropColumn('evaluation_template_id');
                }
                if (Schema::hasColumn('events', 'evaluation_snapshot')) {
                    $table->dropColumn('evaluation_snapshot');
                }
            });
        }

        Schema::dropIfExists('evaluation_questions');
        Schema::dropIfExists('evaluation_templates');
    }
};
