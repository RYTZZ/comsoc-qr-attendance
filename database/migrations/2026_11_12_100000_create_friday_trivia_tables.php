<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('trivia_questions')) {
            Schema::create('trivia_questions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->text('question');
                $table->string('category', 40)->default('general_tech');
                $table->json('options');
                $table->unsignedTinyInteger('correct_index');
                $table->text('explanation')->nullable();
                $table->unsignedSmallInteger('points')->default(10);
                $table->string('feedback_mode', 20)->default('after_close'); // immediate | after_close
                $table->string('status', 20)->default('draft'); // draft | published
                $table->timestamp('publish_at')->nullable();
                $table->timestamp('closes_at')->nullable();
                $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['status', 'publish_at', 'closes_at'], 'trivia_state_idx');
            });
        }

        if (! Schema::hasTable('trivia_answers')) {
            Schema::create('trivia_answers', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('trivia_question_id')->constrained('trivia_questions')->cascadeOnDelete();
                $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
                $table->unsignedTinyInteger('selected_index');
                $table->boolean('is_correct')->default(false);
                $table->unsignedSmallInteger('points_awarded')->default(0);
                $table->timestamps();

                // Hard database guarantee: one submission per member per question.
                $table->unique(['trivia_question_id', 'user_id'], 'trivia_one_answer_per_user');
                $table->index('user_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('trivia_answers');
        Schema::dropIfExists('trivia_questions');
    }
};

