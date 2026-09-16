<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memberships', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignUuid('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->enum('status', ['active', 'inactive'])->default('inactive');
            $table->decimal('fee_paid', 10, 2)->nullable();
            $table->date('paid_at')->nullable();
            $table->string('membership_number')->unique()->nullable();
            $table->timestamps();
            $table->unique(['student_id', 'academic_year_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memberships');
    }
};
