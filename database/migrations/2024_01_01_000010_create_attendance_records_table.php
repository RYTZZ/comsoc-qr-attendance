<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignUuid('attendance_session_id')->constrained('attendance_sessions')->cascadeOnDelete();
            $table->string('qr_token', 64);
            $table->string('participant_type');
            $table->uuid('participant_id');
            $table->enum('action', ['in', 'out']);
            $table->enum('status', ['present', 'late'])->default('present');
            $table->foreignUuid('kiosk_id')->nullable()->constrained('kiosks')->nullOnDelete();
            $table->foreignUuid('scanned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('scanned_at');
            $table->boolean('is_corrected')->default(false);
            $table->foreignUuid('corrected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('corrected_at')->nullable();
            $table->text('correction_reason')->nullable();
            $table->timestamps();
            $table->unique(['attendance_session_id', 'qr_token', 'action'], 'unique_session_scan');
            $table->index(['event_id', 'participant_id']);
            $table->index('qr_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
