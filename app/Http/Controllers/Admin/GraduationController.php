<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\DocumentReadinessService;
use App\Services\GradeRecapService;
use App\Services\GraduationDocumentService;
use App\Services\GraduationDocumentLogService;
use App\Services\GraduationStatusService;
use App\Services\GraduationDocumentType;
use App\Services\SchoolProfileService;
use App\Services\SklSnapshotService;
use App\Services\SklPdfService;
use App\Services\TranscriptPdfService;
use App\Services\TranscriptSnapshotService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GraduationController extends Controller
{
    public function __construct(
        private readonly GradeRecapService $gradeRecapService,
        private readonly DocumentReadinessService $documentReadinessService,
        private readonly GraduationStatusService $graduationStatusService,
        private readonly SklSnapshotService $sklSnapshotService,
        private readonly TranscriptSnapshotService $transcriptSnapshotService,
        private readonly GraduationDocumentService $graduationDocumentService,
        private readonly GraduationDocumentLogService $graduationDocumentLogService,
        private readonly SklPdfService $sklPdfService,
        private readonly TranscriptPdfService $transcriptPdfService,
        private readonly SchoolProfileService $schoolProfileService,
    ) {
    }

    public function status(Request $request): View
    {
        $school = $this->schoolProfileService->getCurrentSchool();
        $query = Student::query()
            ->with('major')
            ->where('school_id', $school->id)
            ->orderBy('name');

        if ($request->filled('q')) {
            $keyword = trim((string) $request->string('q'));
            $query->where(function ($builder) use ($keyword): void {
                $builder
                    ->where('name', 'like', '%' . $keyword . '%')
                    ->orWhere('nisn', 'like', '%' . $keyword . '%');
            });
        }

        $students = $query->get();

        return view('admin.graduation.status', [
            'students' => $students,
            'summary' => [
                'total_students' => Student::query()->where('school_id', $school->id)->count(),
                'lulus' => Student::query()->where('school_id', $school->id)->where('status', 'Lulus')->count(),
                'tidak_lulus' => Student::query()->where('school_id', $school->id)->where('status', 'Tidak Lulus')->count(),
                'pending' => Student::query()->where('school_id', $school->id)->where('status', 'Pending')->count(),
                'access_open' => Student::query()->where('school_id', $school->id)->where('access_locked', false)->where('status_administrasi', true)->count(),
            ],
            'filters' => [
                'q' => (string) $request->string('q'),
            ],
        ]);
    }

    public function documents(Request $request): View
    {
        $school = $this->schoolProfileService->getCurrentSchool();
        $students = Student::query()
            ->with(['school', 'major', 'graduationDocuments', 'grades.subject'])
            ->where('school_id', $school->id)
            ->orderBy('name')
            ->get();

        $selectedStudentId = $request->integer('student');
        $selectedStudent = $students->firstWhere('id', $selectedStudentId) ?? $students->first();
        $student = $selectedStudent instanceof Student ? $selectedStudent : null;

        $readinessByStudent = $students->mapWithKeys(function (Student $student): array {
            return [
                $student->id => $this->documentReadinessService->assess($student),
            ];
        });

        $readinessOverview = [
            'skl_ready' => $readinessByStudent->filter(fn (array $item): bool => (bool) data_get($item, 'documents.skl.ready'))->count(),
            'skl_blocked' => $readinessByStudent->filter(fn (array $item): bool => !(bool) data_get($item, 'documents.skl.ready'))->count(),
            'transcript_ready' => $readinessByStudent->filter(fn (array $item): bool => (bool) data_get($item, 'documents.transcript.ready'))->count(),
            'transcript_blocked' => $readinessByStudent->filter(fn (array $item): bool => !(bool) data_get($item, 'documents.transcript.ready'))->count(),
        ];

        $documentPreview = null;
        if ($student !== null) {
            $transcriptSummary = $this->gradeRecapService->getTranscriptSummary($student);
            $readiness = $readinessByStudent->get($student->id) ?? $this->documentReadinessService->assess($student);
            $sklDocument = $this->graduationDocumentService->findForStudent($student, GraduationDocumentType::SKL);
            $transcriptDocument = $this->graduationDocumentService->findForStudent($student, GraduationDocumentType::TRANSCRIPT);
            $documentPreview = [
                'student_name' => $student->name,
                'student_id' => $student->id,
                'nisn' => $student->nisn,
                'student_photo' => $student->photo,
                'show_student_photo_on_skl' => (bool) ($student->school?->show_student_photo_on_skl ?? false),
                'average_score' => $this->gradeRecapService->getAverageScore($student),
                'grouped_grades' => $this->gradeRecapService->getGroupedGrades($student),
                'transcript_summary' => $transcriptSummary,
                'readiness' => $readiness,
                'skl_snapshot_preview' => $this->sklSnapshotService->buildPayload($student),
                'transcript_snapshot_preview' => $this->transcriptSnapshotService->buildPayload($student),
                'documents' => [
                    'skl' => $sklDocument,
                    'transcript' => $transcriptDocument,
                ],
            ];
        }

        return view('admin.graduation.documents', [
            'documentPreview' => $documentPreview,
            'linkedService' => 'GradeRecapService',
            'students' => $students,
            'selectedStudentId' => $student?->id,
            'majors' => $school->majors()->orderBy('name')->get(),
            'readinessByStudent' => $readinessByStudent,
            'readinessOverview' => $readinessOverview,
        ]);
    }

    public function updateStudentStatus(Request $request, Student $student): RedirectResponse
    {
        $this->abortIfStudentOutsideCurrentSchool($student);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:Lulus,Tidak Lulus,Pending'],
        ]);

        $this->graduationStatusService->updateStatus($student, $validated['status']);

        return redirect()
            ->route('admin.graduation.status.index')
            ->with('status', 'Status kelulusan siswa berhasil diperbarui.');
    }

    public function updateStudentAccess(Request $request, Student $student): RedirectResponse
    {
        $this->abortIfStudentOutsideCurrentSchool($student);

        $validated = $request->validate([
            'is_locked' => ['required', 'boolean'],
        ]);

        $this->graduationStatusService->toggleAccessLock($student, (bool) $validated['is_locked']);

        return redirect()
            ->route('admin.graduation.status.index')
            ->with('status', 'Akses unduh siswa berhasil diperbarui.');
    }

    public function bulkUpdateStatus(Request $request): RedirectResponse
    {
        $school = $this->schoolProfileService->getCurrentSchool();
        $validated = $request->validate([
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['required', 'integer'],
            'status' => ['required', 'string', 'in:Lulus,Tidak Lulus,Pending'],
        ]);

        $studentIds = Student::query()
            ->where('school_id', $school->id)
            ->whereIn('id', $validated['student_ids'])
            ->pluck('id')
            ->all();

        if ($studentIds === []) {
            return redirect()
                ->route('admin.graduation.status.index')
                ->with('error', 'Tidak ada siswa valid yang dipilih untuk update status.');
        }

        $updated = $this->graduationStatusService->bulkUpdateStatus($studentIds, $validated['status']);

        return redirect()
            ->route('admin.graduation.status.index')
            ->with('status', 'Status kelulusan berhasil diperbarui untuk ' . $updated . ' siswa.');
    }

    public function bulkUpdateAccess(Request $request): RedirectResponse
    {
        $school = $this->schoolProfileService->getCurrentSchool();
        $validated = $request->validate([
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['required', 'integer'],
            'is_locked' => ['required', 'boolean'],
        ]);

        $studentIds = Student::query()
            ->where('school_id', $school->id)
            ->whereIn('id', $validated['student_ids'])
            ->pluck('id')
            ->all();

        if ($studentIds === []) {
            return redirect()
                ->route('admin.graduation.status.index')
                ->with('error', 'Tidak ada siswa valid yang dipilih untuk update akses.');
        }

        $updated = $this->graduationStatusService->bulkToggleAccess($studentIds, (bool) $validated['is_locked']);

        return redirect()
            ->route('admin.graduation.status.index')
            ->with('status', 'Akses unduh berhasil diperbarui untuk ' . $updated . ' siswa.');
    }

    public function bulkPublish(Request $request): RedirectResponse
    {
        $school = $this->schoolProfileService->getCurrentSchool();
        $validated = $request->validate([
            'document_type' => ['required', 'string', 'in:skl,transcript'],
            'major_id' => ['nullable', 'integer'],
            'student_status' => ['nullable', 'string', 'in:Lulus,Tidak Lulus,Pending'],
        ]);

        $query = Student::query()
            ->with(['school', 'major', 'grades.subject', 'graduationDocuments'])
            ->where('school_id', $school->id);

        if (!empty($validated['major_id'])) {
            $query->where('major_id', (int) $validated['major_id']);
        }

        if (!empty($validated['student_status'])) {
            $query->where('status', $validated['student_status']);
        }

        $students = $query->orderBy('name')->get();
        $published = 0;
        $skipped = [];

        foreach ($students as $student) {
            try {
                $document = $this->graduationDocumentService->publish(
                    $student,
                    $validated['document_type'],
                    auth('admin')->user(),
                );

                $pdfPath = $validated['document_type'] === GraduationDocumentType::SKL
                    ? $this->sklPdfService->render($document)
                    : $this->transcriptPdfService->render($document);

                $document->forceFill([
                    'last_accessed_at' => now(),
                    'updated_by' => auth('admin')->id(),
                ])->save();

                $this->graduationDocumentLogService->log(
                    $document,
                    'publish',
                    'admin',
                    auth('admin')->user(),
                    ['document_type' => $validated['document_type'], 'context' => 'bulk_publish', 'pdf_path' => $pdfPath]
                );

                $published++;
            } catch (ValidationException $exception) {
                $skipped[] = $student->name . ': ' . collect($exception->errors())->flatten()->join(' ');
            }
        }

        $message = 'Bulk publish selesai. Berhasil: ' . $published . ' siswa.';
        if ($skipped !== []) {
            $message .= ' Dilewati: ' . count($skipped) . ' siswa.';
        }

        return redirect()
            ->route('admin.graduation.documents.index')
            ->with('status', $message)
            ->with('bulk_publish_log', $skipped);
    }

    public function bulkSyncDrafts(Request $request): RedirectResponse
    {
        $school = $this->schoolProfileService->getCurrentSchool();
        $validated = $request->validate([
            'document_type' => ['required', 'string', 'in:skl,transcript'],
            'major_id' => ['nullable', 'integer'],
            'student_status' => ['nullable', 'string', 'in:Lulus,Tidak Lulus,Pending'],
        ]);

        $query = Student::query()
            ->with(['school', 'major', 'grades.subject'])
            ->where('school_id', $school->id);

        if (!empty($validated['major_id'])) {
            $query->where('major_id', (int) $validated['major_id']);
        }

        if (!empty($validated['student_status'])) {
            $query->where('status', $validated['student_status']);
        }

        $students = $query->orderBy('name')->get();
        $synced = 0;
        $failed = [];

        foreach ($students as $student) {
            try {
                $document = $this->graduationDocumentService->syncDraft(
                    $student,
                    $validated['document_type'],
                    auth('admin')->user(),
                );

                $this->graduationDocumentLogService->log(
                    $document,
                    'regenerate',
                    'admin',
                    auth('admin')->user(),
                    ['document_type' => $validated['document_type'], 'context' => 'bulk_sync']
                );

                $synced++;
            } catch (\Throwable $exception) {
                $failed[] = $student->name . ': ' . $exception->getMessage();
            }
        }

        $message = 'Bulk sinkronisasi draft selesai. Berhasil: ' . $synced . ' siswa.';
        if ($failed !== []) {
            $message .= ' Gagal: ' . count($failed) . ' siswa.';
        }

        return redirect()
            ->route('admin.graduation.documents.index')
            ->with('status', $message)
            ->with('bulk_sync_log', $failed);
    }

    public function bulkGenerateCache(Request $request): RedirectResponse
    {
        $school = $this->schoolProfileService->getCurrentSchool();
        $validated = $request->validate([
            'document_type' => ['required', 'string', 'in:skl,transcript'],
            'major_id' => ['nullable', 'integer'],
            'student_status' => ['nullable', 'string', 'in:Lulus,Tidak Lulus,Pending'],
        ]);

        $query = Student::query()
            ->with(['school', 'major', 'grades.subject', 'graduationDocuments'])
            ->where('school_id', $school->id);

        if (!empty($validated['major_id'])) {
            $query->where('major_id', (int) $validated['major_id']);
        }

        if (!empty($validated['student_status'])) {
            $query->where('status', $validated['student_status']);
        }

        $students = $query->orderBy('name')->get();
        $generated = 0;
        $skipped = [];

        foreach ($students as $student) {
            $document = $this->graduationDocumentService->findForStudent($student, $validated['document_type']);

            if ($document === null || $document->status !== 'published') {
                $skipped[] = $student->name . ': dokumen belum published.';
                continue;
            }

            try {
                $pdfPath = $validated['document_type'] === GraduationDocumentType::SKL
                    ? $this->sklPdfService->render($document)
                    : $this->transcriptPdfService->render($document);

                $document->forceFill([
                    'last_accessed_at' => now(),
                    'updated_by' => auth('admin')->id(),
                ])->save();

                $this->graduationDocumentLogService->log(
                    $document,
                    'regenerate',
                    'admin',
                    auth('admin')->user(),
                    ['document_type' => $validated['document_type'], 'context' => 'bulk_cache', 'pdf_path' => $pdfPath]
                );

                $generated++;
            } catch (\Throwable $exception) {
                $skipped[] = $student->name . ': ' . $exception->getMessage();
            }
        }

        $message = 'Generate PDF cache selesai. Berhasil: ' . $generated . ' dokumen.';
        if ($skipped !== []) {
            $message .= ' Dilewati: ' . count($skipped) . ' dokumen.';
        }

        return redirect()
            ->route('admin.graduation.documents.index')
            ->with('status', $message)
            ->with('bulk_cache_log', $skipped);
    }

    public function syncSklDraft(Student $student): RedirectResponse
    {
        return $this->syncDocumentDraft($student, GraduationDocumentType::SKL, 'Draft SKL berhasil disinkronkan.');
    }

    public function previewSkl(Student $student): BinaryFileResponse
    {
        return $this->previewDocumentDraft($student, GraduationDocumentType::SKL);
    }

    public function printSkl(Student $student): BinaryFileResponse|RedirectResponse
    {
        return $this->printDocument($student, GraduationDocumentType::SKL);
    }

    public function syncTranscriptDraft(Student $student): RedirectResponse
    {
        return $this->syncDocumentDraft($student, GraduationDocumentType::TRANSCRIPT, 'Draft transkrip berhasil disinkronkan.');
    }

    public function previewTranscript(Student $student): BinaryFileResponse
    {
        return $this->previewDocumentDraft($student, GraduationDocumentType::TRANSCRIPT);
    }

    public function printTranscript(Student $student): BinaryFileResponse|RedirectResponse
    {
        return $this->printDocument($student, GraduationDocumentType::TRANSCRIPT);
    }

    public function publishSkl(Student $student): RedirectResponse
    {
        return $this->publishDocument($student, GraduationDocumentType::SKL, 'Dokumen SKL berhasil dipublish.');
    }

    public function publishTranscript(Student $student): RedirectResponse
    {
        return $this->publishDocument($student, GraduationDocumentType::TRANSCRIPT, 'Dokumen transkrip berhasil dipublish.');
    }

    public function revokeSkl(Student $student): RedirectResponse
    {
        return $this->revokeDocument($student, GraduationDocumentType::SKL, 'Dokumen SKL berhasil dicabut.');
    }

    public function revokeTranscript(Student $student): RedirectResponse
    {
        return $this->revokeDocument($student, GraduationDocumentType::TRANSCRIPT, 'Dokumen transkrip berhasil dicabut.');
    }

    public function downloadSkl(Student $student): BinaryFileResponse|RedirectResponse
    {
        return $this->downloadPublishedDocument($student, GraduationDocumentType::SKL);
    }

    public function downloadTranscript(Student $student): BinaryFileResponse|RedirectResponse
    {
        return $this->downloadPublishedDocument($student, GraduationDocumentType::TRANSCRIPT);
    }

    private function syncDocumentDraft(Student $student, string $documentType, string $successMessage): RedirectResponse
    {
        $this->abortIfStudentOutsideCurrentSchool($student);

        $document = $this->graduationDocumentService->syncDraft(
            $student,
            $documentType,
            auth('admin')->user(),
        );

        $this->graduationDocumentLogService->log(
            $document,
            'regenerate',
            'admin',
            auth('admin')->user(),
            ['document_type' => $documentType, 'context' => 'draft_sync']
        );

        return redirect()
            ->route('admin.graduation.documents.index', ['student' => $student->id])
            ->with('status', $successMessage . ' Token verifikasi: ' . $document->verification_token);
    }

    private function previewDocumentDraft(Student $student, string $documentType): BinaryFileResponse
    {
        $this->abortIfStudentOutsideCurrentSchool($student);

        $document = $this->graduationDocumentService->syncDraft(
            $student,
            $documentType,
            auth('admin')->user(),
        );

        $pdfPath = $documentType === GraduationDocumentType::SKL
            ? $this->sklPdfService->render($document)
            : $this->transcriptPdfService->render($document);

        $document->forceFill([
            'last_accessed_at' => now(),
            'updated_by' => auth('admin')->id(),
        ])->save();

        $this->graduationDocumentLogService->log(
            $document,
            'view',
            'admin',
            auth('admin')->user(),
            ['document_type' => $documentType, 'context' => 'draft_preview']
        );

        $filename = $documentType === GraduationDocumentType::SKL
            ? 'Preview_SKL_' . str_replace(' ', '_', $student->name) . '.pdf'
            : 'Preview_Transkrip_' . str_replace(' ', '_', $student->name) . '.pdf';

        return response()->file($pdfPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    private function publishDocument(Student $student, string $documentType, string $successMessage): RedirectResponse
    {
        $this->abortIfStudentOutsideCurrentSchool($student);

        try {
            $document = $this->graduationDocumentService->publish(
                $student,
                $documentType,
                auth('admin')->user(),
            );
        } catch (ValidationException $exception) {
            $messages = collect($exception->errors())->flatten()->join(' ');

            return redirect()
                ->route('admin.graduation.documents.index', ['student' => $student->id])
                ->with('error', $messages !== '' ? $messages : 'Dokumen belum memenuhi syarat untuk dipublish.');
        }

        $pdfPath = $documentType === GraduationDocumentType::SKL
            ? $this->sklPdfService->render($document)
            : $this->transcriptPdfService->render($document);

        $document->forceFill([
            'last_accessed_at' => now(),
            'updated_by' => auth('admin')->id(),
        ])->save();

        $this->graduationDocumentLogService->log(
            $document,
            'publish',
            'admin',
            auth('admin')->user(),
            ['document_type' => $documentType, 'pdf_path' => $pdfPath]
        );

        return redirect()
            ->route('admin.graduation.documents.index', ['student' => $student->id])
            ->with('status', $successMessage . ' Nomor dokumen: ' . ($document->document_number ?? '-'));
    }

    private function revokeDocument(Student $student, string $documentType, string $successMessage): RedirectResponse
    {
        $this->abortIfStudentOutsideCurrentSchool($student);

        $document = $this->graduationDocumentService->findForStudent($student, $documentType);
        if ($document === null) {
            return redirect()
                ->route('admin.graduation.documents.index', ['student' => $student->id])
                ->with('error', 'Dokumen belum pernah dibuat sehingga tidak bisa dicabut.');
        }

        $document = $this->graduationDocumentService->revoke($document, auth('admin')->user());
        $this->graduationDocumentLogService->log(
            $document,
            'revoke',
            'admin',
            auth('admin')->user(),
            ['document_type' => $documentType]
        );

        return redirect()
            ->route('admin.graduation.documents.index', ['student' => $student->id])
            ->with('status', $successMessage);
    }

    private function downloadPublishedDocument(Student $student, string $documentType): BinaryFileResponse|RedirectResponse
    {
        $this->abortIfStudentOutsideCurrentSchool($student);

        $document = $this->graduationDocumentService->findForStudent($student, $documentType);
        if ($document === null || $document->status !== 'published') {
            return redirect()
                ->route('admin.graduation.documents.index', ['student' => $student->id])
                ->with('error', 'Dokumen belum berstatus published sehingga belum bisa diunduh.');
        }

        $pdfPath = $documentType === GraduationDocumentType::SKL
            ? $this->sklPdfService->render($document)
            : $this->transcriptPdfService->render($document);

        $document->forceFill([
            'last_accessed_at' => now(),
            'updated_by' => auth('admin')->id(),
        ])->save();

        $this->graduationDocumentLogService->log(
            $document,
            'download',
            'admin',
            auth('admin')->user(),
            ['document_type' => $documentType]
        );

        $filename = $documentType === GraduationDocumentType::SKL
            ? 'SKL_' . str_replace(' ', '_', $student->name) . '.pdf'
            : 'Transkrip_' . str_replace(' ', '_', $student->name) . '.pdf';

        return response()->download($pdfPath, $filename, ['Content-Type' => 'application/pdf']);
    }

    private function printDocument(Student $student, string $documentType): BinaryFileResponse|RedirectResponse
    {
        $this->abortIfStudentOutsideCurrentSchool($student);

        try {
            $document = $this->graduationDocumentService->publish(
                $student,
                $documentType,
                auth('admin')->user(),
            );
        } catch (ValidationException $exception) {
            $messages = collect($exception->errors())->flatten()->join(' ');

            return redirect()
                ->route('admin.graduation.documents.index', ['student' => $student->id])
                ->with('error', $messages !== '' ? $messages : 'Dokumen belum memenuhi syarat untuk dicetak.');
        }

        $pdfPath = $documentType === GraduationDocumentType::SKL
            ? $this->sklPdfService->render($document)
            : $this->transcriptPdfService->render($document);

        $document->forceFill([
            'last_accessed_at' => now(),
            'updated_by' => auth('admin')->id(),
        ])->save();

        $this->graduationDocumentLogService->log(
            $document,
            'view',
            'admin',
            auth('admin')->user(),
            ['document_type' => $documentType, 'context' => 'quick_print']
        );

        $filename = $documentType === GraduationDocumentType::SKL
            ? 'SKL_' . str_replace(' ', '_', $student->name) . '.pdf'
            : 'Transkrip_' . str_replace(' ', '_', $student->name) . '.pdf';

        return response()->file($pdfPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    private function abortIfStudentOutsideCurrentSchool(Student $student): void
    {
        $school = $this->schoolProfileService->getCurrentSchool();

        if ((int) $student->school_id !== (int) $school->id) {
            abort(404);
        }
    }
}
