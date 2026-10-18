<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('announcements')) {
            Schema::create('announcements', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('title');
                $table->text('body');
                $table->string('priority')->default('normal');
                $table->string('category')->default('general');
                $table->string('target_url')->nullable();
                $table->timestamp('published_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->foreignUuid('posted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['is_active', 'published_at', 'expires_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
