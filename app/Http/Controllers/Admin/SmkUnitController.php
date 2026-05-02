<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Major;
use App\Models\SmkUnit;
use App\Services\SchoolProfileService;
use App\Exports\SmkMasterUnitExport;
use App\Imports\SmkMasterUnitImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SmkUnitController extends Controller
{
    public function __construct(private SchoolProfileService $schoolProfileService)
    {
    }

    public function index(Request $request): View
    {
        $school = $this->schoolProfileService->getCurrentSchool();
        
        if ($school->tipe_sekolah !== 'SMK') {
            abort(403, 'Fitur Master Unit hanya tersedia untuk SMK.');
        }

        $majors = Major::where('school_id', $school->id)->orderBy('name')->get();
        
        $selectedMajorId = $request->query('major_id');
        if (!$selectedMajorId && $majors->isNotEmpty()) {
            $selectedMajorId = $majors->first()->id;
        }

        $units = [];
        if ($selectedMajorId) {
            $units = SmkUnit::with('major')
                ->where('major_id', '=', $selectedMajorId)
                ->orderBy('kode_unit')
                ->get();
        }

        return view('admin.school.smk-units', [
            'majors' => $majors,
            'units' => $units,
            'selectedMajorId' => $selectedMajorId,
            'school' => $school,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $schoolId = $this->schoolProfileService->getCurrentSchool()->id;

        $validated = $request->validate([
            'major_id' => [
                'required', 
                'integer', 
                Rule::exists('majors', 'id')->where('school_id', $schoolId)
            ],
            'kode_unit' => ['required', 'string', 'max:100'],
            'judul_unit' => ['required', 'string', 'max:255'],
        ]);

        $exists = SmkUnit::where('kode_unit', $validated['kode_unit'])
            ->where('major_id', $validated['major_id'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['kode_unit' => 'Kode Unit ini sudah ada di jurusan tersebut.'])->withInput();
        }

        SmkUnit::create($validated);

        return back()->with('status', 'Unit Kompetensi berhasil ditambahkan.');
    }

    public function update(Request $request, SmkUnit $unit): RedirectResponse
    {
        $schoolId = $this->schoolProfileService->getCurrentSchool()->id;
        $major = Major::findOrFail($unit->major_id);

        if ((int) $major->school_id !== (int) $schoolId) {
            abort(404);
        }

        $validated = $request->validate([
            'kode_unit' => ['required', 'string', 'max:100'],
            'judul_unit' => ['required', 'string', 'max:255'],
        ]);

        $conflict = SmkUnit::where('kode_unit', $validated['kode_unit'])
            ->where('major_id', $unit->major_id)
            ->where('id', '!=', $unit->id)
            ->exists();

        if ($conflict) {
            return back()->withErrors(['kode_unit' => 'Kode Unit ini sudah dipakai oleh unit lain di jurusan ini.'])->withInput();
        }

        $unit->update($validated);

        return back()->with('status', 'Data Unit Kompetensi berhasil diperbarui.');
    }

    public function destroy(SmkUnit $unit): RedirectResponse
    {
        $schoolId = $this->schoolProfileService->getCurrentSchool()->id;
        $major = Major::findOrFail($unit->major_id);

        if ((int) $major->school_id !== (int) $schoolId) {
            abort(404);
        }

        $unit->delete();

        return back()->with('status', 'Unit Kompetensi berhasil dihapus.');
    }

    public function downloadTemplate(): BinaryFileResponse|RedirectResponse
    {
        $schoolId = $this->schoolProfileService->getCurrentSchool()->id;
        $majors = Major::where('school_id', $schoolId)->orderBy('name')->get();

        if ($majors->isEmpty()) {
            return back()->withErrors(['major_id' => 'Belum ada jurusan sama sekali. Tambahkan minimal 1 jurusan.']);
        }

        $filename = 'Template_Master_Unit_Kompetensi.xlsx';
        return Excel::download(new SmkMasterUnitExport($majors), $filename);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'excel_file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ]);

        $schoolId = $this->schoolProfileService->getCurrentSchool()->id;

        try {
            Excel::import(new SmkMasterUnitImport($schoolId), $request->file('excel_file'));
            return back()->with('status', 'Data Master Unit Kompetensi berhasil diimpor.');
        } catch (\Exception $e) {
            return back()->withErrors(['excel_file' => 'Terjadi kesalahan saat mengimpor Excel: ' . $e->getMessage()]);
        }
    }
}
