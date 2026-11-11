<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Student Projects & Code Submissions for Peer Review
        Schema::create('code_showcases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description');
            $table->string('repository_url')->nullable();
            $table->string('live_demo_url')->nullable();
            $table->longText('code_snippet')->nullable(); // For short direct code review
            $table->string('language')->default('php'); // php, javascript, python, cpp, java, rust, sql
            $table->json('tech_stack')->nullable(); // ['laravel', 'tailwind', 'vue']
            $table->json('review_goals')->nullable(); // ['security', 'performance', 'architecture', 'ui_ux']
            $table->unsignedInteger('bounty_exp')->default(0);
            $table->enum('status', ['open', 'in_review', 'solved', 'closed'])->default('open');
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('reviews_count')->default(0);
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });

        // Peer Reviews & Line Comments
        Schema::create('code_critiques', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('code_showcase_id')->constrained('code_showcases')->cascadeOnDelete();
            $table->foreignUuid('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->text('summary');
            $table->longText('suggested_diff')->nullable(); // Markdown diff or code block
            $table->unsignedInteger('line_number')->nullable();
            $table->boolean('is_accepted')->default(false);
            $table->unsignedInteger('helpful_votes')->default(0);
            $table->timestamps();
            $table->index(['code_showcase_id', 'is_accepted']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('code_critiques');
        Schema::dropIfExists('code_showcases');
    }
};
