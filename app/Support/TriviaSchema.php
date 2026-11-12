<?php

namespace App\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TriviaSchema
{
    /**
     * Ensure the Friday Trivia tables exist gracefully.
     * Prevents database query exceptions on production if migrations have not yet been executed.
     */
    public static function ensureTables(): void
    {
        try {
            if (! Schema::hasTable('trivia_questions')) {
                Schema::create('trivia_questions', function (Blueprint $table) {
                    $table->uuid('id')->primary();
                    $table->text('question');
                    $table->string('category', 40)->default('general_tech');
                    $table->json('options');
                    $table->unsignedTinyInteger('correct_index');
                    $table->text('explanation')->nullable();
                    $table->unsignedSmallInteger('points')->default(10);
                    $table->string('feedback_mode', 20)->default('after_close');
                    $table->string('status', 20)->default('draft');
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

                    $table->unique(['trivia_question_id', 'user_id'], 'trivia_one_answer_per_user');
                    $table->index('user_id');
                });
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('TriviaSchema::ensureTables encountered an error: ' . $e->getMessage());
        }
    }
}
