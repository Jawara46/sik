<?php

declare(strict_types=1);

namespace App\Services;

use App\Exports\GradesTemplateExport;
use App\Exports\GradesTemplateSheetExport;
use App\Imports\SimpleSheetImport;
use App\Models\Major;
use App\Models\Student;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class StudentManagementService
{
    /**
     * @param array<string, mixed> $payload
     * @param array<string, UploadedFile|null> $uploadedFiles
     */
    public function create(int $schoolId, array $payload, array $uploadedFiles = []): Student
    {
        return DB::transaction(function () use ($schoolId, $payload, $uploadedFiles): Student {
            $payload['school_id'] = $schoolId;
            $payload['major_id'] = $this->resolveMajorId($schoolId, $payload['major_id'] ?? null);
            $payload['phone_number'] = $payload['nomor_wa'] ?? null;
            $payload['status'] = (string) ($payload['status'] ?? 'Pending');
            $payload['password'] = (string) ($payload['password'] ?? $payload['nisn']);
            $payload['status_administrasi'] = (bool) ($payload['status_administrasi'] ?? true);
            $payload['photo'] = $this->storePhoto($uploadedFiles['photo'] ?? null);

            return Student::query()->create($payload);
        });
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, UploadedFile|null> $uploadedFiles
     */
    public function update(Student $student, array $payload, array $uploadedFiles = []): Student
    {
        return DB::transaction(function () use ($student, $payload, $uploadedFiles): Student {
            $payload['major_id'] = $this->resolveMajorId((int) $student->school_id, $payload['major_id'] ?? null);
            $payload['phone_number'] = $payload['nomor_wa'] ?? null;
            $payload['status_administrasi'] = (bool) ($payload['status_administrasi'] ?? true);
            $payload['status'] = (string) ($payload['status'] ?? $student->status);

            $newPhoto = $this->storePhoto($uploadedFiles['photo'] ?? null);
            if ($newPhoto !== null) {
                $this->deletePhotoIfExists((string) $student->photo, $newPhoto);
                $payload['photo'] = $newPhoto;
            }

            $student->fill($payload);
            $student->save();

            return $student->refresh();
        });
    }

    public function delete(Student $student): void
    {
        $this->deletePhotoIfExists((string) $student->photo, null);
        $student->delete();
    }

    public function downloadTemplate(int $schoolId): BinaryFileResponse
    {
        $headings = [
            'NISN',
            'NIS',
            'Nama Siswa',
            'Kode Jurusan',
            'Tempat Lahir',
            'Tanggal Lahir',
            'Nama Orang Tua',
            'Nomor WA',
            'Status Administrasi',
        ];

        $rows = Student::query()
            ->where('school_id', $schoolId)
            ->orderBy('name')
            ->with('major:id,code')
            ->get([
                'nisn',
                'nis',
                'name',
                'major_id',
                'tempat_lahir',
                'tanggal_lahir',
                'nama_orang_tua',
                'nomor_wa',
                'status_administrasi',
            ])
            ->map(static function (Student $student): array {
                return [
                    $student->nisn,
                    $student->nis,
                    $student->name,
                    $student->major?->code,
                    $student->tempat_lahir,
                    optional($student->tanggal_lahir)->format('Y-m-d'),
                    $student->nama_orang_tua,
                    $student->nomor_wa,
                    (bool) $student->status_administrasi ? '1' : '0',
                ];
            })
            ->all();

        if ($rows === []) {
            $rows[] = ['0012345678', '', 'Nama Siswa', 'AKL', 'Bandung', '2008-01-01', 'Nama Wali', '081234567890', '1'];
        }

        return Excel::download(
            new GradesTemplateExport([
                new GradesTemplateSheetExport('Siswa', $headings, $rows, 0)
            ]),
            'template-siswa-' . now()->format('Ymd-His') . '.xlsx',
        );
    }

    /**
     * @return array{
     *   success:bool,
     *   updated:int,
     *   skipped_rows:array<int,string>,
     *   message:string
     * }
     */
    public function import(UploadedFile $file, int $schoolId): array
    {
        $import = new SimpleSheetImport();
        $result = [
            'success' => false,
            'updated' => 0,
            'skipped_rows' => [],
            'message' => '',
        ];

        try {
            Excel::import($import, $file);
            $rows = $import->rows();

            if ($rows->isEmpty()) {
                throw new InvalidArgumentException('File Excel kosong.');
            }

            $headerMap = $this->mapHeader($this->rowToArray($rows->first()));
            $requiredNisn = $headerMap['nisn'] ?? null;
            if (!is_int($requiredNisn)) {
                throw new InvalidArgumentException('Kolom NISN wajib ada pada template siswa.');
            }

            DB::transaction(function () use ($rows, $headerMap, $schoolId, &$result): void {
                $majors = Major::query()
                    ->where('school_id', $schoolId)
                    ->get(['id', 'code'])
                    ->keyBy(static fn (Major $major): string => strtoupper((string) $major->code));
                $majorCodeIndex = $headerMap['kode_jurusan'] ?? null;

                foreach ($rows->skip(1)->values() as $offset => $row) {
                    $rowNumber = $offset + 2;
                    $cells = $this->rowToArray($row);

                    $nisn = $this->toString($cells[$headerMap['nisn']] ?? null);
                    if ($nisn === null) {
                        $result['skipped_rows'][] = "Baris {$rowNumber}: NISN kosong.";
                        continue;
                    }

                    $student = Student::query()->where('nisn', $nisn)->first();
                    if ($student instanceof Student && (int) $student->school_id !== (int) $schoolId) {
                        $result['skipped_rows'][] = "Baris {$rowNumber}: NISN {$nisn} sudah terdaftar di sekolah lain.";
                        continue;
                    }

                    if (!$student instanceof Student) {
                        $student = new Student();
                        $student->school_id = $schoolId;
                        $student->nisn = $nisn;
                    }

                    if (!$student->exists) {
                        $student->password = $nisn;
                        $student->status = 'Pending';
                    }

                    if (is_int($majorCodeIndex)) {
                        $majorCode = $this->toString($cells[$majorCodeIndex] ?? null);
                        if ($majorCode !== null) {
                            $major = $majors->get(strtoupper($majorCode));
                            if (!$major instanceof Major) {
                                $result['skipped_rows'][] = "Baris {$rowNumber}: kode jurusan {$majorCode} tidak ditemukan.";
                                continue;
                            }
                            $student->major_id = $major->id;
                        } else {
                            $student->major_id = null;
                        }
                    }

                    $student->nis = $this->toString($cells[$headerMap['nis'] ?? -1] ?? null);
                    $student->name = $this->toString($cells[$headerMap['nama_siswa'] ?? $headerMap['nama'] ?? -1] ?? null) ?? $student->name ?? 'Tanpa Nama';
                    $student->tempat_lahir = $this->toString($cells[$headerMap['tempat_lahir'] ?? -1] ?? null);
                    $student->tanggal_lahir = $this->parseDate($cells[$headerMap['tanggal_lahir'] ?? -1] ?? null);
                    $student->nama_orang_tua = $this->toString($cells[$headerMap['nama_orang_tua'] ?? -1] ?? null);

                    $nomorWa = $this->toString($cells[$headerMap['nomor_wa'] ?? -1] ?? null);
                    $student->nomor_wa = $nomorWa;
                    $student->phone_number = $nomorWa;

                    $statusAdministrasiValue = $cells[$headerMap['status_administrasi'] ?? -1] ?? null;
                    $student->status_administrasi = $this->toBool($statusAdministrasiValue, true);

                    $student->save();
                    $result['updated']++;
                }
            });

            $result['success'] = true;
            $result['message'] = sprintf(
                'Import siswa selesai. %d baris tersimpan, %d baris dilewati.',
                $result['updated'],
                count($result['skipped_rows']),
            );

            return $result;
        } catch (Throwable $exception) {
            Log::error('Import siswa gagal', [
                'school_id' => $schoolId,
                'message' => $exception->getMessage(),
            ]);

            $result['message'] = 'Import gagal: ' . $exception->getMessage();

            return $result;
        }
    }

    /**
     * @param array<int, mixed> $header
     * @return array<string, int>
     */
    private function mapHeader(array $header): array
    {
        $mapped = [];
        foreach ($header as $index => $value) {
            $heading = $this->normalizeHeading((string) $value);
            if ($heading === '') {
                continue;
            }
            $mapped[$heading] = $index;
        }

        return $mapped;
    }

    private function normalizeHeading(string $heading): string
    {
        return (string) Str::of($heading)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_');
    }

    /**
     * @param mixed $row
     * @return array<int, mixed>
     */
    private function rowToArray(mixed $row): array
    {
        if ($row instanceof Collection) {
            return $row->values()->all();
        }

        if (is_array($row)) {
            return array_values($row);
        }

        return [];
    }

    private function toString(mixed $value): ?string
    {
        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        return $text;
    }

    private function parseDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return CarbonImmutable::instance(ExcelDate::excelToDateTimeObject((float) $value))->toDateString();
        }

        try {
            return CarbonImmutable::parse((string) $value)->toDateString();
        } catch (Throwable) {
            throw new InvalidArgumentException('Format tanggal lahir pada file siswa tidak valid.');
        }
    }

    private function toBool(mixed $value, bool $default): bool
    {
        if ($value === null || $value === '') {
            return $default;
        }

        $text = strtolower(trim((string) $value));
        if (in_array($text, ['1', 'true', 'ya', 'yes', 'open', 'buka'], true)) {
            return true;
        }

        if (in_array($text, ['0', 'false', 'tidak', 'no', 'lock', 'kunci'], true)) {
            return false;
        }

        return $default;
    }

    private function resolveMajorId(int $schoolId, mixed $majorId): ?int
    {
        $id = (int) $majorId;
        if ($id <= 0) {
            return null;
        }

        $exists = Major::query()
            ->where('school_id', $schoolId)
            ->where('id', $id)
            ->exists();

        return $exists ? $id : null;
    }

    private function storePhoto(?UploadedFile $file): ?string
    {
        if (!$file instanceof UploadedFile) {
            return null;
        }

        return $file->store('students/photos', 'public');
    }

    private function deletePhotoIfExists(string $oldPath, ?string $newPath): void
    {
        if ($oldPath === '' || $oldPath === $newPath) {
            return;
        }

        if (Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }
    }
}
