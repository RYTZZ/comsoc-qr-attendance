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
        Schema::create('raffles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignUuid('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('prize_name');
            $table->integer('points_bonus')->default(0);
            $table->integer('winner_count')->default(1);
            $table->enum('status', ['draft', 'open', 'drawn', 'cancelled'])->default('draft');
            $table->timestamp('drawn_at')->nullable();
            $table->foreignUuid('drawn_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('raffle_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('raffle_id')->constrained('raffles')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('attendance_record_id')->nullable()->constrained('attendance_records')->nullOnDelete();
            $table->timestamp('eligible_at');
            $table->timestamps();

            $table->unique(['raffle_id', 'user_id']);
        });

        Schema::create('raffle_winners', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('raffle_id')->constrained('raffles')->cascadeOnDelete();
            $table->foreignUuid('raffle_entry_id')->constrained('raffle_entries')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->integer('draw_order')->default(1);
            $table->integer('points_awarded')->default(0);
            $table->boolean('is_claimed')->default(false);
            $table->timestamp('claimed_at')->nullable();
            $table->foreignUuid('claimed_by_staff_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('claim_notes')->nullable();
            $table->timestamps();

            $table->unique(['raffle_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('raffle_winners');
        Schema::dropIfExists('raffle_entries');
        Schema::dropIfExists('raffles');
    }
};
