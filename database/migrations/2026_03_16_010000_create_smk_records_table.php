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
        Schema::create('smk_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            
            // PKL (Praktik Kerja Lapangan)
            $table->string('company_name')->nullable();
            $table->decimal('pkl_score', 5, 2)->nullable();
            
            // UKK (Uji Kompetensi Keahlian)
            $table->decimal('ukk_internal_score', 5, 2)->nullable();
            $table->decimal('ukk_external_score', 5, 2)->nullable();
            $table->decimal('ukk_final_score', 5, 2)->nullable();
            
            // Enum Status: Kompeten, Tidak Kompeten, Sangat Kompeten
            $table->string('ukk_status')->nullable();
            
            $table->timestamps();
            
            // A student only has one SMK record logically for graduation
            $table->unique('student_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('smk_records');
    }
};
