<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
            $table->timestamp('last_activity_at')->nullable()->after('is_active');
            $table->foreignUuid('created_by')->nullable()->after('student_id')->constrained('users')->nullOnDelete();
        });

        Schema::table('kiosks', function (Blueprint $table) {
            $table->foreignUuid('user_id')->nullable()->unique()->after('id')->constrained('users')->nullOnDelete();
            $table->foreignUuid('assigned_event_id')->nullable()->after('assigned_staff_id')->constrained('events')->nullOnDelete();
            $table->foreignUuid('created_by')->nullable()->after('is_active')->constrained('users')->nullOnDelete();
            $table->timestamp('last_activity_at')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('kiosks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropConstrainedForeignId('assigned_event_id');
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn('last_activity_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'last_activity_at']);
            $table->dropConstrainedForeignId('created_by');
        });
    }
};
