<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Student;
use App\Services\GraduationDocumentLogService;
use App\Services\GraduationDocumentService;
use App\Services\GraduationDocumentType;
use App\Services\GraduationStatusService;
use App\Services\SklPdfService;
use App\Services\TranscriptPdfService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DashboardController extends Controller
{
    public function __construct(
        private readonly GraduationDocumentService $graduationDocumentService,
        private readonly GraduationDocumentLogService $graduationDocumentLogService,
        private readonly GraduationStatusService $graduationStatusService,
        private readonly SklPdfService $sklPdfService,
        private readonly TranscriptPdfService $transcriptPdfService,
    ) {
    }

    public function index(): View
    {
        /** @var Student $student */
        $student = auth('student')->user();
        $student->loadMissing(['school', 'major', 'graduationDocuments']);

        $sklDocument = $student->graduationDocuments->firstWhere('document_type', GraduationDocumentType::SKL);
        $transcriptDocument = $student->graduationDocuments->firstWhere('document_type', GraduationDocumentType::TRANSCRIPT);
        $canDownload = $this->canStudentDownload($student);

        $showEnvelope = session('show_envelope', false) && ($student->school?->use_envelope_animation ?? true);

        // Log envelope opening only when it actually shows
        if ($showEnvelope) {
            ActivityLog::record(
                event: 'open_envelope',
                description: $student->name . ' membuka amplop pengumuman kelulusan.',
                subjectName: $student->name,
                subject: $student,
                ip: request()->ip(),
                ua: request()->userAgent()
            );

            \App\Models\Notification::create([
                'title' => 'Amplop Dibuka',
                'message' => "Siswa <strong>{$student->name}</strong> baru saja membuka amplop kelulusan.",
                'type' => 'success',
                'icon' => 'ri-mail-open-line',
            ]);
        }

        return view('student.dashboard', [
            'student' => $student,
            'showEnvelope' => $showEnvelope,
            'canDownload' => $canDownload,
            'documents' => [
                'skl' => $sklDocument,
                'transcript' => $transcriptDocument,
            ],
        ]);
    }

    public function downloadSkl(): BinaryFileResponse|RedirectResponse
    {
        return $this->downloadPublishedDocument(GraduationDocumentType::SKL);
    }

    public function downloadTranscript(): BinaryFileResponse|RedirectResponse
    {
        return $this->downloadPublishedDocument(GraduationDocumentType::TRANSCRIPT);
    }

    public function previewSkl(): \Symfony\Component\HttpFoundation\Response|RedirectResponse
    {
        return $this->previewPublishedDocument(GraduationDocumentType::SKL);
    }

    public function previewTranscript(): \Symfony\Component\HttpFoundation\Response|RedirectResponse
    {
        return $this->previewPublishedDocument(GraduationDocumentType::TRANSCRIPT);
    }


    private function downloadPublishedDocument(string $documentType): BinaryFileResponse|RedirectResponse
    {
        /** @var Student $student */
        $student = auth('student')->user();
        $student->loadMissing(['graduationDocuments', 'school', 'major']);

        if (!$this->canStudentDownload($student)) {
            return redirect()
                ->route('student.dashboard')
                ->with('error', 'Akses unduh dokumen masih dikunci oleh status administrasi atau status kelulusan belum final.');
        }

        $document = $student->graduationDocuments->firstWhere('document_type', $documentType);
        if ($document === null || $document->status !== 'published') {
            return redirect()
                ->route('student.dashboard')
                ->with('error', 'Dokumen belum tersedia untuk diunduh.');
        }

        $pdfPath = $documentType === GraduationDocumentType::SKL
            ? $this->sklPdfService->render($document)
            : $this->transcriptPdfService->render($document);

        $document->forceFill([
            'last_accessed_at' => now(),
        ])->save();

        $this->graduationDocumentLogService->log(
            $document,
            'download',
            'student',
            null,
            ['document_type' => $documentType, 'student_id' => $student->id]
        );

        // Activity log for dashboard
        $label = $documentType === GraduationDocumentType::SKL ? 'SKL' : 'Transkrip';
        ActivityLog::record(
            event: 'download_' . strtolower(str_replace(' ', '_', $documentType)),
            description: $student->name . ' mengunduh dokumen ' . $label . '.',
            subjectName: $student->name,
            subject: $student,
            ip: request()->ip(),
            ua: request()->userAgent()
        );

        \App\Models\Notification::create([
            'title' => 'Dokumen Diunduh',
            'message' => "Siswa <strong>{$student->name}</strong> mengunduh file <strong>{$label}</strong>.",
            'type' => 'info',
            'icon' => 'ri-file-download-line',
        ]);

        $filename = $documentType === GraduationDocumentType::SKL
            ? 'SKL_' . str_replace(' ', '_', $student->name) . '.pdf'
            : 'Transkrip_' . str_replace(' ', '_', $student->name) . '.pdf';

        return response()->download($pdfPath, $filename, ['Content-Type' => 'application/pdf']);
    }

    private function previewPublishedDocument(string $documentType): \Symfony\Component\HttpFoundation\Response|RedirectResponse
    {
        /** @var Student $student */
        $student = auth('student')->user();
        $student->loadMissing(['graduationDocuments', 'school', 'major']);

        if (!$this->canStudentDownload($student)) {
            return redirect()->route('student.dashboard')
                ->with('error', 'Akses preview dokumen masih dikunci.');
        }

        $document = $student->graduationDocuments->firstWhere('document_type', $documentType);
        if ($document === null || $document->status !== 'published') {
            return redirect()->route('student.dashboard')
                ->with('error', 'Dokumen belum tersedia untuk dipreview.');
        }

        $pdfPath = $documentType === GraduationDocumentType::SKL
            ? $this->sklPdfService->render($document)
            : $this->transcriptPdfService->render($document);

        $label = $documentType === GraduationDocumentType::SKL ? 'SKL' : 'Transkrip';
        $filename = $label . '_' . str_replace(' ', '_', $student->name) . '.pdf';

        // Stream inline — buka di browser PDF viewer, bukan force download
        return response()->file($pdfPath, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    private function canStudentDownload(Student $student): bool
    {
        return !$student->access_locked && $this->graduationStatusService->canDownloadDocument($student);
    }
}

