<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('qr_code_id')->constrained('qr_codes')->cascadeOnDelete();
            $table->enum('status', ['for_claiming', 'claimed', 'lost', 'reissued'])->default('for_claiming');
            $table->foreignUuid('claimed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('claimed_at')->nullable();
            $table->foreignUuid('reissued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reissued_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cards');
    }
};
