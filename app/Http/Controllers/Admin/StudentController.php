<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\SchoolProfileService;
use App\Services\StudentManagementService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StudentController extends Controller
{
    public function __construct(
        private readonly SchoolProfileService $schoolProfileService,
        private readonly StudentManagementService $studentManagementService,
    ) {
    }

    public function index(Request $request): View
    {
        return $this->renderIndex($request, (string) $request->query('open', ''));
    }

    public function importIndex(Request $request): View
    {
        return $this->renderIndex($request, 'import');
    }

    public function store(Request $request): RedirectResponse
    {
        $school = $this->schoolProfileService->getCurrentSchool();
        $validated = $request->validate([
            'nisn' => ['required', 'string', 'max:20', Rule::unique('students', 'nisn')],
            'nis' => ['nullable', 'string', 'max:20'],
            'name' => ['required', 'string', 'max:150'],
            'major_id' => [
                'nullable',
                Rule::exists('majors', 'id')->where(fn ($query) => $query->where('school_id', $school->id)),
            ],
            'tempat_lahir' => ['nullable', 'string', 'max:120'],
            'tanggal_lahir' => ['nullable', 'date'],
            'nama_orang_tua' => ['nullable', 'string', 'max:150'],
            'nomor_wa' => ['nullable', 'string', 'max:20'],
            'status_administrasi' => ['nullable', 'boolean'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        $validated['status_administrasi'] = $request->boolean('status_administrasi', true);
        $this->studentManagementService->create($school->id, $validated, [
            'photo' => $request->file('photo'),
        ]);

        return back()->with('status', 'Data siswa berhasil ditambahkan.');
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $this->assertSameSchool($student);

        $validated = $request->validate([
            'nisn' => ['required', 'string', 'max:20', Rule::unique('students', 'nisn')->ignore($student->id)],
            'nis' => ['nullable', 'string', 'max:20'],
            'name' => ['required', 'string', 'max:150'],
            'major_id' => [
                'nullable',
                Rule::exists('majors', 'id')->where(fn ($query) => $query->where('school_id', $student->school_id)),
            ],
            'tempat_lahir' => ['nullable', 'string', 'max:120'],
            'tanggal_lahir' => ['nullable', 'date'],
            'nama_orang_tua' => ['nullable', 'string', 'max:150'],
            'nomor_wa' => ['nullable', 'string', 'max:20'],
            'status' => ['required', 'in:Lulus,Tidak Lulus,Pending'],
            'status_administrasi' => ['nullable', 'boolean'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        $validated['status_administrasi'] = $request->boolean('status_administrasi', true);
        $this->studentManagementService->update($student, $validated, [
            'photo' => $request->file('photo'),
        ]);

        return back()->with('status', 'Data siswa berhasil diperbarui.');
    }

    public function destroy(Student $student): RedirectResponse
    {
        $this->assertSameSchool($student);
        $this->studentManagementService->delete($student);

        return back()->with('status', 'Data siswa berhasil dihapus.');
    }

    public function downloadTemplate(): BinaryFileResponse
    {
        $school = $this->schoolProfileService->getCurrentSchool();

        return $this->studentManagementService->downloadTemplate($school->id);
    }

    public function import(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $school = $this->schoolProfileService->getCurrentSchool();
        $result = $this->studentManagementService->import($validated['student_file'], $school->id);

        if ($result['success'] !== true) {
            return back()->withErrors([
                'student_file' => $result['message'],
            ]);
        }

        return back()
            ->with('status', $result['message'])
            ->with('student_import_log', $result['skipped_rows']);
    }

    private function assertSameSchool(Student $student): void
    {
        $schoolId = $this->schoolProfileService->getCurrentSchool()->id;
        if ((int) $student->school_id !== (int) $schoolId) {
            throw new ModelNotFoundException();
        }
    }

    private function renderIndex(Request $request, string $openModal): View
    {
        $school = $this->schoolProfileService->getCurrentSchool();
        $keyword = trim((string) $request->query('q', ''));

        $students = Student::query()
            ->where('school_id', $school->id)
            ->with('major:id,name,code')
            ->when($keyword !== '', function ($query) use ($keyword): void {
                $query->where(function ($inner) use ($keyword): void {
                    $inner->where('name', 'like', '%' . $keyword . '%')
                        ->orWhere('nisn', 'like', '%' . $keyword . '%');
                });
            })
            ->orderBy('name')
            ->get();

        return view('admin.students.index', [
            'students' => $students,
            'school' => $school,
            'majors' => $school->majors()->orderBy('name')->get(['id', 'name', 'code']),
            'isSmk' => $school->tipe_sekolah === 'SMK',
            'keyword' => $keyword,
            'openModal' => $openModal,
        ]);
    }
}
