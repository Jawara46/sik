<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table): void {
            if (!Schema::hasColumn('schools', 'tempat_surat')) {
                $table->string('tempat_surat', 120)->nullable()->after('alamat_sekolah');
            }

            if (!Schema::hasColumn('schools', 'tanggal_surat')) {
                $table->date('tanggal_surat')->nullable()->after('semester');
            }
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table): void {
            if (Schema::hasColumn('schools', 'tempat_surat')) {
                $table->dropColumn('tempat_surat');
            }

            if (Schema::hasColumn('schools', 'tanggal_surat')) {
                $table->dropColumn('tanggal_surat');
            }
        });
    }
};
