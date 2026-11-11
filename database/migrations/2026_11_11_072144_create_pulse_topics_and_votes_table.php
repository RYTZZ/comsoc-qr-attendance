<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Anonymous Student Voice & Org Feedback Topics
        Schema::create('pulse_topics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('category', ['lab_facilities', 'curriculum', 'org_governance', 'student_welfare', 'general'])->default('general');
            $table->string('title');
            $table->text('content');
            $table->string('blind_session_hash', 64)->index(); // Blind one-way hash to throttle without storing user_id
            $table->enum('status', ['under_review', 'in_progress', 'resolved', 'closed'])->default('under_review');
            $table->text('official_response')->nullable();
            $table->foreignUuid('responded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('responded_at')->nullable();
            $table->unsignedInteger('votes_count')->default(1);
            $table->boolean('is_pinned')->default(false);
            $table->timestamps();
            $table->index(['category', 'status']);
        });

        // Anonymous Votes (Zero user_id stored)
        Schema::create('pulse_votes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('pulse_topic_id')->constrained('pulse_topics')->cascadeOnDelete();
            $table->string('voter_hash', 64);
            $table->timestamps();
            $table->unique(['pulse_topic_id', 'voter_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pulse_votes');
        Schema::dropIfExists('pulse_topics');
    }
};
