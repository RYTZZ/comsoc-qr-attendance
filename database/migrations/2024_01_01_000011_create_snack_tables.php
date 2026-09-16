<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('snack_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', ['morning', 'afternoon', 'other'])->default('other');
            $table->time('available_from')->nullable();
            $table->time('available_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('snack_inventories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('snack_session_id')->constrained('snack_sessions')->cascadeOnDelete();
            $table->string('item_name');
            $table->integer('total_quantity')->default(0);
            $table->integer('distributed_quantity')->default(0);
            $table->timestamps();
        });

        Schema::create('snack_claims', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('snack_session_id')->constrained('snack_sessions')->cascadeOnDelete();
            $table->foreignUuid('snack_inventory_id')->constrained('snack_inventories')->cascadeOnDelete();
            $table->string('qr_token', 64);
            $table->string('participant_type');
            $table->uuid('participant_id');
            $table->integer('quantity')->default(1);
            $table->foreignUuid('kiosk_id')->nullable()->constrained('kiosks')->nullOnDelete();
            $table->foreignUuid('distributed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('claimed_at');
            $table->timestamps();
            $table->unique(['snack_session_id', 'qr_token'], 'unique_snack_claim');
            $table->index('qr_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('snack_claims');
        Schema::dropIfExists('snack_inventories');
        Schema::dropIfExists('snack_sessions');
    }
};
