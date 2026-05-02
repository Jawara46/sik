<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\SmkCompetencyService;
use App\Services\SchoolProfileService;
use App\Services\SmkExcelService;
use App\Services\CertificateService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SmkRecordController extends Controller
{
    public function __construct(
        private SchoolProfileService $schoolProfileService,
        private SmkCompetencyService $smkCompetencyService,
        private SmkExcelService $smkExcelService,
        private CertificateService $certificateService,
    ) {
    }

    public function index(): View
    {
        $school = $this->schoolProfileService->getCurrentSchool();
        
        // This feature is exclusive to SMK
        if ($school->tipe_sekolah !== 'SMK') {
            abort(403, 'Fitur Kompetensi & PKL hanya tersedia untuk tipe sekolah SMK.');
        }

        $students = Student::query()
            ->where('school_id', '=', $school->id)
            ->with(['major', 'smkRecord'])
            ->orderBy('name')
            ->get();

        $majors = \App\Models\Major::query()->orderBy('name')->get();

        return view('admin.smk.index', [
            'students' => $students,
            'school' => $school,
            'majors' => $majors,
        ]);
    }

    public function getRecordData(Student $student): JsonResponse
    {
        $schoolId = $this->schoolProfileService->getCurrentSchool()->id;
        if ((int) $student->school_id !== (int) $schoolId) {
            abort(404);
        }

        $record = $student->smkRecord;
        
        $majorId = $student->major_id;
        $units = [];
        if ($majorId) {
            $units = \App\Models\SmkUnit::where('major_id', $majorId)->orderBy('kode_unit')->get();
        }

        $scores = [];
        if ($record) {
            foreach ($record->units as $recordUnit) {
                $scores[$recordUnit->smk_unit_id] = $recordUnit->score;
            }
        }

        $formattedUnits = $units->map(function ($unit) use ($scores) {
            return [
                'id' => $unit->id,
                'kode_unit' => $unit->kode_unit,
                'judul_unit' => $unit->judul_unit,
                'score' => $scores[$unit->id] ?? null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'company_name' => $record->company_name ?? '',
                'pkl_score' => $record->pkl_score ?? '',
                'ukk_status' => $record->ukk_status ?? '',
                'units' => $formattedUnits,
            ]
        ]);
    }

    public function update(Request $request, Student $student): JsonResponse
    {
        $schoolId = $this->schoolProfileService->getCurrentSchool()->id;
        if ((int) $student->school_id !== (int) $schoolId) {
            abort(404);
        }

        $validated = $request->validate([
            'company_name' => ['nullable', 'string', 'max:255'],
            'pkl_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'ukk_internal_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'ukk_external_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'ukk_status' => ['nullable', 'string', 'max:50'],
        ]);

        $record = $this->smkCompetencyService->calculateAndUpdateSmkRecord((int) $student->id, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Data kompetensi berhasil diperbarui.',
            'data' => $record,
        ]);
    }

    public function updatePklInline(Request $request, Student $student): JsonResponse
    {
        $schoolId = $this->schoolProfileService->getCurrentSchool()->id;
        if ((int) $student->school_id !== (int) $schoolId) {
            abort(404);
        }

        $validated = $request->validate([
            'company_name' => ['nullable', 'string', 'max:255'],
            'pkl_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $record = $student->smkRecord ?? $student->smkRecord()->create();
        
        // Update only the provided fields
        if ($request->has('company_name')) {
            $record->company_name = $validated['company_name'];
        }
        if ($request->has('pkl_score')) {
            $record->pkl_score = $validated['pkl_score'];
        }
        
        $record->save();

        return response()->json([
            'success' => true,
            'message' => 'Data PKL tersimpan otomatis.',
        ]);
    }

    public function destroyRecord(Student $student): JsonResponse
    {
        $schoolId = $this->schoolProfileService->getCurrentSchool()->id;
        if ((int) $student->school_id !== (int) $schoolId) {
            abort(404);
        }

        $record = $student->smkRecord;
        if ($record) {
            // Delete associated Unit records
            $record->units()->delete();
            
            // Delete the main record itself
            $record->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Seluruh data nilai UKK & PKL siswa berhasil direset/dihapus.'
        ]);
    }

    public function downloadTemplate(Request $request): BinaryFileResponse|RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:pkl,units'],
            'major_id' => ['nullable', 'integer', 'exists:majors,id'],
        ]);

        $schoolId = $this->schoolProfileService->getCurrentSchool()->id;
        
        if ($validated['type'] === 'units') {
            if (empty($validated['major_id'])) {
                return back()->withErrors(['major_id' => 'Silakan pilih jurusan untuk mengunduh template Nilai Kompetensi.']);
            }
            return $this->smkExcelService->downloadUnitsTemplate($schoolId, (int) $validated['major_id']);
        }

        return $this->smkExcelService->downloadPklTemplate($schoolId);
    }

    public function import(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'import_type' => ['required', 'string', 'in:pkl,units'],
            'pkl_file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $schoolId = $this->schoolProfileService->getCurrentSchool()->id;
        
        if ($validated['import_type'] === 'units') {
            $result = $this->smkExcelService->importUnits($validated['pkl_file'], $schoolId);
        } else {
            $result = $this->smkExcelService->importPklRecords($validated['pkl_file'], $schoolId);
        }

        if ($result['success'] !== true) {
            return back()->withErrors([
                'pkl_file' => $result['message'],
            ]);
        }

        return back()
            ->with('status', $result['message'])
            ->with('import_log', $result['skipped_rows'] ?? []);
    }

    public function printCertificate(Student $student): BinaryFileResponse
    {
        [$school, $record] = $this->resolvePrintableRecord($student);
        $pdfPath = $this->certificateService->renderCertificatePdf($record, $school);
        $filename = 'Sertifikat_UKK_' . str_replace(' ', '_', $student->name) . '_' . date('Y') . '.pdf';

        return response()->file($pdfPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    public function printStatement(Student $student): BinaryFileResponse
    {
        [$school, $record] = $this->resolvePrintableRecord($student);
        $pdfPath = $this->certificateService->renderStatementPdf($record, $school);
        $filename = 'Surat_Keterangan_UKK_' . str_replace(' ', '_', $student->name) . '_' . date('Y') . '.pdf';

        return response()->file($pdfPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    /**
     * @return array{0: \App\Models\School, 1: \App\Models\SmkRecord}
     */
    private function resolvePrintableRecord(Student $student): array
    {
        $school = $this->schoolProfileService->getCurrentSchool();
        if ((int) $student->school_id !== (int) $school->id) {
            abort(404);
        }

        $record = $student->smkRecord;
        if (!$record || !in_array($record->ukk_status, ['Kompeten', 'Sangat Kompeten'], true)) {
            abort(403, 'Siswa belum memiliki rekam UKK yang dinyatakan Kompeten.');
        }

        return [$school, $record];
    }
}
