<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('graduation_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('document_type', 30);
            $table->string('document_number', 120)->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->json('snapshot_payload')->nullable();
            $table->string('verification_token', 120)->unique();
            $table->string('pdf_path')->nullable();
            $table->string('pdf_hash', 64)->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('last_accessed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['student_id', 'document_type'], 'graduation_documents_student_type_unique');
            $table->index(['school_id', 'document_type', 'status'], 'graduation_documents_school_type_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('graduation_documents');
    }
};
