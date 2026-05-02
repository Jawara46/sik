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
        Schema::table('schools', function (Blueprint $table) {
            $table->enum('skl_number_mode', ['dynamic', 'static'])->default('dynamic')->after('skl_number_pattern');
            $table->enum('transcript_number_mode', ['dynamic', 'static'])->default('dynamic')->after('transcript_number_pattern');
            $table->enum('certificate_number_mode', ['dynamic', 'static'])->default('dynamic')->after('certificate_number_pattern');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn(['skl_number_mode', 'transcript_number_mode', 'certificate_number_mode']);
        });
    }
};
