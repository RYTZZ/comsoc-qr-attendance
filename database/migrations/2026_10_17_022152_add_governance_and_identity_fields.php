<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'member_id')) {
                $table->string('member_id', 32)->nullable()->unique()->after('id');
            }
            if (!Schema::hasColumn('users', 'is_registrar_imported')) {
                $table->boolean('is_registrar_imported')->default(false)->after('is_active');
            }
            if (!Schema::hasColumn('users', 'identity_verified_at')) {
                $table->timestamp('identity_verified_at')->nullable()->after('email_verified_at');
            }
            if (!Schema::hasColumn('users', 'identity_verification_method')) {
                $table->string('identity_verification_method', 50)->nullable()->after('identity_verified_at');
            }
        });

        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'is_registrar_imported')) {
                $table->boolean('is_registrar_imported')->default(false)->after('block');
            }
        });

        Schema::table('events', function (Blueprint $table) {
            if (!Schema::hasColumn('events', 'approved_by')) {
                $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete()->after('created_by');
            }
            if (!Schema::hasColumn('events', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
            if (!Schema::hasColumn('events', 'approval_notes')) {
                $table->text('approval_notes')->nullable()->after('approved_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['approved_by', 'approved_at', 'approval_notes']);
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['is_registrar_imported']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'member_id',
                'is_registrar_imported',
                'identity_verified_at',
                'identity_verification_method',
            ]);
        });
    }
};
