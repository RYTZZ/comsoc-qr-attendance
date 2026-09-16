<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('event_type')->nullable()->after('name');
            $table->string('organizer')->nullable()->after('event_type');
            $table->string('contact_info')->nullable()->after('organizer');
            $table->string('logo_path')->nullable()->after('contact_info');
            $table->string('venue_name')->nullable()->after('location');
            $table->string('venue_address')->nullable()->after('venue_name');
            $table->text('venue_details')->nullable()->after('venue_address');
            $table->datetime('registration_opens_at')->nullable()->after('ends_at');
            $table->time('attendance_starts_at')->nullable()->after('registration_deadline');
            $table->time('attendance_ends_at')->nullable()->after('attendance_starts_at');
            $table->unsignedInteger('max_participants')->nullable()->after('attendance_ends_at');
            $table->string('status', 30)->default('draft')->after('is_published');
            $table->boolean('attendance_enabled')->default(true)->after('status');
            $table->boolean('snack_distribution_enabled')->default(true)->after('attendance_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'event_type',
                'organizer',
                'contact_info',
                'logo_path',
                'venue_name',
                'venue_address',
                'venue_details',
                'registration_opens_at',
                'attendance_starts_at',
                'attendance_ends_at',
                'max_participants',
                'status',
                'attendance_enabled',
                'snack_distribution_enabled',
            ]);
        });
    }
};
