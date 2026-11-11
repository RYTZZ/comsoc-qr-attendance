<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Event Breakout Tracks / Sub-Sessions table
        Schema::create('event_tracks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('title');
            $table->string('track_category')->nullable(); // e.g. "AI & Data", "Cybersecurity", "Web3"
            $table->text('description')->nullable();
            $table->string('speaker_name')->nullable();
            $table->string('room_or_venue')->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->unsignedInteger('capacity_limit')->nullable(); // Nullable for unlimited
            $table->unsignedInteger('exp_points')->default(50);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['event_id', 'starts_at']);
        });

        // Event Track Registrations & Attendances
        Schema::create('event_track_attendances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('event_track_id')->constrained('event_tracks')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('scanned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['registered', 'attended', 'cancelled'])->default('attended');
            $table->timestamp('attended_at')->nullable();
            $table->timestamps();
            $table->unique(['event_track_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_track_attendances');
        Schema::dropIfExists('event_tracks');
    }
};
