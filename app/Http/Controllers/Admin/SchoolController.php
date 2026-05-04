<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AnnouncementService;
use App\Services\SchoolProfileService;
use App\Services\WaSessionService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use App\Models\Major;
use App\Models\SmkAssessor;

class SchoolController extends Controller
{
    public function __construct(
        private readonly SchoolProfileService $schoolProfileService,
        private readonly WaSessionService $waSessionService,
        private readonly AnnouncementService $announcementService,
    ) {
    }

    public function index(): View
    {
        $school = $this->schoolProfileService->getCurrentSchool();
        $waStatus = $this->waSessionService->getStatus($school);

        $majors = [];
        if ($school->tipe_sekolah === 'SMK') {
            $majors = Major::with(['smkAssessor' => function ($query) use ($school): void {
                $query->where('school_id', $school->id);
            }])->where('school_id', $school->id)->get();
        }

        return view('admin.school.index', [
            'school' => $school,
            'waStatus' => $waStatus,
            'announcementAt' => $this->announcementService->getAnnouncementAt(),
            'majors' => $majors,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'npsn' => ['required', 'string', 'max:20'],
            'nama_sekolah' => ['required', 'string', 'max:150'],
            'email_sekolah' => ['nullable', 'email', 'max:150'],
            'telepon_sekolah' => ['nullable', 'string', 'max:30'],
            'web_sekolah' => ['nullable', 'url', 'max:255'],
            'alamat_sekolah' => ['nullable', 'string'],
            'tempat_surat' => ['nullable', 'string', 'max:120'],
            'tipe_sekolah' => ['required', 'in:SMP,MTs,SMK'],
            'nama_kepsek' => ['nullable', 'string', 'max:150'],
            'nip_kepsek' => ['nullable', 'string', 'max:50'],
            'tahun_pelajaran' => ['nullable', 'string', 'max:20'],
            'semester' => ['nullable', 'in:Ganjil,Genap'],
            'tanggal_surat' => ['nullable', 'date'],
            'show_pkl_transcript' => ['nullable', 'boolean'],
            'show_student_photo_on_skl' => ['nullable', 'boolean'],
            'show_grades_on_skl' => ['nullable', 'boolean'],
            'skl_number_pattern' => ['nullable', 'string', 'max:150'],
            'skl_number_mode' => ['nullable', 'in:dynamic,static'],
            'transcript_number_pattern' => ['nullable', 'string', 'max:150'],
            'transcript_number_mode' => ['nullable', 'in:dynamic,static'],
            'certificate_number_pattern' => ['nullable', 'string', 'max:150'],
            'certificate_number_mode' => ['nullable', 'in:dynamic,static'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'kop_surat' => ['nullable', 'image', 'max:4096'],
            'ttd_kepsek' => ['nullable', 'image', 'mimes:png', 'max:2048'],
            'stempel_sekolah' => ['nullable', 'image', 'mimes:png', 'max:2048'],
            'bg_countdown' => ['nullable', 'image', 'max:4096'],
            'use_digital_stamp' => ['nullable', 'boolean'],
            'use_envelope_animation' => ['nullable', 'boolean'],
        ]);

        $validated['show_pkl_transcript'] = $request->boolean('show_pkl_transcript');
        $validated['show_student_photo_on_skl'] = $request->boolean('show_student_photo_on_skl');
        $validated['show_grades_on_skl'] = $request->boolean('show_grades_on_skl');
        // Because unchecked HTML checkboxes send nothing, we check existence/boolean cast
        $validated['use_digital_stamp'] = $request->boolean('use_digital_stamp');
        $validated['use_envelope_animation'] = $request->boolean('use_envelope_animation');

        $this->schoolProfileService->updateProfile($validated, [
            'logo' => $request->file('logo'),
            'kop_surat' => $request->file('kop_surat'),
            'ttd_kepsek' => $request->file('ttd_kepsek'),
            'stempel_sekolah' => $request->file('stempel_sekolah'),
            'bg_countdown' => $request->file('bg_countdown'),
        ]);

        return redirect()
            ->route('admin.school.profile.index')
            ->with('status', 'Profil sekolah berhasil diperbarui.');
    }

    public function waStatus(): JsonResponse
    {
        $school = $this->schoolProfileService->getCurrentSchool();
        $status = $this->waSessionService->getStatus($school);

        return response()->json($status);
    }

    public function waQr(): JsonResponse
    {
        $school = $this->schoolProfileService->getCurrentSchool();
        $status = $this->waSessionService->refreshQr($school);

        return response()->json($status);
    }

    public function updateAnnouncementDate(Request $request): RedirectResponse
    {
        if (!Schema::hasTable('settings')) {
            return back()->withErrors([
                'announcement_date' => 'Tabel settings belum tersedia. Jalankan migrasi terlebih dahulu.',
            ]);
        }

        $validated = $request->validate([
            'announcement_date' => ['required', 'date_format:Y-m-d\TH:i'],
        ]);

        $timezone = (string) config('sik.announcement_timezone', 'Asia/Jakarta');
        $announcementAt = CarbonImmutable::createFromFormat(
            'Y-m-d\TH:i',
            $validated['announcement_date'],
            $timezone,
        );

        if ($announcementAt === false) {
            return back()->withErrors([
                'announcement_date' => 'Format tanggal tidak valid.',
            ]);
        }

        $this->announcementService->saveAnnouncementAt($announcementAt);

        return redirect()
            ->route('admin.school.profile.index')
            ->with('status', 'Jadwal rilis pengumuman berhasil diperbarui.');
    }

    public function updateAssessor(Request $request, int $majorId): RedirectResponse
    {
        $validated = $request->validate([
            'internal_name' => ['nullable', 'string', 'max:255'],
            'internal_nip' => ['nullable', 'string', 'max:255'],
            'external_name' => ['nullable', 'string', 'max:255'],
            'external_company' => ['nullable', 'string', 'max:255'],
            'external_position' => ['nullable', 'string', 'max:255'],
        ]);

        $schoolId = $this->schoolProfileService->getCurrentSchool()->id;

        SmkAssessor::updateOrCreate(
            [
                'school_id' => $schoolId,
                'major_id' => $majorId,
            ],
            $validated
        );

        return back()->with('status', 'Data Penguji Kompetensi berhasil diperbarui.');
    }
}
