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
        if (Schema::hasTable('dynamic_qr_tokens') && !Schema::hasColumn('dynamic_qr_tokens', 'manual_token')) {
            Schema::table('dynamic_qr_tokens', function (Blueprint $table) {
                $table->string('manual_token', 16)->nullable()->after('token_hash')->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('dynamic_qr_tokens') && Schema::hasColumn('dynamic_qr_tokens', 'manual_token')) {
            Schema::table('dynamic_qr_tokens', function (Blueprint $table) {
                $table->dropIndex(['manual_token']);
                $table->dropColumn('manual_token');
            });
        }
    }
};
