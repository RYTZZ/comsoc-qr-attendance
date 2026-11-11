<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Algorithm Challenges Bank
        Schema::create('arena_challenges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->string('slug')->unique();
            $table->enum('difficulty', ['novice', 'adept', 'master'])->default('novice');
            $table->string('category')->default('algorithms'); // arrays, strings, dynamic_programming, math, recursion
            $table->longText('description_markdown');
            $table->text('sample_input')->nullable();
            $table->text('sample_output')->nullable();
            $table->unsignedInteger('time_limit_ms')->default(2000);
            $table->unsignedInteger('memory_limit_mb')->default(128);
            $table->unsignedInteger('exp_reward')->default(50);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['difficulty', 'is_active']);
        });

        // Test Cases (Sample + Hidden Validation Cases)
        Schema::create('arena_test_cases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('arena_challenge_id')->constrained('arena_challenges')->cascadeOnDelete();
            $table->longText('input_data');
            $table->longText('expected_output');
            $table->boolean('is_hidden')->default(false);
            $table->timestamps();
            $table->index('arena_challenge_id');
        });

        // Student Submissions & Verdicts
        Schema::create('arena_submissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('arena_challenge_id')->constrained('arena_challenges')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('language', ['php', 'python', 'javascript'])->default('php');
            $table->longText('source_code');
            $table->enum('verdict', ['accepted', 'wrong_answer', 'runtime_error', 'time_limit_exceeded', 'pending'])->default('pending');
            $table->unsignedInteger('tests_passed')->default(0);
            $table->unsignedInteger('total_tests')->default(0);
            $table->unsignedInteger('execution_time_ms')->default(0);
            $table->text('compiler_output')->nullable();
            $table->unsignedInteger('exp_earned')->default(0);
            $table->timestamps();
            $table->index(['user_id', 'verdict']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arena_submissions');
        Schema::dropIfExists('arena_test_cases');
        Schema::dropIfExists('arena_challenges');
    }
};
