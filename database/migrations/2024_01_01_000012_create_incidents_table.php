<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->foreignUuid('reported_by')->constrained('users')->cascadeOnDelete();
            $table->enum('category', [
                'qr_identity_issue',
                'attendance_issue',
                'disruptive_conduct',
                'harassment_bullying',
                'property_issue',
                'safety_concern',
                'other',
            ]);
            $table->string('subject_type')->nullable();
            $table->uuid('subject_id')->nullable();
            $table->text('description');
            $table->enum('status', ['open', 'resolved', 'archived'])->default('open');
            $table->foreignUuid('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
