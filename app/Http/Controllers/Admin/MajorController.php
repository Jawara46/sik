<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Major;
use App\Services\MajorManagementService;
use App\Services\SchoolProfileService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MajorController extends Controller
{
    public function __construct(
        private readonly MajorManagementService $majorManagementService,
        private readonly SchoolProfileService $schoolProfileService,
    ) {
    }

    public function index(Request $request): View|RedirectResponse
    {
        $school = $this->schoolProfileService->getCurrentSchool();
        if ($school->tipe_sekolah !== 'SMK') {
            return redirect()->route('admin.school.profile.index')->withErrors([
                'major' => 'Menu jurusan hanya tersedia untuk sekolah tipe SMK.',
            ]);
        }

        $keyword = trim((string) $request->query('q', ''));
        $majors = $this->majorManagementService->forSchool($school->id)
            ->when($keyword !== '', static function ($collection) use ($keyword) {
                return $collection->filter(static function (Major $major) use ($keyword): bool {
                    return Str::contains(Str::lower($major->name), Str::lower($keyword))
                        || Str::contains(Str::lower($major->code), Str::lower($keyword));
                })->values();
            });

        return view('admin.majors.index', [
            'majors' => $majors,
            'keyword' => $keyword,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $school = $this->schoolProfileService->getCurrentSchool();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('majors', 'code')->where(
                    fn ($query) => $query->where('school_id', $school->id)
                ),
            ],
        ]);

        $this->majorManagementService->create($school->id, $validated);

        return back()->with('status', 'Jurusan berhasil ditambahkan.');
    }

    public function update(Request $request, Major $major): RedirectResponse
    {
        $school = $this->schoolProfileService->getCurrentSchool();
        $this->majorManagementService->assertSameSchool($major, $school->id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('majors', 'code')
                    ->where(fn ($query) => $query->where('school_id', $school->id))
                    ->ignore($major->id),
            ],
        ]);

        $this->majorManagementService->update($major, $validated);

        return back()->with('status', 'Jurusan berhasil diperbarui.');
    }

    public function destroy(Major $major): RedirectResponse
    {
        $school = $this->schoolProfileService->getCurrentSchool();
        $this->majorManagementService->assertSameSchool($major, $school->id);
        $this->majorManagementService->delete($major);

        return back()->with('status', 'Jurusan berhasil dihapus.');
    }
}
