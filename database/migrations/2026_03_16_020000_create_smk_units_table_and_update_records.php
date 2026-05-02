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
        // 1. Table for Master Unit Competency per Major
        Schema::create('smk_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('major_id')->constrained('majors')->cascadeOnDelete();
            $table->string('kode_unit');
            $table->string('judul_unit');
            $table->timestamps();
        });

        // 2. Table for Student's Competency Unit Scores
        Schema::create('smk_record_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('smk_record_id')->constrained('smk_records')->cascadeOnDelete();
            $table->foreignId('smk_unit_id')->constrained('smk_units')->cascadeOnDelete();
            $table->decimal('score', 5, 2)->nullable();
            $table->timestamps();
            
            // A student's record can only have one score per specific unit
            $table->unique(['smk_record_id', 'smk_unit_id']);
        });

        // 3. Extend existing smk_records with certificate fields
        Schema::table('smk_records', function (Blueprint $table) {
            $table->string('certificate_number')->nullable()->after('ukk_status');
            $table->date('exam_date')->nullable()->after('certificate_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('smk_records', function (Blueprint $table) {
            $table->dropColumn(['certificate_number', 'exam_date']);
        });
        
        Schema::dropIfExists('smk_record_units');
        Schema::dropIfExists('smk_units');
    }
};
