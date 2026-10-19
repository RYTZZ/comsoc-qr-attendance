<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('session_id_hash', 64)->index();
            $table->string('device_type', 30)->default('Desktop');
            $table->string('platform', 50)->default('Unknown OS');
            $table->string('browser', 50)->default('Unknown Browser');
            $table->string('device_signature', 64)->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('first_authenticated_at');
            $table->timestamp('last_active_at');
            $table->timestamp('revoked_at')->nullable()->index();
            $table->timestamps();

            $table->index(['user_id', 'revoked_at']);
            $table->index(['user_id', 'device_signature']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_sessions');
    }
};
