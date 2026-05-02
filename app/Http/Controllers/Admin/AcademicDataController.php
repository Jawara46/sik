<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AnnouncementService;
use App\Services\SchoolProfileService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AcademicDataController extends Controller
{
    public function __construct(
        private readonly SchoolProfileService $schoolProfileService,
        private readonly AnnouncementService $announcementService,
    ) {
    }

    public function academicYears(): View
    {
        return view('admin.school.academic-years', [
            'school' => $this->schoolProfileService->getCurrentSchool(),
            'announcementAt' => $this->announcementService->getAnnouncementAt(),
        ]);
    }

    public function updateAcademicYears(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tahun_pelajaran' => ['required', 'string', 'max:20'],
            'semester' => ['required', 'in:Ganjil,Genap'],
            'announcement_date' => ['nullable', 'date_format:Y-m-d\TH:i'],
        ]);

        $this->schoolProfileService->updateProfile([
            'tahun_pelajaran' => $validated['tahun_pelajaran'],
            'semester' => $validated['semester'],
        ], []);

        if (($validated['announcement_date'] ?? null) !== null && Schema::hasTable('settings')) {
            $timezone = (string) config('sik.announcement_timezone', 'Asia/Jakarta');
            $announcementAt = CarbonImmutable::createFromFormat(
                'Y-m-d\TH:i',
                (string) $validated['announcement_date'],
                $timezone,
            );

            if ($announcementAt !== false) {
                $this->announcementService->saveAnnouncementAt($announcementAt);
            }
        }

        return redirect()
            ->route('admin.school.academic-years.index')
            ->with('status', 'Tahun akademik aktif berhasil diperbarui.');
    }

    public function majors(): RedirectResponse
    {
        return redirect()->route('admin.school.majors.index');
    }

    public function gradesCompetency(): RedirectResponse
    {
        return redirect()->route('admin.grades.competency.index');
    }
}
