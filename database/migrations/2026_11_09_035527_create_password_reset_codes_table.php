<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('password_reset_codes')) {
            Schema::create('password_reset_codes', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('email', 255);
                $table->string('code_hash', 255);
                $table->unsignedTinyInteger('attempts')->default(0);
                $table->timestamp('expires_at');
                $table->timestamp('consumed_at')->nullable();
                $table->timestamp('invalidated_at')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamps();

                $table->index(['user_id', 'email', 'expires_at'], 'prc_user_email_expires_idx');
                $table->index(['user_id', 'created_at'], 'prc_user_created_idx');
                $table->index(['email', 'expires_at'], 'prc_email_expires_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_codes');
    }
};
