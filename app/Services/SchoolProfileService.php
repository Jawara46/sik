<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\School;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class SchoolProfileService
{
    public function getCurrentSchool(): School
    {
        $school = School::query()->first();
        if ($school !== null) {
            return $school;
        }

        $defaults = [
            'npsn' => '00000000',
            'name' => 'Sekolah Belum Diatur',
        ];

        if (Schema::hasColumn('schools', 'nama_sekolah')) {
            $defaults['nama_sekolah'] = 'Sekolah Belum Diatur';
        }
        if (Schema::hasColumn('schools', 'tipe_sekolah')) {
            $defaults['tipe_sekolah'] = 'SMP';
        }
        if (Schema::hasColumn('schools', 'tempat_surat')) {
            $defaults['tempat_surat'] = 'Kabupaten';
        }
        if (Schema::hasColumn('schools', 'semester')) {
            $defaults['semester'] = 'Ganjil';
        }
        if (Schema::hasColumn('schools', 'show_pkl_transcript')) {
            $defaults['show_pkl_transcript'] = true;
        }
        if (Schema::hasColumn('schools', 'show_student_photo_on_skl')) {
            $defaults['show_student_photo_on_skl'] = false;
        }

        return School::query()->create($defaults);
    }

    public function getCurrentSchoolType(): ?string
    {
        return School::query()->value('tipe_sekolah');
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, UploadedFile|null> $uploadedFiles
     */
    public function updateProfile(array $payload, array $uploadedFiles): School
    {
        $school = $this->getCurrentSchool();

        return DB::transaction(function () use ($school, $payload, $uploadedFiles): School {
            $payload['name'] = $payload['nama_sekolah'] ?? $school->name;

            $uploadMap = [
                'logo' => 'schools/branding/logo',
                'kop_surat' => 'schools/branding/letterhead',
                'ttd_kepsek' => 'schools/branding/signature',
                'stempel_sekolah' => 'schools/branding/stamp',
                'bg_countdown' => 'schools/branding/background',
            ];

            foreach ($uploadMap as $field => $directory) {
                $file = $uploadedFiles[$field] ?? null;
                if (!$file instanceof UploadedFile) {
                    continue;
                }

                $newPath = $file->store($directory, 'public');
                $this->deleteOldIfExists((string) ($school->{$field} ?? ''), $newPath);
                $payload[$field] = $newPath;
            }

            if (Schema::hasColumn('schools', 'logo_path')) {
                $payload['logo_path'] = $payload['logo'] ?? $school->logo_path;
            }
            if (Schema::hasColumn('schools', 'signature_path')) {
                $payload['signature_path'] = $payload['ttd_kepsek'] ?? $school->signature_path;
            }
            if (Schema::hasColumn('schools', 'stamp_path')) {
                $payload['stamp_path'] = $payload['stempel_sekolah'] ?? $school->stamp_path;
            }

            $allowedPayload = array_intersect_key($payload, array_flip(Schema::getColumnListing('schools')));
            $school->fill($allowedPayload);
            $school->save();

            return $school->refresh();
        });
    }

    private function deleteOldIfExists(string $oldPath, string $newPath): void
    {
        if ($oldPath === '' || $oldPath === $newPath) {
            return;
        }

        if (Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }
    }
}
