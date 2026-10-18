<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->foreignUuid('user_id')->nullable()->after('event_id')->constrained('users')->nullOnDelete();
            $table->string('ticket_code', 32)->nullable()->unique()->after('status');
            $table->index(['user_id', 'status']);
        });

        Schema::table('events', function (Blueprint $table) {
            $table->string('cover_image')->nullable()->after('logo_path');
            $table->text('participation_requirements')->nullable()->after('venue_details');
            $table->integer('reward_xp')->default(50)->after('snack_distribution_enabled');
            $table->integer('reward_points')->default(20)->after('reward_xp');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'cover_image',
                'participation_requirements',
                'reward_xp',
                'reward_points',
            ]);
        });

        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn('ticket_code');
        });
    }
};
