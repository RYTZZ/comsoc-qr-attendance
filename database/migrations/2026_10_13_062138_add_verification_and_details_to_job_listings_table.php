<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_listings', function (Blueprint $table) {
            if (!Schema::hasColumn('job_listings', 'is_verified')) {
                $table->boolean('is_verified')->default(true)->after('is_active');
            }
            if (!Schema::hasColumn('job_listings', 'compensation')) {
                $table->string('compensation')->nullable()->after('is_remote');
            }
            if (!Schema::hasColumn('job_listings', 'posted_by')) {
                $table->foreignUuid('posted_by')->nullable()->after('is_verified')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('job_listings', function (Blueprint $table) {
            $table->dropForeign(['posted_by']);
            $table->dropColumn(['is_verified', 'compensation', 'posted_by']);
        });
    }
};
