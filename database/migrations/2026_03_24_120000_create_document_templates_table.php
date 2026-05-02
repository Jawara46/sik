<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_templates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('document_type', 50);
            $table->string('name', 120);
            $table->longText('title_html')->nullable();
            $table->longText('intro_html')->nullable();
            $table->longText('body_html')->nullable();
            $table->longText('closing_html')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'document_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_templates');
    }
};
