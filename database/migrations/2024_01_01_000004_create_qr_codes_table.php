<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_codes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('membership_id')->constrained('memberships')->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->enum('status', ['generated', 'active', 'revoked', 'expired'])->default('generated');
            $table->integer('batch_number')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->string('revoke_reason')->nullable();
            $table->timestamps();
            $table->index('token');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_codes');
    }
};
