<?php

declare(strict_types=1);

namespace App\Services;

use App\Exports\GradesTemplateExport;
use App\Exports\GradesTemplateSheetExport;
use App\Models\Grade;
use App\Models\Major;
use App\Models\School;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class ExcelImportService
{
    /**
     * @return array{students:int,subjects:int,can_download:bool}
     */
    public function getTemplateAvailability(int $schoolId): array
    {
        $students = Student::query()->where('school_id', $schoolId)->count();
        $subjects = Subject::query()->count();

        return [
            'students' => $students,
            'subjects' => $subjects,
            'can_download' => $students > 0 && $subjects > 0,
        ];
    }

    public function canDownloadTemplate(int $schoolId): bool
    {
        return $this->getTemplateAvailability($schoolId)['can_download'];
    }

    public function downloadTemplate(int $schoolId, string $type = 'final', ?int $majorId = null): BinaryFileResponse
    {
        if (!$this->canDownloadTemplate($schoolId)) {
            throw new InvalidArgumentException('Template hanya bisa diunduh jika data siswa dan mata pelajaran sudah tersedia.');
        }

        if (!in_array($type, ['final', 'leger'], true)) {
            throw new InvalidArgumentException('Tipe template nilai tidak dikenali.');
        }

        $schoolType = (string) (School::query()->where('id', $schoolId)->value('tipe_sekolah') ?? '');
        $isSmk = $schoolType === 'SMK';
        
        $majorCode = '';
        if ($isSmk && $majorId !== null) {
            $majorCode = Major::query()->where('id', $majorId)->value('code') ?? '';
        }

        $subjects = Subject::query()
            ->with('majors:id')
            ->orderBy('name')
            ->get(['id', 'name']);

        // Filter subjects based on Major
        if ($isSmk && $majorId !== null) {
            $subjects = $subjects->filter(function(Subject $subject) use ($majorId) {
                $majorIds = $subject->majors->pluck('id')->map(static fn (mixed $id): int => (int) $id)->all();
                return empty($majorIds) || in_array((int)$majorId, $majorIds, true);
            })->values();
        }

        $subjectIds = $subjects->pluck('id')->all();
        
        $studentsQuery = Student::query()
            ->where('school_id', $schoolId)
            ->orderBy('name')
            ->with([
                'grades' => static fn ($query) => $query
                    ->whereIn('subject_id', $subjectIds)
                    ->whereIn('semester', range(1, 6)),
                'major:id,code',
            ]);
            
        if ($isSmk && $majorId !== null) {
            $studentsQuery->where('major_id', $majorId);
        }
            
        $students = $studentsQuery->get([
                'id',
                'nisn',
                'name',
                'major_id',
            ]);

        $filenameSuffix = $majorCode !== '' ? '-' . strtolower($majorCode) : '';

        if ($type === 'leger') {
            $sheets = [];
            foreach (range(1, 6) as $semester) {
                $sheets[] = new GradesTemplateSheetExport(
                    'SMT' . $semester,
                    $this->buildBaseHeadings($subjects),
                    $this->buildSemesterRows($students, $subjects, $semester, $isSmk),
                );
            }

            return Excel::download(
                new GradesTemplateExport($sheets),
                sprintf('template-nilai-leger%s-%s.xlsx', $filenameSuffix, now()->format('Ymd-His')),
            );
        }

        return Excel::download(
            new GradesTemplateExport([
                new GradesTemplateSheetExport(
                    'Nilai Akhir',
                    $this->buildBaseHeadings($subjects),
                    $this->buildFinalRows($students, $subjects, $isSmk),
                ),
            ]),
            sprintf('template-nilai-akhir%s-%s.xlsx', $filenameSuffix, now()->format('Ymd-His')),
        );
    }

    /**
     * @return array{
     *   success:bool,
     *   template_type:string,
     *   processed_rows:int,
     *   updated_students:int,
     *   updated_grades:int,
     *   skipped_rows:array<int,string>,
     *   message:string
     * }
     */
    public function importGrades(UploadedFile $file, int $schoolId): array
    {
        $result = [
            'success' => false,
            'template_type' => 'unknown',
            'processed_rows' => 0,
            'updated_students' => 0,
            'updated_grades' => 0,
            'skipped_rows' => [],
            'message' => '',
        ];

        try {
            $workbookSheets = $this->loadWorkbookSheets($file);
            if ($workbookSheets->isEmpty()) {
                throw new InvalidArgumentException('File Excel kosong.');
            }

            $templateType = $this->detectWorkbookTemplateType($workbookSheets);
            $result['template_type'] = $templateType;

            $schoolType = (string) (School::query()->where('id', $schoolId)->value('tipe_sekolah') ?? '');
            $isSmk = $schoolType === 'SMK';
            $majorCodeMap = Major::query()
                ->where('school_id', $schoolId)
                ->get(['id', 'code'])
                ->keyBy(static fn (Major $major): string => strtoupper((string) $major->code));

            $subjects = Subject::query()
                ->with('majors:id')
                ->orderBy('name')
                ->get(['id', 'name']);
            $studentsByNisn = Student::query()
                ->where('school_id', $schoolId)
                ->get()
                ->keyBy(static fn (Student $student): string => (string) $student->nisn);

            DB::transaction(function () use (
                $workbookSheets,
                $templateType,
                $subjects,
                $studentsByNisn,
                $majorCodeMap,
                $isSmk,
                &$result,
            ): void {
                $processedStudents = [];

                if ($templateType === 'leger') {
                    if ($this->hasSemesterSheetStructure($workbookSheets)) {
                        $this->importMultiSheetLedger(
                            $workbookSheets,
                            $subjects,
                            $studentsByNisn,
                            $majorCodeMap,
                            $isSmk,
                            $processedStudents,
                            $result,
                        );
                    } else {
                        $this->importSingleSheetLedger(
                            $workbookSheets->first(),
                            $subjects,
                            $studentsByNisn,
                            $majorCodeMap,
                            $isSmk,
                            $processedStudents,
                            $result,
                        );
                    }
                } else {
                    $this->importFinalSheet(
                        $workbookSheets->first(),
                        $subjects,
                        $studentsByNisn,
                        $majorCodeMap,
                        $isSmk,
                        $processedStudents,
                        $result,
                    );
                }

                $result['updated_students'] = count($processedStudents);
            });

            $result['success'] = true;
            $result['message'] = sprintf(
                'Import %s selesai. %d siswa diproses, %d nilai diperbarui, %d baris dilewati.',
                $templateType === 'leger' ? 'leger semester' : 'nilai akhir',
                $result['updated_students'],
                $result['updated_grades'],
                count($result['skipped_rows']),
            );

            return $result;
        } catch (Throwable $exception) {
            Log::error('Excel import gagal', [
                'school_id' => $schoolId,
                'message' => $exception->getMessage(),
            ]);

            $result['message'] = 'Import gagal: ' . $exception->getMessage();

            return $result;
        }
    }

    /**
     * @param Collection<int, Subject> $subjects
     * @return array<int, string>
     */
    private function buildBaseHeadings(Collection $subjects): array
    {
        return [
            'NISN',
            'Nama Siswa',
            'Kode Jurusan',
            ...$subjects->pluck('name')->all(),
        ];
    }

    /**
     * @param Collection<int, Student> $students
     * @param Collection<int, Subject> $subjects
     * @return array<int, array<int, mixed>>
     */
    private function buildFinalRows(Collection $students, Collection $subjects, bool $isSmk): array
    {
        return $students->map(function (Student $student) use ($subjects, $isSmk): array {
            $staticColumns = $this->buildStaticStudentColumns($student);
            $dynamicColumns = $subjects->map(function (Subject $subject) use ($student, $isSmk): ?float {
                $majorIds = $subject->majors->pluck('id')->map(static fn (mixed $id): int => (int) $id)->all();
                if (!$this->isSubjectApplicableForMajor($majorIds, $student->major_id, $isSmk)) {
                    return null;
                }

                return $this->calculateFinalScore($student->grades->where('subject_id', $subject->id));
            })->all();

            return [...$staticColumns, ...$dynamicColumns];
        })->all();
    }

    /**
     * @param Collection<int, Student> $students
     * @param Collection<int, Subject> $subjects
     * @return array<int, array<int, mixed>>
     */
    private function buildSemesterRows(Collection $students, Collection $subjects, int $semester, bool $isSmk): array
    {
        return $students->map(function (Student $student) use ($subjects, $semester, $isSmk): array {
            $staticColumns = $this->buildStaticStudentColumns($student);
            $dynamicColumns = $subjects->map(function (Subject $subject) use ($student, $semester, $isSmk): ?float {
                $majorIds = $subject->majors->pluck('id')->map(static fn (mixed $id): int => (int) $id)->all();
                if (!$this->isSubjectApplicableForMajor($majorIds, $student->major_id, $isSmk)) {
                    return null;
                }

                $grade = $student->grades
                    ->where('subject_id', $subject->id)
                    ->firstWhere('semester', $semester);

                return $grade instanceof Grade ? round((float) $grade->score, 2) : null;
            })->all();

            return [...$staticColumns, ...$dynamicColumns];
        })->all();
    }

    /**
     * @return array<int, mixed>
     */
    private function buildStaticStudentColumns(Student $student): array
    {
        return [
            $student->nisn,
            $student->name,
            $student->major?->code,
        ];
    }

    /**
     * @return Collection<int, array{name:string,rows:Collection<int, array<int, mixed>>}>
     */
    private function loadWorkbookSheets(UploadedFile $file): Collection
    {
        $spreadsheet = IOFactory::load($file->getRealPath());

        return collect($spreadsheet->getWorksheetIterator())->map(
            static function (Worksheet $worksheet): array {
                $rows = collect($worksheet->toArray(null, true, true, false))
                    ->map(static fn (array $row): array => array_values($row));

                return [
                    'name' => trim((string) $worksheet->getTitle()),
                    'rows' => $rows,
                ];
            }
        )->values();
    }

    /**
     * @param Collection<int, array{name:string,rows:Collection<int, array<int, mixed>>}> $workbookSheets
     */
    private function detectWorkbookTemplateType(Collection $workbookSheets): string
    {
        if ($this->hasSemesterSheetStructure($workbookSheets)) {
            return 'leger';
        }

        $firstSheet = $workbookSheets->first();
        if (!is_array($firstSheet)) {
            throw new InvalidArgumentException('File Excel kosong.');
        }

        $headerRow = $this->rowToArray($firstSheet['rows']->first());
        foreach ($headerRow as $heading) {
            $normalized = $this->normalizeHeading(trim((string) $heading));
            if (preg_match('/_smt_[1-6]$/', $normalized) === 1) {
                return 'leger';
            }
        }

        return 'final';
    }

    /**
     * @param Collection<int, array{name:string,rows:Collection<int, array<int, mixed>>}> $workbookSheets
     */
    private function hasSemesterSheetStructure(Collection $workbookSheets): bool
    {
        $sheetNames = $workbookSheets
            ->pluck('name')
            ->filter(static fn (mixed $name): bool => is_string($name) && preg_match('/^smt[1-6]$/i', $name) === 1)
            ->map(static fn (string $name): string => strtoupper($name))
            ->unique()
            ->values()
            ->all();

        return $sheetNames !== [];
    }

    /**
     * @param array<string, Student> $processedStudents
     * @param array{success:bool,template_type:string,processed_rows:int,updated_students:int,updated_grades:int,skipped_rows:array<int,string>,message:string} $result
     * @param Collection<int, array{name:string,rows:Collection<int, array<int, mixed>>}> $workbookSheets
     * @param Collection<int, Subject> $subjects
     * @param Collection<string, Student> $studentsByNisn
     * @param Collection<string, Major> $majorCodeMap
     */
    private function importMultiSheetLedger(
        Collection $workbookSheets,
        Collection $subjects,
        Collection $studentsByNisn,
        Collection $majorCodeMap,
        bool $isSmk,
        array &$processedStudents,
        array &$result,
    ): void {
        foreach ($workbookSheets as $sheet) {
            $sheetName = strtoupper((string) ($sheet['name'] ?? ''));
            if (preg_match('/^SMT([1-6])$/', $sheetName, $matches) !== 1) {
                continue;
            }

            $semester = (int) $matches[1];
            $rows = $sheet['rows'] ?? collect();
            if (!$rows instanceof Collection || $rows->isEmpty()) {
                continue;
            }

            $headerRow = $this->rowToArray($rows->first());
            $columnIndex = $this->mapColumnIndex($headerRow);
            if (!isset($columnIndex['nisn'])) {
                throw new InvalidArgumentException("Kolom NISN tidak ditemukan pada sheet {$sheetName}.");
            }

            $subjectColumnMap = $this->resolveFinalSubjectColumns($columnIndex, $subjects);
            if ($subjectColumnMap === []) {
                throw new InvalidArgumentException("Tidak ada kolom mata pelajaran yang dikenali pada sheet {$sheetName}.");
            }

            $this->processRowsForSemester(
                $rows,
                $semester,
                $subjectColumnMap,
                $studentsByNisn,
                $majorCodeMap,
                $isSmk,
                $processedStudents,
                $result,
                $sheetName,
            );
        }
    }

    /**
     * @param array<string, Student> $processedStudents
     * @param array{success:bool,template_type:string,processed_rows:int,updated_students:int,updated_grades:int,skipped_rows:array<int,string>,message:string} $result
     * @param array{name:string,rows:Collection<int, array<int, mixed>>} $sheet
     * @param Collection<int, Subject> $subjects
     * @param Collection<string, Student> $studentsByNisn
     * @param Collection<string, Major> $majorCodeMap
     */
    private function importSingleSheetLedger(
        array $sheet,
        Collection $subjects,
        Collection $studentsByNisn,
        Collection $majorCodeMap,
        bool $isSmk,
        array &$processedStudents,
        array &$result,
    ): void {
        $rows = $sheet['rows'];
        $headerRow = $this->rowToArray($rows->first());
        $columnIndex = $this->mapColumnIndex($headerRow);
        if (!isset($columnIndex['nisn'])) {
            throw new InvalidArgumentException('Kolom NISN tidak ditemukan pada template.');
        }

        $subjectColumnMap = $this->resolveLedgerSubjectColumns($columnIndex, $subjects);
        foreach ($rows->skip(1)->values() as $offset => $row) {
            $excelRowNumber = $offset + 2;
            $cells = $this->rowToArray($row);
            [$student, $majorId] = $this->resolveStudentForImport(
                $cells,
                $columnIndex,
                $studentsByNisn,
                $majorCodeMap,
                $processedStudents,
                $result,
                'SHEET-LEGER',
                $excelRowNumber,
            );

            if (!$student instanceof Student) {
                continue;
            }

            foreach ($subjectColumnMap as $subjectId => $semesterMap) {
                foreach ($semesterMap as $semester => $subjectConfig) {
                    if ($isSmk && !$this->isSubjectApplicableForMajor($subjectConfig['major_ids'], $majorId, $isSmk)) {
                        continue;
                    }

                    $score = $this->toNullableFloat($cells[$subjectConfig['column_index']] ?? null);
                    if ($score === null) {
                        continue;
                    }

                    $this->persistGradeValue($student->id, $subjectId, (int) $semester, $score, $excelRowNumber);
                    $result['updated_grades']++;
                }
            }
        }
    }

    /**
     * @param array<string, Student> $processedStudents
     * @param array{success:bool,template_type:string,processed_rows:int,updated_students:int,updated_grades:int,skipped_rows:array<int,string>,message:string} $result
     * @param array{name:string,rows:Collection<int, array<int, mixed>>} $sheet
     * @param Collection<int, Subject> $subjects
     * @param Collection<string, Student> $studentsByNisn
     * @param Collection<string, Major> $majorCodeMap
     */
    private function importFinalSheet(
        array $sheet,
        Collection $subjects,
        Collection $studentsByNisn,
        Collection $majorCodeMap,
        bool $isSmk,
        array &$processedStudents,
        array &$result,
    ): void {
        $rows = $sheet['rows'];
        $headerRow = $this->rowToArray($rows->first());
        $columnIndex = $this->mapColumnIndex($headerRow);
        if (!isset($columnIndex['nisn'])) {
            throw new InvalidArgumentException('Kolom NISN tidak ditemukan pada template.');
        }

        $subjectColumnMap = $this->resolveFinalSubjectColumns($columnIndex, $subjects);
        foreach ($rows->skip(1)->values() as $offset => $row) {
            $excelRowNumber = $offset + 2;
            $cells = $this->rowToArray($row);
            [$student, $majorId] = $this->resolveStudentForImport(
                $cells,
                $columnIndex,
                $studentsByNisn,
                $majorCodeMap,
                $processedStudents,
                $result,
                'NILAI-AKHIR',
                $excelRowNumber,
            );

            if (!$student instanceof Student) {
                continue;
            }

            foreach ($subjectColumnMap as $subjectId => $subjectConfig) {
                if ($isSmk && !$this->isSubjectApplicableForMajor($subjectConfig['major_ids'], $majorId, $isSmk)) {
                    continue;
                }

                $score = $this->toNullableFloat($cells[$subjectConfig['column_index']] ?? null);
                if ($score === null) {
                    continue;
                }

                $this->persistGradeValue($student->id, $subjectId, 6, $score, $excelRowNumber);
                $result['updated_grades']++;
            }
        }
    }

    /**
     * @param Collection<int, array<int, mixed>> $rows
     * @param array<int, array{column_index:int, major_ids:array<int,int>}> $subjectColumnMap
     * @param Collection<string, Student> $studentsByNisn
     * @param Collection<string, Major> $majorCodeMap
     * @param array<string, Student> $processedStudents
     * @param array{success:bool,template_type:string,processed_rows:int,updated_students:int,updated_grades:int,skipped_rows:array<int,string>,message:string} $result
     */
    private function processRowsForSemester(
        Collection $rows,
        int $semester,
        array $subjectColumnMap,
        Collection $studentsByNisn,
        Collection $majorCodeMap,
        bool $isSmk,
        array &$processedStudents,
        array &$result,
        string $sheetName,
    ): void {
        $headerRow = $this->rowToArray($rows->first());
        $columnIndex = $this->mapColumnIndex($headerRow);

        foreach ($rows->skip(1)->values() as $offset => $row) {
            $excelRowNumber = $offset + 2;
            $cells = $this->rowToArray($row);
            [$student, $majorId] = $this->resolveStudentForImport(
                $cells,
                $columnIndex,
                $studentsByNisn,
                $majorCodeMap,
                $processedStudents,
                $result,
                $sheetName,
                $excelRowNumber,
            );

            if (!$student instanceof Student) {
                continue;
            }

            foreach ($subjectColumnMap as $subjectId => $subjectConfig) {
                if ($isSmk && !$this->isSubjectApplicableForMajor($subjectConfig['major_ids'], $majorId, $isSmk)) {
                    continue;
                }

                $score = $this->toNullableFloat($cells[$subjectConfig['column_index']] ?? null);
                if ($score === null) {
                    continue;
                }

                $this->persistGradeValue($student->id, $subjectId, $semester, $score, $excelRowNumber);
                $result['updated_grades']++;
            }
        }
    }

    /**
     * @param array<int, mixed> $cells
     * @param array<string, int> $columnIndex
     * @param Collection<string, Student> $studentsByNisn
     * @param Collection<string, Major> $majorCodeMap
     * @param array<string, Student> $processedStudents
     * @param array{success:bool,template_type:string,processed_rows:int,updated_students:int,updated_grades:int,skipped_rows:array<int,string>,message:string} $result
     * @return array{0:Student|null,1:int|null}
     */
    private function resolveStudentForImport(
        array $cells,
        array $columnIndex,
        Collection $studentsByNisn,
        Collection $majorCodeMap,
        array &$processedStudents,
        array &$result,
        string $sheetName,
        int $excelRowNumber,
    ): array {
        $nisn = $this->toStringCell($cells[$columnIndex['nisn'] ?? -1] ?? null);
        if ($nisn === null) {
            $result['skipped_rows'][] = "{$sheetName} baris {$excelRowNumber}: NISN kosong.";

            return [null, null];
        }

        $student = $studentsByNisn->get($nisn);
        if (!$student instanceof Student) {
            $result['skipped_rows'][] = "{$sheetName} baris {$excelRowNumber}: NISN {$nisn} tidak ditemukan.";

            return [null, null];
        }

        $majorCode = $this->toStringCell($cells[$columnIndex['kode_jurusan'] ?? -1] ?? null);
        $majorId = $student->major_id;
        if ($majorCode !== null) {
            $major = $majorCodeMap->get(strtoupper($majorCode));
            if (!$major instanceof Major) {
                $result['skipped_rows'][] = "{$sheetName} baris {$excelRowNumber}: kode jurusan {$majorCode} tidak ditemukan.";

                return [null, null];
            }
            $majorId = (int) $major->id;
        }

        $studentPayload = $this->extractStudentPayload($cells, $columnIndex, $student->name, $majorId);
        $student->fill($studentPayload);
        $student->save();

        $processedStudents[$nisn] = $student;
        $result['processed_rows']++;

        return [$student, $majorId];
    }

    /**
     * @param array<string, int> $columnIndex
     * @param Collection<int, Subject> $subjects
     * @return array<int, array{column_index:int, major_ids:array<int,int>}>
     */
    private function resolveFinalSubjectColumns(array $columnIndex, Collection $subjects): array
    {
        $subjectColumns = [];

        foreach ($subjects as $subject) {
            $subjectHeading = $this->normalizeHeading($subject->name);
            if (!isset($columnIndex[$subjectHeading])) {
                continue;
            }

            $subjectColumns[(int) $subject->id] = [
                'column_index' => $columnIndex[$subjectHeading],
                'major_ids' => $subject->majors
                    ->pluck('id')
                    ->map(static fn (mixed $id): int => (int) $id)
                    ->all(),
            ];
        }

        return $subjectColumns;
    }

    /**
     * @param array<string, int> $columnIndex
     * @param Collection<int, Subject> $subjects
     * @return array<int, array<int, array{column_index:int, major_ids:array<int,int>}>>
     */
    private function resolveLedgerSubjectColumns(array $columnIndex, Collection $subjects): array
    {
        $subjectColumns = [];

        foreach ($subjects as $subject) {
            $majorIds = $subject->majors
                ->pluck('id')
                ->map(static fn (mixed $id): int => (int) $id)
                ->all();

            foreach (range(1, 6) as $semester) {
                $subjectHeading = $this->normalizeHeading(sprintf('%s Smt %d', $subject->name, $semester));
                if (!isset($columnIndex[$subjectHeading])) {
                    continue;
                }

                $subjectColumns[(int) $subject->id][$semester] = [
                    'column_index' => $columnIndex[$subjectHeading],
                    'major_ids' => $majorIds,
                ];
            }
        }

        return $subjectColumns;
    }

    /**
     * @param array<int, mixed> $cells
     * @param array<string, int> $columnIndex
     * @return array<string, mixed>
     */
    private function extractStudentPayload(array $cells, array $columnIndex, string $fallbackName, ?int $majorId): array
    {
        $name = $this->toStringCell($cells[$columnIndex['nama_siswa'] ?? -1] ?? null) ?? $fallbackName;

        return [
            'name' => $name,
            'major_id' => $majorId,
        ];
    }

    /**
     * @param array<int, int> $subjectMajorIds
     */
    private function isSubjectApplicableForMajor(array $subjectMajorIds, ?int $majorId, bool $isSmk): bool
    {
        if (!$isSmk || $subjectMajorIds === []) {
            return true;
        }

        if ($majorId === null) {
            return false;
        }

        return in_array((int) $majorId, $subjectMajorIds, true);
    }

    /**
     * @param Collection<int, Grade> $grades
     */
    private function calculateFinalScore(Collection $grades): ?float
    {
        if ($grades->isEmpty()) {
            return null;
        }

        return round((float) $grades->avg('score'), 2);
    }

    private function persistGradeValue(int $studentId, int $subjectId, int $semester, float $score, int $excelRowNumber): void
    {
        if ($score < 0 || $score > 100) {
            throw new InvalidArgumentException(
                "Baris {$excelRowNumber}: nilai harus berada pada rentang 0-100."
            );
        }

        Grade::query()->updateOrCreate(
            [
                'student_id' => $studentId,
                'subject_id' => $subjectId,
                'semester' => $semester,
            ],
            [
                'score' => round($score, 2),
            ],
        );
    }

    /**
     * @param array<int, mixed> $headerRow
     * @return array<string, int>
     */
    private function mapColumnIndex(array $headerRow): array
    {
        $columns = [];

        foreach ($headerRow as $index => $heading) {
            $headingString = is_string($heading) ? trim($heading) : trim((string) $heading);
            if ($headingString === '') {
                continue;
            }

            $columns[$this->normalizeHeading($headingString)] = $index;
        }

        return $columns;
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
    private function toStringCell(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $stringValue = trim((string) $value);
        if ($stringValue === '') {
            return null;
        }

        return $stringValue;
    }

    private function toNullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            $value = str_replace(',', '.', trim($value));
        }

        if (!is_numeric($value)) {
            throw new InvalidArgumentException('Ditemukan format nilai non-numerik pada file Excel.');
        }

        return (float) $value;
    }
}
