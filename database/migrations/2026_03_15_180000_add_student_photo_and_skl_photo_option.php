<?php

declare(strict_types=1);

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
        if (!Schema::hasColumn('students', 'photo')) {
            Schema::table('students', function (Blueprint $table): void {
                $table->string('photo')->nullable()->after('name');
            });
        }

        if (!Schema::hasColumn('schools', 'show_student_photo_on_skl')) {
            Schema::table('schools', function (Blueprint $table): void {
                $table->boolean('show_student_photo_on_skl')
                    ->default(false)
                    ->after('show_pkl_transcript')
                    ->comment('Tampilkan pas foto siswa pada SKL');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('students', 'photo')) {
            Schema::table('students', function (Blueprint $table): void {
                $table->dropColumn('photo');
            });
        }

        if (Schema::hasColumn('schools', 'show_student_photo_on_skl')) {
            Schema::table('schools', function (Blueprint $table): void {
                $table->dropColumn('show_student_photo_on_skl');
            });
        }
    }
};

