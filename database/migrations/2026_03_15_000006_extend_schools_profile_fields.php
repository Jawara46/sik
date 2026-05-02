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
        $hasNamaSekolah = Schema::hasColumn('schools', 'nama_sekolah');
        $hasEmailSekolah = Schema::hasColumn('schools', 'email_sekolah');
        $hasWebSekolah = Schema::hasColumn('schools', 'web_sekolah');
        $hasAlamatSekolah = Schema::hasColumn('schools', 'alamat_sekolah');
        $hasTipeSekolah = Schema::hasColumn('schools', 'tipe_sekolah');
        $hasNamaKepsek = Schema::hasColumn('schools', 'nama_kepsek');
        $hasNipKepsek = Schema::hasColumn('schools', 'nip_kepsek');
        $hasTahunPelajaran = Schema::hasColumn('schools', 'tahun_pelajaran');
        $hasSemester = Schema::hasColumn('schools', 'semester');
        $hasLogo = Schema::hasColumn('schools', 'logo');
        $hasTtdKepsek = Schema::hasColumn('schools', 'ttd_kepsek');
        $hasStempelSekolah = Schema::hasColumn('schools', 'stempel_sekolah');
        $hasBgCountdown = Schema::hasColumn('schools', 'bg_countdown');

        Schema::table('schools', function (Blueprint $table) use (
            $hasNamaSekolah,
            $hasEmailSekolah,
            $hasWebSekolah,
            $hasAlamatSekolah,
            $hasTipeSekolah,
            $hasNamaKepsek,
            $hasNipKepsek,
            $hasTahunPelajaran,
            $hasSemester,
            $hasLogo,
            $hasTtdKepsek,
            $hasStempelSekolah,
            $hasBgCountdown
        ): void {
            if (!$hasNamaSekolah) {
                $table->string('nama_sekolah', 150)->nullable()->after('npsn');
            }
            if (!$hasEmailSekolah) {
                $table->string('email_sekolah', 150)->nullable()->after('nama_sekolah');
            }
            if (!$hasWebSekolah) {
                $table->string('web_sekolah', 255)->nullable()->after('email_sekolah');
            }
            if (!$hasAlamatSekolah) {
                $table->text('alamat_sekolah')->nullable()->after('web_sekolah');
            }
            if (!$hasTipeSekolah) {
                $table->enum('tipe_sekolah', ['SMP', 'MTs', 'SMK'])->default('SMP')->after('alamat_sekolah');
            }
            if (!$hasNamaKepsek) {
                $table->string('nama_kepsek', 150)->nullable()->after('tipe_sekolah');
            }
            if (!$hasNipKepsek) {
                $table->string('nip_kepsek', 50)->nullable()->after('nama_kepsek');
            }
            if (!$hasTahunPelajaran) {
                $table->string('tahun_pelajaran', 20)->nullable()->after('nip_kepsek');
            }
            if (!$hasSemester) {
                $table->enum('semester', ['Ganjil', 'Genap'])->default('Ganjil')->after('tahun_pelajaran');
            }
            if (!$hasLogo) {
                $table->string('logo')->nullable()->after('semester');
            }
            if (!$hasTtdKepsek) {
                $table->string('ttd_kepsek')->nullable()->after('logo');
            }
            if (!$hasStempelSekolah) {
                $table->string('stempel_sekolah')->nullable()->after('ttd_kepsek');
            }
            if (!$hasBgCountdown) {
                $table->string('bg_countdown')->nullable()->after('stempel_sekolah');
            }
        });

        if (
            Schema::hasColumn('schools', 'name')
            && Schema::hasColumn('schools', 'logo_path')
            && Schema::hasColumn('schools', 'signature_path')
            && Schema::hasColumn('schools', 'stamp_path')
        ) {
            $schools = DB::table('schools')->select([
                'id',
                'name',
                'logo_path',
                'signature_path',
                'stamp_path',
                'nama_sekolah',
                'logo',
                'ttd_kepsek',
                'stempel_sekolah',
            ])->get();

            foreach ($schools as $school) {
                DB::table('schools')
                    ->where('id', $school->id)
                    ->update([
                        'nama_sekolah' => $school->nama_sekolah ?? $school->name,
                        'logo' => $school->logo ?? $school->logo_path,
                        'ttd_kepsek' => $school->ttd_kepsek ?? $school->signature_path,
                        'stempel_sekolah' => $school->stempel_sekolah ?? $school->stamp_path,
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table): void {
            $columns = [
                'nama_sekolah',
                'email_sekolah',
                'web_sekolah',
                'alamat_sekolah',
                'tipe_sekolah',
                'nama_kepsek',
                'nip_kepsek',
                'tahun_pelajaran',
                'semester',
                'logo',
                'ttd_kepsek',
                'stempel_sekolah',
                'bg_countdown',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('schools', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
