<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Major;
use App\Models\Student;
use App\Models\Subject;
use App\Services\SchoolProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SearchController extends Controller
{
    public function __construct(
        private readonly SchoolProfileService $schoolProfileService,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));
        $school = $this->schoolProfileService->getCurrentSchool();

        $groups = collect([
            $this->buildMenuGroup($query, $school->tipe_sekolah === 'SMK'),
            $this->buildStudentGroup($query, $school->id),
            $this->buildMajorGroup($query, $school->id, $school->tipe_sekolah === 'SMK'),
            $this->buildSubjectGroup($query, $school->id),
        ])->filter(static fn (?array $group): bool => $group !== null)->values();

        return response()->json([
            'query' => $query,
            'minimum_characters' => 2,
            'groups' => $groups,
        ]);
    }

    private function buildMenuGroup(string $query, bool $isSmk): ?array
    {
        $menus = collect([
            $this->menuItem(__('app.sidebar.dashboard'), route('admin.dashboard'), 'ri-dashboard-line', [__('app.search.keywords.analytics')]),
            $this->menuItem(__('app.sidebar.school_center'), route('admin.school.profile.index'), 'ri-building-2-line', [__('app.sidebar.school_profile')]),
            $this->menuItem(__('app.sidebar.students_center'), route('admin.students.index'), 'ri-team-line', [__('app.sidebar.students_list')]),
            $this->menuItem(__('app.sidebar.grade_management'), route('admin.grades.academic.index'), 'ri-task-line', [__('app.sidebar.academic_grades')]),
            $this->menuItem(__('app.sidebar.whatsapp_center'), route('admin.whatsapp.connection.index'), 'ri-whatsapp-line', [__('app.sidebar.gateway_connection')]),
            $this->menuItem(__('app.sidebar.graduation_services'), route('admin.graduation.documents.index'), 'ri-graduation-cap-line', [__('app.sidebar.print_documents')]),
            $this->menuItem(__('app.sidebar.system_config'), route('admin.settings.branding.index'), 'ri-settings-3-line', [__('app.sidebar.branding_settings')]),
            $this->menuItem(__('app.sidebar.subjects'), route('admin.school.subjects.index'), 'ri-book-open-line', [__('app.search.keywords.curriculum')]),
        ]);

        if ($isSmk) {
            $menus->push(
                $this->menuItem(__('app.sidebar.majors'), route('admin.school.majors.index'), 'ri-briefcase-4-line', [__('app.search.keywords.vocational')])
            );
        }

        $filtered = $this->filterStaticItems($menus, $query)->take(8)->values();
        if ($filtered->isEmpty()) {
            return null;
        }

        return [
            'key' => 'navigation',
            'label' => __('app.search.groups.navigation'),
            'items' => $filtered->all(),
        ];
    }

    private function buildStudentGroup(string $query, int $schoolId): ?array
    {
        if ($query === '' || mb_strlen($query) < 2) {
            return null;
        }

        $students = Student::query()
            ->where('school_id', $schoolId)
            ->with('major:id,name,code')
            ->where(function ($inner) use ($query): void {
                $inner->where('name', 'like', '%' . $query . '%')
                    ->orWhere('nisn', 'like', '%' . $query . '%')
                    ->orWhere('nis', 'like', '%' . $query . '%')
                    ->orWhereHas('major', function ($majorQuery) use ($query): void {
                        $majorQuery->where('name', 'like', '%' . $query . '%')
                            ->orWhere('code', 'like', '%' . $query . '%');
                    });
            })
            ->orderBy('name')
            ->limit(6)
            ->get();

        if ($students->isEmpty()) {
            return null;
        }

        return [
            'key' => 'students',
            'label' => __('app.search.groups.students'),
            'items' => $students->map(function (Student $student): array {
                $subtitleParts = array_filter([
                    $student->nisn !== null ? 'NISN: ' . $student->nisn : null,
                    $student->major?->code !== null ? __('app.search.labels.major') . ': ' . $student->major->code : null,
                ]);

                return [
                    'title' => $student->name,
                    'subtitle' => implode(' | ', $subtitleParts),
                    'url' => route('admin.students.index', ['q' => $student->nisn ?: $student->name]),
                    'icon' => 'ri-user-3-line',
                    'badge' => __('app.search.badges.student'),
                ];
            })->all(),
        ];
    }

    private function buildMajorGroup(string $query, int $schoolId, bool $isSmk): ?array
    {
        if (!$isSmk || $query === '' || mb_strlen($query) < 2) {
            return null;
        }

        $majors = Major::query()
            ->where('school_id', $schoolId)
            ->where(function ($inner) use ($query): void {
                $inner->where('name', 'like', '%' . $query . '%')
                    ->orWhere('code', 'like', '%' . $query . '%');
            })
            ->orderBy('name')
            ->limit(6)
            ->get();

        if ($majors->isEmpty()) {
            return null;
        }

        return [
            'key' => 'majors',
            'label' => __('app.search.groups.majors'),
            'items' => $majors->map(static function (Major $major): array {
                return [
                    'title' => $major->name,
                    'subtitle' => $major->code,
                    'url' => route('admin.school.majors.index', ['q' => $major->code]),
                    'icon' => 'ri-briefcase-4-line',
                    'badge' => __('app.search.badges.major'),
                ];
            })->all(),
        ];
    }

    private function buildSubjectGroup(string $query, int $schoolId): ?array
    {
        if ($query === '' || mb_strlen($query) < 2) {
            return null;
        }

        $subjects = Subject::query()
            ->with([
                'majors' => static fn ($queryBuilder) => $queryBuilder->where('school_id', $schoolId)->select('majors.id', 'majors.code'),
            ])
            ->where(function ($inner) use ($query): void {
                $inner->where('name', 'like', '%' . $query . '%')
                    ->orWhere('category', 'like', '%' . $query . '%');
            })
            ->orderBy('name')
            ->limit(6)
            ->get();

        if ($subjects->isEmpty()) {
            return null;
        }

        return [
            'key' => 'subjects',
            'label' => __('app.search.groups.subjects'),
            'items' => $subjects->map(function (Subject $subject): array {
                $majorCodes = $subject->majors->pluck('code')->implode(', ');
                $subtitle = $subject->category;

                if ($majorCodes !== '') {
                    $subtitle .= ' | ' . __('app.search.labels.major_map') . ': ' . $majorCodes;
                }

                return [
                    'title' => $subject->name,
                    'subtitle' => $subtitle,
                    'url' => route('admin.school.subjects.index', ['q' => $subject->name]),
                    'icon' => 'ri-book-open-line',
                    'badge' => __('app.search.badges.subject'),
                ];
            })->all(),
        ];
    }

    private function menuItem(string $title, string $url, string $icon, array $keywords = []): array
    {
        return [
            'title' => $title,
            'subtitle' => __('app.search.labels.quick_navigation'),
            'url' => $url,
            'icon' => $icon,
            'badge' => __('app.search.badges.menu'),
            'keywords' => $keywords,
        ];
    }

    /**
     * @param Collection<int, array<string, mixed>> $items
     * @return Collection<int, array<string, mixed>>
     */
    private function filterStaticItems(Collection $items, string $query): Collection
    {
        if ($query === '' || mb_strlen($query) < 2) {
            return $items->take(6)->values();
        }

        $needle = Str::lower($query);

        return $items->filter(static function (array $item) use ($needle): bool {
            $haystack = collect([
                $item['title'] ?? '',
                $item['subtitle'] ?? '',
                ...((array) ($item['keywords'] ?? [])),
            ])->map(static fn ($value): string => Str::lower((string) $value))->implode(' ');

            return Str::contains($haystack, $needle);
        })->map(static function (array $item): array {
            unset($item['keywords']);

            return $item;
        })->values();
    }
}
