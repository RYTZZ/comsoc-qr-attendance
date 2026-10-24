<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add public visibility and public slug to events if not present
        Schema::table('events', function (Blueprint $table) {
            if (!Schema::hasColumn('events', 'is_public')) {
                $table->boolean('is_public')->default(false)->after('is_published');
            }
            if (!Schema::hasColumn('events', 'public_slug')) {
                $table->string('public_slug', 255)->nullable()->unique()->after('name');
            }
        });

        // 2. Organization Officers for Public Leadership
        Schema::create('org_officers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name', 255);
            $table->string('position', 150);
            $table->string('program', 150)->nullable();
            $table->text('bio')->nullable();
            $table->string('portrait_path', 500)->nullable();
            $table->string('social_link', 500)->nullable();
            $table->string('category', 50)->default('executive'); // executive, board, adviser
            $table->integer('sort_order')->default(0);
            $table->boolean('is_current')->default(true);
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->index(['is_current', 'is_published', 'sort_order']);
        });

        // 3. Organization Achievements & Milestones
        Schema::create('org_achievements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('date_or_year', 100)->nullable();
            $table->string('image_path', 500)->nullable();
            $table->string('award_details', 255)->nullable();
            $table->string('verification_reference', 500)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->index(['is_published', 'sort_order']);
        });

        // 4. Approved Public Gallery
        Schema::create('org_gallery_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->string('title', 255)->nullable();
            $table->string('image_path', 500);
            $table->string('caption', 500)->nullable();
            $table->string('alt_text', 255)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->index(['is_published', 'sort_order']);
        });

        // 5. Approved Partners & Collaborations
        Schema::create('org_partners', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 255);
            $table->string('logo_path', 500)->nullable();
            $table->text('description')->nullable();
            $table->string('collaboration_type', 150)->nullable(); // Academic, Industry, Community, Government
            $table->string('website_url', 500)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->index(['is_published', 'sort_order']);
        });

        // 6. Organization FAQs
        Schema::create('org_faqs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('question', 500);
            $table->text('answer');
            $table->string('category', 100)->default('General');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->index(['is_published', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('org_faqs');
        Schema::dropIfExists('org_partners');
        Schema::dropIfExists('org_gallery_items');
        Schema::dropIfExists('org_achievements');
        Schema::dropIfExists('org_officers');

        Schema::table('events', function (Blueprint $table) {
            if (Schema::hasColumn('events', 'public_slug')) {
                $table->dropColumn('public_slug');
            }
            if (Schema::hasColumn('events', 'is_public')) {
                $table->dropColumn('is_public');
            }
        });
    }
};
