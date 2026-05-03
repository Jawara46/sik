<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Admin User
        User::query()->updateOrCreate([
            'email' => env('ADMIN_EMAIL', 'admin@sik.local'),
        ], [
            'name' => env('ADMIN_NAME', 'Super Admin'),
            'password' => Hash::make(env('ADMIN_PASSWORD', 'password')),
            'email_verified_at' => now(),
        ]);

        // 2. Default School Data
        $school = \App\Models\School::query()->updateOrCreate([
            'npsn' => '12345678',
        ], [
            'name' => 'SMK Negeri 1 Contoh',
            'nama_sekolah' => 'SMK Negeri 1 Contoh',
            'email_sekolah' => 'info@smkn1contoh.sch.id',
            'telepon_sekolah' => '021-1234567',
            'alamat_sekolah' => 'Jl. Pendidikan No. 1, Kota Contoh',
            'tipe_sekolah' => 'SMK',
            'nama_kepsek' => 'Dr. H. Contoh Purwanto, M.Pd',
            'nip_kepsek' => '197001012000031001',
            'tahun_pelajaran' => '2023/2024',
            'semester' => 'Genap',
            'tanggal_surat' => now()->format('Y-m-d'),
            'bg_countdown' => 'assets/img/bg-lp.jpg',
            'use_envelope_animation' => true,
        ]);

        // 3. Default Major (Jurusan)
        $major = \App\Models\Major::query()->updateOrCreate([
            'school_id' => $school->id,
            'code' => 'AKL',
        ], [
            'name' => 'Akuntansi dan Keuangan Lembaga',
        ]);

        // 4. Default Subjects
        $subjects = [
            ['name' => 'Pendidikan Agama dan Budi Pekerti', 'category' => 'Umum'],
            ['name' => 'Pendidikan Pancasila dan Kewarganegaraan', 'category' => 'Umum'],
            ['name' => 'Bahasa Indonesia', 'category' => 'Umum'],
            ['name' => 'Matematika', 'category' => 'Umum'],
            ['name' => 'Sejarah Indonesia', 'category' => 'Umum'],
            ['name' => 'Bahasa Inggris', 'category' => 'Umum'],
            ['name' => 'Seni Budaya', 'category' => 'Umum'],
            ['name' => 'Pendidikan Jasmani, Olahraga dan Kesehatan', 'category' => 'Umum'],
            ['name' => 'Akuntansi Dasar', 'category' => 'C1'],
            ['name' => 'Perbankan Dasar', 'category' => 'C1'],
        ];

        foreach ($subjects as $s) {
            \App\Models\Subject::query()->updateOrCreate(
                ['name' => $s['name']],
                ['category' => $s['category']]
            );
        }

        // 5. Default Settings
        if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
            \Illuminate\Support\Facades\DB::table('settings')->updateOrInsert(
                ['key' => 'background_image'],
                ['value' => 'assets/img/bg-lp.jpg']
            );
            \Illuminate\Support\Facades\DB::table('settings')->updateOrInsert(
                ['key' => 'app_logo'],
                ['value' => 'assets/img/logo.png']
            );
        }
    }
}
