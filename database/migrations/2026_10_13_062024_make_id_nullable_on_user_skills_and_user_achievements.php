<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_skills', function (Blueprint $table) {
            $table->uuid('id')->nullable()->change();
        });

        Schema::table('user_achievements', function (Blueprint $table) {
            $table->uuid('id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('user_skills', function (Blueprint $table) {
            $table->uuid('id')->nullable(false)->change();
        });

        Schema::table('user_achievements', function (Blueprint $table) {
            $table->uuid('id')->nullable(false)->change();
        });
    }
};
