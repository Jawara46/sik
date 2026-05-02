<?php

declare(strict_types=1);

namespace App\Services;

use App\Exports\SmkRecordsTemplateExport;
use App\Exports\SmkPklTemplateExport;
use App\Exports\SmkUnitTemplateExport;
use App\Models\SmkRecord;
use App\Models\SmkRecordUnit;
use App\Models\SmkUnit;
use App\Models\Student;
use App\Models\Major;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class SmkExcelService
{
    public function __construct(private SmkCompetencyService $smkCompetencyService)
    {
    }

    public function downloadPklTemplate(int $schoolId): BinaryFileResponse
    {
        $students = Student::query()
            ->where('school_id', '=', $schoolId)
            ->with(['major:id,code', 'smkRecord'])
            ->orderBy('name')
            ->get();

        $rows = $students->map(function (Student $student) {
            return collect([
                $student->nisn,
                $student->name,
                $student->major?->code ?? '-',
                $student->smkRecord?->company_name ?? '',
                $student->smkRecord?->pkl_score ?? '',
            ]);
        });

        return Excel::download(
            new SmkPklTemplateExport($rows),
            sprintf('template-pkl-%s.xlsx', now()->format('Ymd-His'))
        );
    }

    public function importPklRecords(UploadedFile $file, int $schoolId): array
    {
        $result = [
            'success' => false,
            'processed_rows' => 0,
            'skipped_rows' => [],
            'message' => '',
        ];

        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = collect($worksheet->toArray(null, true, true, false))
                ->map(fn(array $row) => array_values($row));
                
            if ($rows->count() <= 1) {
                throw new InvalidArgumentException('File Excel kosong atau tidak memiliki data.');
            }

            $studentsByNisn = Student::query()
                ->where('school_id', '=', $schoolId)
                ->get()
                ->keyBy(fn(Student $student) => (string) $student->nisn);

            DB::transaction(function () use ($rows, $studentsByNisn, &$result) {
                // Header is at index 0, so loop from index 1.
                foreach ($rows->skip(1)->values() as $offset => $row) {
                    if (!is_array($row)) {
                        continue;
                    }
                    
                    $excelRowNumber = $offset + 2;
                    
                    $nisn = $this->toStringCell($row[0] ?? null);
                    if ($nisn === null) {
                        $result['skipped_rows'][] = "Baris {$excelRowNumber}: NISN kosong.";
                        continue;
                    }

                    $student = $studentsByNisn->get($nisn);
                    if (!$student) {
                        $result['skipped_rows'][] = "Baris {$excelRowNumber}: NISN {$nisn} tidak ditemukan di sistem.";
                        continue;
                    }

                    $companyName = $this->toStringCell($row[3] ?? null);
                    $pklScore = $this->toNullableFloat($row[4] ?? null);

                    // We only want to update PKL strictly. Using updateOrCreate to preserve UKK data.
                    SmkRecord::updateOrCreate(
                        ['student_id' => $student->id],
                        [
                            'company_name' => $companyName,
                            'pkl_score' => $pklScore,
                        ]
                    );
                    $result['processed_rows']++;
                }
            });

            $result['success'] = true;
            $processedCount = (string) $result['processed_rows'];
            $result['message'] = "Import PKL selesai. {$processedCount} data siswa berhasil diperbarui.";

            return $result;
        } catch (Throwable $e) {
            Log::error('SMK PKL Excel import failed', [
                'school_id' => $schoolId,
                'message' => $e->getMessage()
            ]);
            
            $result['message'] = 'Gagal import: ' . $e->getMessage();
            return $result;
        }
    }

    private function toStringCell(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        return trim((string) $value);
    }

    private function toNullableFloat(mixed $value): ?float
    {
        $stringVal = $this->toStringCell($value);
        if ($stringVal === null || !is_numeric(str_replace(',', '.', $stringVal))) {
            return null;
        }
        return (float) str_replace(',', '.', $stringVal);
    }

    public function downloadUnitsTemplate(int $schoolId, int $majorId): BinaryFileResponse
    {
        $major = Major::with('smkUnits')->findOrFail($majorId);
        $units = $major->smkUnits;

        $students = Student::query()
            ->where('school_id', '=', $schoolId)
            ->where('major_id', '=', $majorId)
            ->with(['smkRecord.units' => function ($query) {
                // Eager load only for units we care about
            }])
            ->orderBy('name')
            ->get();

        $headers = ['NISN', 'Nama Siswa'];
        foreach ($units as $unit) {
            $headers[] = $unit->kode_unit . "\n" . $unit->judul_unit;
        }

        $rows = $students->map(function (Student $student) use ($units) {
            $row = [
                (string) $student->nisn,
                $student->name,
            ];

            $unitScores = [];
            if ($student->smkRecord) {
                foreach ($student->smkRecord->units as $recordUnit) {
                    $unitScores[$recordUnit->smk_unit_id] = $recordUnit->score;
                }
            }

            foreach ($units as $unit) {
                $row[] = $unitScores[$unit->id] ?? '';
            }

            return $row;
        });

        return Excel::download(
            new SmkUnitTemplateExport($rows, $headers),
            sprintf('template-unit-ukk-%s-%s.xlsx', Str::slug($major->code), now()->format('Ymd-His'))
        );
    }

    public function importUnits(UploadedFile $file, int $schoolId): array
    {
        $result = [
            'success' => false,
            'processed_rows' => 0,
            'skipped_rows' => [],
            'message' => '',
        ];

        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = collect($worksheet->toArray(null, true, true, false))
                ->map(fn(array $row) => array_values($row));
                
            if ($rows->count() <= 1) {
                throw new InvalidArgumentException('File Excel kosong atau tidak memiliki data.');
            }

            $headerRow = $rows->first();
            if (!is_array($headerRow) || count($headerRow) <= 2) {
                throw new InvalidArgumentException('Format header tidak valid atau unit kompetensi tidak ditemukan.');
            }

            $unitMapIndex = [];
            for ($i = 2; $i < count($headerRow); $i++) {
                $rawHeader = $this->toStringCell($headerRow[$i] ?? null);
                if (!$rawHeader) continue;

                $kodeUnit = trim(explode("\n", $rawHeader)[0]);
                $smkUnit = SmkUnit::where('kode_unit', '=', $kodeUnit)->first();
                if ($smkUnit) {
                    $unitMapIndex[$i] = $smkUnit->id;
                }
            }

            if (empty($unitMapIndex)) {
                throw new InvalidArgumentException('Header Kolom Unit Kompetensi (Kode Unit) tidak dikenali oleh sistem DB.');
            }

            $studentsByNisn = Student::query()
                ->where('school_id', '=', $schoolId)
                ->with('smkRecord')
                ->get()
                ->keyBy(fn(Student $student) => (string) $student->nisn);

            DB::transaction(function () use ($rows, $studentsByNisn, $unitMapIndex, &$result) {
                foreach ($rows->skip(1)->values() as $offset => $row) {
                    if (!is_array($row)) continue;
                    
                    $excelRowNumber = $offset + 2;
                    $nisn = $this->toStringCell($row[0] ?? null);
                    
                    if ($nisn === null) {
                        $result['skipped_rows'][] = "Baris {$excelRowNumber}: NISN kosong.";
                        continue;
                    }

                    $student = $studentsByNisn->get($nisn);
                    if (!$student) {
                        $result['skipped_rows'][] = "Baris {$excelRowNumber}: NISN {$nisn} tidak ditemukan.";
                        continue;
                    }

                    $record = $student->smkRecord;
                    if (!$record) {
                        $record = SmkRecord::create(['student_id' => $student->id]);
                    }

                    $hasUpdates = false;
                    foreach ($unitMapIndex as $colIndex => $unitId) {
                        // User might leave it empty, or fill it with numbers.
                        $scoreRaw = $this->toStringCell($row[$colIndex] ?? null);
                        
                        // We only overwrite if they explicitly provided a value.
                        if ($scoreRaw !== null && $scoreRaw !== '') {
                            $floatScore = $this->toNullableFloat($scoreRaw);
                            SmkRecordUnit::updateOrCreate(
                                [
                                    'smk_record_id' => $record->id,
                                    'smk_unit_id' => $unitId,
                                ],
                                [
                                    'score' => $floatScore,
                                ]
                            );
                            $hasUpdates = true;
                        }
                    }

                    if ($hasUpdates) {
                        $result['processed_rows']++;
                    }
                }
            });

            $result['success'] = true;
            $processedCount = (string) $result['processed_rows'];
            $result['message'] = "Import Nilai Unit selesai. {$processedCount} baris siswa berhasil diperbarui.";

            return $result;
        } catch (Throwable $e) {
            Log::error('SMK Units Excel import failed', [
                'school_id' => $schoolId,
                'message' => $e->getMessage()
            ]);
            
            $result['message'] = 'Gagal import unit: ' . $e->getMessage();
            return $result;
        }
    }
}
