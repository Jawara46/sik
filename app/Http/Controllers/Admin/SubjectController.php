<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Services\SchoolProfileService;
use App\Services\SubjectManagementService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SubjectController extends Controller
{
    public function __construct(
        private readonly SubjectManagementService $subjectManagementService,
        private readonly SchoolProfileService $schoolProfileService,
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
            'name' => ['required', 'string', 'max:100'],
            'category' => ['required', Rule::in($this->subjectManagementService->categories())],
            'major_ids' => ['nullable', 'array'],
            'major_ids.*' => ['integer'],
        ]);

        $this->subjectManagementService->create($school->id, $validated);

        return back()->with('status', 'Mata pelajaran berhasil ditambahkan.');
    }

    public function update(Request $request, Subject $subject): RedirectResponse
    {
        $school = $this->schoolProfileService->getCurrentSchool();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'category' => ['required', Rule::in($this->subjectManagementService->categories())],
            'major_ids' => ['nullable', 'array'],
            'major_ids.*' => ['integer'],
        ]);

        $this->subjectManagementService->update($school->id, $subject, $validated);

        return back()->with('status', 'Mata pelajaran berhasil diperbarui.');
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        try {
            $this->subjectManagementService->delete($subject);
        } catch (\InvalidArgumentException $exception) {
            return back()->withErrors([
                'subject' => $exception->getMessage(),
            ]);
        }

        return back()->with('status', 'Mata pelajaran berhasil dihapus.');
    }

    public function downloadTemplate(): BinaryFileResponse
    {
        $school = $this->schoolProfileService->getCurrentSchool();

        return $this->subjectManagementService->downloadTemplate($school->id);
    }

    public function import(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject_file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $school = $this->schoolProfileService->getCurrentSchool();
        $result = $this->subjectManagementService->import($validated['subject_file'], $school->id);
        if ($result['success'] !== true) {
            return back()->withErrors([
                'subject_file' => $result['message'],
            ]);
        }

        return back()
            ->with('status', $result['message'])
            ->with('subject_import_log', $result['skipped_rows']);
    }

    private function renderIndex(Request $request, string $openModal): View
    {
        $school = $this->schoolProfileService->getCurrentSchool();
        $keyword = trim((string) $request->query('q', ''));
        $subjects = Subject::query()
            ->with([
                'majors' => static fn ($query) => $query->where('school_id', $school->id),
            ])
            ->when($keyword !== '', function ($query) use ($keyword): void {
                $query->where('name', 'like', '%' . $keyword . '%');
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.subjects.index', [
            'subjects' => $subjects,
            'majors' => $school->majors()->orderBy('name')->get(['id', 'name', 'code']),
            'isSmk' => $school->tipe_sekolah === 'SMK',
            'categories' => $this->subjectManagementService->categories(),
            'keyword' => $keyword,
            'openModal' => $openModal,
        ]);
    }
}
