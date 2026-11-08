<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('email_verification_codes')) {
            Schema::create('email_verification_codes', function (Blueprint $table) {
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

                $table->index(['user_id', 'email', 'expires_at'], 'evc_user_email_expires_idx');
                $table->index(['user_id', 'created_at'], 'evc_user_created_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('email_verification_codes');
    }
};

