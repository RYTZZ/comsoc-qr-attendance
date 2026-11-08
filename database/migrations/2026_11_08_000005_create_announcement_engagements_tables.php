<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('announcement_reactions')) {
            Schema::create('announcement_reactions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('announcement_id')->constrained('announcements')->cascadeOnDelete();
                $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('reaction_type', 20); // like, love, haha, wow, celebrate, support
                $table->timestamps();

                $table->unique(['announcement_id', 'user_id']);
                $table->index(['announcement_id', 'reaction_type']);
            });
        }

        if (!Schema::hasTable('announcement_comments')) {
            Schema::create('announcement_comments', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('announcement_id')->constrained('announcements')->cascadeOnDelete();
                $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignUuid('parent_id')->nullable()->constrained('announcement_comments')->cascadeOnDelete();
                $table->text('content');
                $table->string('status', 20)->default('active'); // active, hidden
                $table->timestamps();
                $table->softDeletes();

                $table->index(['announcement_id', 'parent_id', 'status']);
                $table->index(['user_id', 'status']);
            });
        }

        if (!Schema::hasTable('announcement_comment_reports')) {
            Schema::create('announcement_comment_reports', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('comment_id')->constrained('announcement_comments')->cascadeOnDelete();
                $table->foreignUuid('reporter_id')->constrained('users')->cascadeOnDelete();
                $table->string('reason', 100);
                $table->text('details')->nullable();
                $table->string('status', 20)->default('pending'); // pending, reviewed, dismissed
                $table->foreignUuid('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();

                $table->unique(['comment_id', 'reporter_id']);
                $table->index(['comment_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_comment_reports');
        Schema::dropIfExists('announcement_comments');
        Schema::dropIfExists('announcement_reactions');
    }
};

