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
        Schema::dropIfExists('organization_documents');

        Schema::create('organization_documents', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique(); // e.g. 'cbl'
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('file_path', 500);
            $table->string('original_filename', 255);
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('mime_type', 100)->default('application/pdf');
            $table->string('file_hash', 64)->nullable(); // SHA-256 for integrity verification
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_published')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->foreignUuid('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organization_documents');
    }
};
