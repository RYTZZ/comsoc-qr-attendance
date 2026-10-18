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
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS "pgcrypto";');
            DB::statement('ALTER TABLE user_skills ALTER COLUMN id SET DEFAULT gen_random_uuid();');
            DB::statement('ALTER TABLE user_achievements ALTER COLUMN id SET DEFAULT gen_random_uuid();');
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE user_skills ALTER COLUMN id DROP DEFAULT;');
            DB::statement('ALTER TABLE user_achievements ALTER COLUMN id DROP DEFAULT;');
        }
    }
};
