<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('graduation_document_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('graduation_document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('admin_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_type', 20);
            $table->string('action', 30);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['graduation_document_id', 'action'], 'graduation_document_logs_doc_action_index');
            $table->index(['actor_type', 'action'], 'graduation_document_logs_actor_action_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('graduation_document_logs');
    }
};
