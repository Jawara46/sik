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
        Schema::create('smk_assessors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('major_id')->constrained()->cascadeOnDelete();
            $table->string('internal_name')->nullable();
            $table->string('internal_nip')->nullable();
            $table->string('external_name')->nullable();
            $table->string('external_company')->nullable();
            $table->string('external_position')->nullable();
            $table->timestamps();

            // Sinergi unik agar satu jurusan di sebuah sekolah hanya ada 1 mapping penguji.
            $table->unique(['school_id', 'major_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('smk_assessors');
    }
};
