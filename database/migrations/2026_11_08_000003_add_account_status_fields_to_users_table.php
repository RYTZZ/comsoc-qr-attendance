<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds suspension/reactivation/deletion metadata and Soft Deletes to users.
     * Existing `is_active` remains the login-eligibility flag; no data is altered.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'suspended_at')) {
                $table->timestamp('suspended_at')->nullable();
            }
            if (! Schema::hasColumn('users', 'suspended_by')) {
                $table->uuid('suspended_by')->nullable();
            }
            if (! Schema::hasColumn('users', 'suspension_reason')) {
                $table->text('suspension_reason')->nullable();
            }
            if (! Schema::hasColumn('users', 'reactivated_at')) {
                $table->timestamp('reactivated_at')->nullable();
            }
            if (! Schema::hasColumn('users', 'reactivated_by')) {
                $table->uuid('reactivated_by')->nullable();
            }
            if (! Schema::hasColumn('users', 'deleted_at')) {
                $table->softDeletes();
            }
            if (! Schema::hasColumn('users', 'deleted_by')) {
                $table->uuid('deleted_by')->nullable();
            }
            if (! Schema::hasColumn('users', 'deletion_reason')) {
                $table->text('deletion_reason')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['suspended_at', 'suspended_by', 'suspension_reason', 'reactivated_at', 'reactivated_by', 'deleted_by', 'deletion_reason'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
            if (Schema::hasColumn('users', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};

