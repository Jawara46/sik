<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Subject;
use App\Services\ExcelImportService;
use App\Services\GradeManagementService;
use App\Services\SchoolProfileService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GradeController extends Controller
{
    public function __construct(
        private readonly ExcelImportService $excelImportService,
        private readonly SchoolProfileService $schoolProfileService,
        private readonly GradeManagementService $gradeManagementService,
    ) {
    }

    public function index(): View
    {
        $school = $this->schoolProfileService->getCurrentSchool();
        $availability = $this->excelImportService->getTemplateAvailability($school->id);
        $subjects = $this->gradeManagementService->getSubjects();

        $students = Student::query()
            ->where('school_id', $school->id)
            ->with([
                'major:id,name,code',
                'school:id,tipe_sekolah',
                'grades' => static fn ($query) => $query->whereIn('semester', range(1, 6)),
            ])
            ->orderBy('name')
            ->get();

        $studentStatuses = $students->map(
            fn (Student $student): array => $this->gradeManagementService->buildStudentStatus($student, $subjects)
        );

        $majors = [];
        if ($school->tipe_sekolah === 'SMK') {
            $majors = \App\Models\Major::query()->where('school_id', $school->id)->orderBy('name')->get();
        }

        return view('admin.grades.index', [
            'availability' => $availability,
            'studentStatuses' => $studentStatuses,
            'majors' => $majors,
            'school' => $school,
        ]);
    }

    public function edit(Student $student): View
    {
        $this->assertSameSchool($student);
        $ledger = $this->gradeManagementService->getStudentLedger(
            $student->load(['school:id,tipe_sekolah', 'major:id,name,code', 'grades'])
        );

        return view('admin.grades.edit', [
            'ledger' => $ledger,
        ]);
    }

    public function downloadTemplate(Request $request): BinaryFileResponse|RedirectResponse
    {
        $school = $this->schoolProfileService->getCurrentSchool();
        $type = in_array((string) $request->query('type', 'final'), ['final', 'leger'], true)
            ? (string) $request->query('type', 'final')
            : 'final';

        $majorId = $request->query('major_id') ? (int) $request->query('major_id') : null;

        if (!$this->excelImportService->canDownloadTemplate($school->id)) {
            return back()->withErrors([
                'grade_file' => 'Template belum bisa diunduh. Pastikan data siswa dan mata pelajaran sudah tersedia.',
            ]);
        }

        return $this->excelImportService->downloadTemplate($school->id, $type, $majorId);
    }

    public function import(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'grade_file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $school = $this->schoolProfileService->getCurrentSchool();
        $result = $this->excelImportService->importGrades($validated['grade_file'], $school->id);

        if ($result['success'] !== true) {
            return back()->withErrors([
                'grade_file' => $result['message'],
            ])->withInput();
        }

        return back()
            ->with('status', $result['message'])
            ->with('import_log', $result['skipped_rows'])
            ->with('import_result', $result);
    }

    public function updateSemester(Request $request, Student $student, Subject $subject, int $semester): JsonResponse
    {
        $this->assertSameSchool($student);

        $validated = $request->validate([
            'score' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $payload = $this->gradeManagementService->saveSemesterScore(
            $student->load(['school:id,tipe_sekolah', 'major:id,name,code']),
            $subject,
            $semester,
            isset($validated['score']) ? (float) $validated['score'] : null,
        );

        return response()->json($payload);
    }

    private function assertSameSchool(Student $student): void
    {
        $schoolId = $this->schoolProfileService->getCurrentSchool()->id;
        if ((int) $student->school_id !== (int) $schoolId) {
            throw new ModelNotFoundException();
        }
    }
}
