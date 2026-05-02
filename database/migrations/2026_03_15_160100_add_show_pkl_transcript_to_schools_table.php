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
        if (Schema::hasColumn('schools', 'show_pkl_transcript')) {
            return;
        }

        Schema::table('schools', function (Blueprint $table): void {
            $table->boolean('show_pkl_transcript')
                ->default(true)
                ->after('bg_countdown')
                ->comment('Tampilkan nilai PKL pada transkrip');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('schools', 'show_pkl_transcript')) {
            return;
        }

        Schema::table('schools', function (Blueprint $table): void {
            $table->dropColumn('show_pkl_transcript');
        });
    }
};

