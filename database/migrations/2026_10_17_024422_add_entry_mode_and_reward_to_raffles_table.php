<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('raffles', function (Blueprint $table) {
            $table->foreignUuid('reward_id')->nullable()->constrained('rewards')->nullOnDelete();
            $table->string('entry_type')->default('attendance'); // 'attendance', 'points_purchase', 'tickets'
            $table->integer('points_per_entry')->default(0);
            $table->integer('max_entries_per_user')->default(1);
        });

        Schema::table('raffle_entries', function (Blueprint $table) {
            $table->string('source_type')->default('attendance'); // 'attendance', 'points_purchase', 'ticket'
            $table->integer('points_deducted')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('raffle_entries', function (Blueprint $table) {
            $table->dropColumn(['source_type', 'points_deducted']);
        });

        Schema::table('raffles', function (Blueprint $table) {
            $table->dropForeign(['reward_id']);
            $table->dropColumn(['reward_id', 'entry_type', 'points_per_entry', 'max_entries_per_user']);
        });
    }
};
