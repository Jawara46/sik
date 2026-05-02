<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * The columns numbering_mode and cert_pattern already exist.
     * This migration only updates the default cert_pattern value if it was the old format.
     */
    public function up(): void
    {
        if (!Schema::hasTable('smk_settings') || !Schema::hasColumn('smk_settings', 'cert_pattern')) {
            return;
        }

        // Update old default pattern to new format
        DB::table('smk_settings')
            ->where('cert_pattern', 'UKK/{MAJOR}/{YEAR}/{NO}')
            ->update([
                'cert_pattern' => '420/UKK.{JURUSAN}/{BULAN}/{TAHUN}/{NO}',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('smk_settings') || !Schema::hasColumn('smk_settings', 'cert_pattern')) {
            return;
        }

        // Revert back to old default
        DB::table('smk_settings')
            ->where('cert_pattern', '420/UKK.{JURUSAN}/{BULAN}/{TAHUN}/{NO}')
            ->update([
                'cert_pattern' => 'UKK/{MAJOR}/{YEAR}/{NO}',
            ]);
    }
};
