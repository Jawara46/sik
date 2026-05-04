<?php

declare(strict_types=1);

namespace App\Services;

use App\Exports\GradesTemplateExport;
use App\Exports\GradesTemplateSheetExport;
use App\Imports\SimpleSheetImport;
use App\Models\Major;
use App\Models\Subject;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class SubjectManagementService
{
    /**
     * @return array<int, string>
     */
    public function categories(): array
    {
        return [
            'Umum',
            'Muatan Nasional',
            'Kewilayahan',
            'C1',
            'C2',
            'C3',
            'UKK',
            'PKL',
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function create(int $schoolId, array $payload): Subject
    {
        return DB::transaction(function () use ($schoolId, $payload): Subject {
            $subject = Subject::query()->create([
                'name' => (string) $payload['name'],
                'category' => (string) $payload['category'],
            ]);

            $subject->majors()->sync($this->resolveMajorIds($schoolId, $payload['major_ids'] ?? []));

            return $subject->refresh();
        });
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function update(int $schoolId, Subject $subject, array $payload): Subject
    {
        return DB::transaction(function () use ($schoolId, $subject, $payload): Subject {
            $subject->fill([
                'name' => (string) $payload['name'],
                'category' => (string) $payload['category'],
            ]);
            $subject->save();

            $subject->majors()->sync($this->resolveMajorIds($schoolId, $payload['major_ids'] ?? []));

            return $subject->refresh();
        });
    }

    public function delete(Subject $subject): void
    {
        if ($subject->grades()->exists()) {
            throw new InvalidArgumentException('Mapel tidak bisa dihapus karena sudah digunakan pada data nilai.');
        }

        $subject->majors()->detach();
        $subject->delete();
    }

    public function downloadTemplate(int $schoolId): BinaryFileResponse
    {
        $headings = [
            'Nama Mata Pelajaran',
            'Kategori',
            'Kode Jurusan',
        ];

        $subjects = Subject::query()
            ->with([
                'majors' => static fn ($query) => $query->where('school_id', $schoolId),
            ])
            ->orderBy('name')
            ->get(['id', 'name', 'category']);

        $rows = $subjects->map(static function (Subject $subject): array {
            $majorCodes = $subject->majors->pluck('code')->map(
                static fn ($code): string => strtoupper((string) $code)
            )->implode(',');

            return [
                $subject->name,
                $subject->category,
                $majorCodes,
            ];
        })->all();

        if ($rows === []) {
            $rows[] = ['Akuntansi Dasar', 'C1', 'AKL'];
            $rows[] = ['Pendidikan Agama', 'Umum', ''];
        }

        return Excel::download(
            new GradesTemplateExport([
                new GradesTemplateSheetExport('Mata Pelajaran', $headings, $rows, 0, false)
            ]),
            'template-mapel-' . now()->format('Ymd-His') . '.xlsx',
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
            $nameIndex = $headerMap['nama_mata_pelajaran'] ?? $headerMap['nama_mapel'] ?? null;
            $categoryIndex = $headerMap['kategori'] ?? null;
            $majorCodeIndex = $headerMap['kode_jurusan'] ?? null;

            if (!is_int($nameIndex) || !is_int($categoryIndex)) {
                throw new InvalidArgumentException('Kolom "Nama Mata Pelajaran" dan "Kategori" wajib ada.');
            }

            $allowedCategories = $this->categories();
            $majors = Major::query()
                ->where('school_id', $schoolId)
                ->get(['id', 'code'])
                ->keyBy(static fn (Major $major): string => strtoupper((string) $major->code));

            DB::transaction(function () use (
                $rows,
                $nameIndex,
                $categoryIndex,
                $majorCodeIndex,
                $allowedCategories,
                $majors,
                &$result,
            ): void {
                foreach ($rows->skip(1)->values() as $offset => $row) {
                    $rowNumber = $offset + 2;
                    $cells = $this->rowToArray($row);

                    $name = $this->toString($cells[$nameIndex] ?? null);
                    $category = $this->toString($cells[$categoryIndex] ?? null);
                    $majorCodeRaw = is_int($majorCodeIndex)
                        ? $this->toString($cells[$majorCodeIndex] ?? null)
                        : null;

                    if ($name === null || $category === null) {
                        $result['skipped_rows'][] = "Baris {$rowNumber}: nama mapel/kategori kosong.";
                        continue;
                    }

                    if (!in_array($category, $allowedCategories, true)) {
                        $result['skipped_rows'][] = "Baris {$rowNumber}: kategori {$category} tidak valid.";
                        continue;
                    }

                    $majorIds = [];
                    if ($majorCodeRaw !== null) {
                        foreach ($this->splitMajorCodes($majorCodeRaw) as $code) {
                            $major = $majors->get($code);
                            if (!$major instanceof Major) {
                                $result['skipped_rows'][] = "Baris {$rowNumber}: kode jurusan {$code} tidak ditemukan.";
                                continue 2;
                            }

                            $majorIds[] = (int) $major->id;
                        }
                    }

                    $subject = Subject::query()->updateOrCreate(
                        ['name' => $name],
                        ['category' => $category],
                    );
                    $subject->majors()->sync($majorIds);
                    $result['updated']++;
                }
            });

            $result['success'] = true;
            $result['message'] = sprintf(
                'Import mapel selesai. %d baris tersimpan, %d baris dilewati.',
                $result['updated'],
                count($result['skipped_rows']),
            );

            return $result;
        } catch (Throwable $exception) {
            Log::error('Import mata pelajaran gagal', [
                'message' => $exception->getMessage(),
            ]);

            $result['message'] = 'Import gagal: ' . $exception->getMessage();

            return $result;
        }
    }

    /**
     * @param iterable<mixed> $majorIds
     * @return array<int, int>
     */
    private function resolveMajorIds(int $schoolId, iterable $majorIds): array
    {
        $ids = collect($majorIds)
            ->map(static fn ($value): int => (int) $value)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            return [];
        }

        return Major::query()
            ->where('school_id', $schoolId)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
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

    /**
     * @return array<int, string>
     */
    private function splitMajorCodes(string $raw): array
    {
        return collect(preg_split('/[,\;\|]+/', strtoupper($raw)) ?: [])
            ->map(static fn (string $code): string => trim($code))
            ->filter(static fn (string $code): bool => $code !== '')
            ->unique()
            ->values()
            ->all();
    }
}

