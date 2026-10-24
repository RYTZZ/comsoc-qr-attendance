<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_signatures', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            // Laravel-encrypted PNG bytes (base64 envelope with MAC/auth tag). Never plaintext.
            $table->longText('encrypted_payload');
            $table->string('image_format', 10)->default('png');
            $table->unsignedSmallInteger('data_version')->default(1);
            $table->unsignedSmallInteger('width');
            $table->unsignedSmallInteger('height');
            $table->unsignedInteger('version')->default(1);
            $table->string('status', 12)->default('active'); // active | superseded
            $table->timestamp('consent_acknowledged_at');
            $table->string('consent_version', 20)->default('v1');
            $table->timestamp('replaced_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->unique(['user_id', 'version']);
        });

        Schema::create('attendance_sheet_issuances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignUuid('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('storage_path'); // private disk, content encrypted
            $table->string('pdf_sha256', 64);
            $table->unsignedInteger('attendee_count');
            $table->unsignedInteger('missing_signature_count')->default(0);
            // {user_id: signature_id|null} -- references only, no signature data.
            $table->json('signature_refs');
            $table->foreignUuid('reissue_of_id')->nullable()->constrained('attendance_sheet_issuances')->nullOnDelete();
            $table->text('reissue_reason')->nullable();
            $table->timestamp('issued_at');
            $table->timestamps();

            $table->index(['event_id', 'issued_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_sheet_issuances');
        Schema::dropIfExists('student_signatures');
    }
};
