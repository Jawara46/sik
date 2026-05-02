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
        // Drop the old smk_settings table to avoid conflict and clean up database
        Schema::dropIfExists('smk_settings');

        // Add certificate_number_pattern to schools table
        Schema::table('schools', function (Blueprint $table) {
            $table->string('certificate_number_pattern', 150)->nullable()->after('transcript_number_pattern');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn('certificate_number_pattern');
        });

        // Recreate the smk_settings table in case of rollback
        Schema::create('smk_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->enum('numbering_mode', ['static', 'dynamic'])->default('dynamic');
            $table->string('cert_pattern')->default('420/UKK.{JURUSAN}/{BULAN}/{TAHUN}/{NO}');
            $table->timestamps();
        });
    }
};
