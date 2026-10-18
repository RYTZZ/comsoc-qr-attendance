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
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('tshirt_enabled')->default(true)->after('requires_registration');
            $table->boolean('tshirt_required')->default(false)->after('tshirt_enabled');
            $table->boolean('dietary_enabled')->default(true)->after('tshirt_required');
            $table->boolean('dietary_required')->default(false)->after('dietary_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['tshirt_enabled', 'tshirt_required', 'dietary_enabled', 'dietary_required']);
        });
    }
};
