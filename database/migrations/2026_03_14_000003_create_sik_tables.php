<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menjalankan Migrasi Database SIK.
     */
    public function up(): void
    {
        // 1. Tabel Schools (Data Sekolah)
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('npsn', 20)->unique();
            $table->string('logo_path')->nullable()->comment('Path/Base64 Logo Sekolah');
            $table->string('signature_path')->nullable()->comment('Path/Base64 TTD Kepala Sekolah');
            $table->string('stamp_path')->nullable()->comment('Path/Base64 Stempel Sekolah untuk Auto-PDF');
            $table->timestamps();
        });

        // 2. Tabel Subjects (Master Mata Pelajaran)
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->enum('category', [
                'Umum', 
                'Muatan Nasional', 
                'Kewilayahan', 
                'C1', 
                'C2', 
                'C3', 
                'UKK', 
                'PKL'
            ]);
            $table->timestamps();
        });

        // 3. Tabel Students (Data Peserta Didik)
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('nisn', 20)->unique()->index();
            $table->string('nis', 20)->nullable();
            $table->string('name', 150);
            $table->string('password'); // Password Hashed
            $table->string('phone_number', 20)->nullable()->comment('Nomor WA untuk Auto-Blast');
            $table->enum('status', ['Lulus', 'Tidak Lulus', 'Pending'])->default('Pending');
            $table->boolean('access_locked')->default(false)->comment('Kunci SKL/Transkrip');
            $table->timestamps();
        });

        // 4. Tabel Grades (Nilai)
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->decimal('score', 5, 2)->default(0);
            $table->string('location')->nullable()->comment('Khusus untuk menyimpan Lokasi PKL');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Mencegah duplikasi nilai
            $table->unique(['student_id', 'subject_id']);
        });

        // 5. Tabel WA Sessions (WhatsApp Gateway)
        Schema::create('wa_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->constrained('schools')->cascadeOnDelete();
            $table->string('session_id')->unique();
            $table->enum('status', ['DISCONNECTED', 'QR_READY', 'CONNECTED'])->default('DISCONNECTED');
            $table->text('qr_code')->nullable()->comment('Penyimpanan QR');
            $table->timestamps();
        });
    }

    /**
     * Membatalkan Migrasi (Rollback).
     */
    public function down(): void
    {
        Schema::dropIfExists('wa_sessions');
        Schema::dropIfExists('grades');
        Schema::dropIfExists('students');
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('schools');
    }
};
