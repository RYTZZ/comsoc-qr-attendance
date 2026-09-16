<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->string('tshirt_size', 20)->nullable()->after('organization');
            $table->string('year_level', 50)->nullable()->after('tshirt_size');
            $table->string('program')->nullable()->after('year_level');
            $table->string('food_restrictions', 50)->default('None')->after('program');
            $table->text('food_restriction_details')->nullable()->after('food_restrictions');
            $table->boolean('confirmed')->default(false)->after('food_restriction_details');
            $table->unique(['event_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropUnique(['event_id', 'email']);
            $table->dropColumn([
                'tshirt_size',
                'year_level',
                'program',
                'food_restrictions',
                'food_restriction_details',
                'confirmed',
            ]);
        });
    }
};
