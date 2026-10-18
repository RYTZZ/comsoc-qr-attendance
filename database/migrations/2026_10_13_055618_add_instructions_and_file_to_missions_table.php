<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('missions', function (Blueprint $table) {
            $table->text('objectives')->nullable()->after('description');
            $table->text('instructions')->nullable()->after('objectives');
            $table->boolean('allow_resubmission')->default(true)->after('submission_type');
        });

        Schema::table('mission_submissions', function (Blueprint $table) {
            $table->string('file_path')->nullable()->after('proof_url');
        });
    }

    public function down(): void
    {
        Schema::table('mission_submissions', function (Blueprint $table) {
            $table->dropColumn('file_path');
        });

        Schema::table('missions', function (Blueprint $table) {
            $table->dropColumn([
                'objectives',
                'instructions',
                'allow_resubmission',
            ]);
        });
    }
};
