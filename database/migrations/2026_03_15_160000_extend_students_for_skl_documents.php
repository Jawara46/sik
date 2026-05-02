<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $hasTempatLahir = Schema::hasColumn('students', 'tempat_lahir');
        $hasTanggalLahir = Schema::hasColumn('students', 'tanggal_lahir');
        $hasNamaOrangTua = Schema::hasColumn('students', 'nama_orang_tua');
        $hasNomorWa = Schema::hasColumn('students', 'nomor_wa');
        $hasStatusAdministrasi = Schema::hasColumn('students', 'status_administrasi');

        Schema::table('students', function (Blueprint $table) use (
            $hasTempatLahir,
            $hasTanggalLahir,
            $hasNamaOrangTua,
            $hasNomorWa,
            $hasStatusAdministrasi
        ): void {
            if (!$hasTempatLahir) {
                $table->string('tempat_lahir', 120)->nullable()->after('name');
            }

            if (!$hasTanggalLahir) {
                $table->date('tanggal_lahir')->nullable()->after('tempat_lahir');
            }

            if (!$hasNamaOrangTua) {
                $table->string('nama_orang_tua', 150)->nullable()->after('tanggal_lahir');
            }

            if (!$hasNomorWa) {
                $table->string('nomor_wa', 20)->nullable()->after('nama_orang_tua');
            }

            if (!$hasStatusAdministrasi) {
                $table->boolean('status_administrasi')
                    ->default(true)
                    ->after('status')
                    ->comment('true = akses unduh diizinkan, false = dikunci');
            }
        });

        if (Schema::hasColumn('students', 'phone_number') && Schema::hasColumn('students', 'nomor_wa')) {
            DB::table('students')
                ->whereNull('nomor_wa')
                ->whereNotNull('phone_number')
                ->update([
                    'nomor_wa' => DB::raw('phone_number'),
                ]);
        }

        if (Schema::hasColumn('students', 'access_locked') && Schema::hasColumn('students', 'status_administrasi')) {
            DB::table('students')->update([
                'status_administrasi' => DB::raw('NOT access_locked'),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $hasTempatLahir = Schema::hasColumn('students', 'tempat_lahir');
        $hasTanggalLahir = Schema::hasColumn('students', 'tanggal_lahir');
        $hasNamaOrangTua = Schema::hasColumn('students', 'nama_orang_tua');
        $hasNomorWa = Schema::hasColumn('students', 'nomor_wa');
        $hasStatusAdministrasi = Schema::hasColumn('students', 'status_administrasi');

        Schema::table('students', function (Blueprint $table) use (
            $hasTempatLahir,
            $hasTanggalLahir,
            $hasNamaOrangTua,
            $hasNomorWa,
            $hasStatusAdministrasi
        ): void {
            if ($hasTempatLahir) {
                $table->dropColumn('tempat_lahir');
            }
            if ($hasTanggalLahir) {
                $table->dropColumn('tanggal_lahir');
            }
            if ($hasNamaOrangTua) {
                $table->dropColumn('nama_orang_tua');
            }
            if ($hasNomorWa) {
                $table->dropColumn('nomor_wa');
            }
            if ($hasStatusAdministrasi) {
                $table->dropColumn('status_administrasi');
            }
        });
    }
};

