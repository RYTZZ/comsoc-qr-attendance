<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('user_profiles', 'tech_stack')) {
                $table->json('tech_stack')->nullable()->after('interests');
            }
            if (!Schema::hasColumn('user_profiles', 'project_categories')) {
                $table->json('project_categories')->nullable()->after('collaboration_preferences');
            }
            if (!Schema::hasColumn('user_profiles', 'open_to_collaborate')) {
                $table->boolean('open_to_collaborate')->default(true)->after('is_public');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn(['tech_stack', 'project_categories', 'open_to_collaborate']);
        });
    }
};
