<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GraduationDocument;
use App\Services\DocumentNumberService;
use App\Services\DocumentTemplateService;
use App\Services\SchoolProfileService;
use App\Services\SklPdfService;
use App\Services\SklSnapshotService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DocumentTemplateController extends Controller
{
    public function __construct(
        private readonly SchoolProfileService $schoolProfileService,
        private readonly DocumentTemplateService $documentTemplateService,
        private readonly SklPdfService $sklPdfService,
        private readonly SklSnapshotService $sklSnapshotService,
        private readonly DocumentNumberService $documentNumberService,
    ) {
    }

    public function index(Request $request): View
    {
        $school = $this->schoolProfileService->getCurrentSchool();
        $types = collect($this->documentTemplateService->supportedTypes());
        $activeType = (string) $request->string('type', DocumentTemplateService::TYPE_SKL);

        if (!$types->pluck('type')->contains($activeType)) {
            $activeType = DocumentTemplateService::TYPE_SKL;
        }

        $templates = $types->mapWithKeys(function (array $item) use ($school): array {
            $template = $this->documentTemplateService->forSchool($school, $item['type']);

            return [
                $item['type'] => $template,
            ];
        });

        $sampleStudent = $school->students()->with('major')->orderBy('name')->first();
        $previewVariables = [];
        foreach ($types as $item) {
            $previewVariables[$item['type']] = $this->documentTemplateService->previewVariables($school, $item['type'], $sampleStudent);
        }

        return view('admin.graduation.templates.index', [
            'school' => $school,
            'templates' => $templates,
            'supportedTypes' => $types->values(),
            'activeType' => $activeType,
            'placeholders' => $this->documentTemplateService->placeholders(),
            'previewVariables' => $previewVariables,
            'sampleStudent' => $sampleStudent,
        ]);
    }

    public function update(Request $request, string $documentType): RedirectResponse
    {
        $supportedTypes = collect($this->documentTemplateService->supportedTypes())->pluck('type')->all();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'paper_size' => ['required', 'string', Rule::in(['a4', 'legal', 'f4', 'letter'])],
            'orientation' => ['required', 'string', Rule::in(['portrait', 'landscape'])],
            'title_html' => ['nullable', 'string'],
            'intro_html' => ['nullable', 'string'],
            'body_html' => ['nullable', 'string'],
            'closing_html' => ['nullable', 'string'],
            'document_type' => ['required', 'string', Rule::in($supportedTypes)],
        ]);

        if ($validated['document_type'] !== $documentType) {
            abort(422, 'Jenis template tidak sinkron.');
        }

        $school = $this->schoolProfileService->getCurrentSchool();
        $this->documentTemplateService->update($school, $documentType, $validated);

        return redirect()
            ->route('admin.graduation.templates.index', ['type' => $documentType])
            ->with('status', 'Template surat berhasil diperbarui.');
    }

    /**
     * Preview PDF from template editor
     */
    public function preview(Request $request, string $documentType): \Symfony\Component\HttpFoundation\Response
    {
        $school = $this->schoolProfileService->getCurrentSchool();
        $sampleStudent = $school->students()->with('major')->orderBy('name')->first();

        if (!$sampleStudent) {
            return response('Data siswa tidak ditemukan untuk pratinjau.', 404);
        }

        // For SKL, we use the SklPdfService
        // We'll need a temporary document or just mock the logic
        // Actually, the easiest way is to find/create a dummy GraduationDocument for this student
        $document = GraduationDocument::updateOrCreate(
            [
                'school_id' => $school->id,
                'student_id' => $sampleStudent->id,
                'document_type' => $documentType,
            ],
            [
                'status' => 'Lulus',
                'issued_at' => $school->tanggal_surat ?: now(),
                'snapshot_payload' => $this->sklSnapshotService->buildPayload($sampleStudent),
            ]
        );

        $document->document_number = null; // Force regeneration
        $document->document_number = $this->documentNumberService->generate($document);
        $document->save();

        $pdfPath = $this->sklPdfService->render($document);

        return response()->file($pdfPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="preview-' . $documentType . '.pdf"',
        ]);
    }
}
