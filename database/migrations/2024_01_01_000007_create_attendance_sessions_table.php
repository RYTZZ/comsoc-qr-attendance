<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('event_id')->constrained('events')->cascadeOnDelete();
            $table->enum('type', ['morning_in', 'morning_out', 'afternoon_in', 'afternoon_out']);
            $table->time('opens_at');
            $table->time('closes_at');
            $table->time('late_threshold')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['event_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_sessions');
    }
};
