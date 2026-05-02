<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Grade;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class GradeManagementService
{
    /**
     * @param EloquentCollection<int, Subject>|Collection<int, Subject>|null $subjects
     * @return array{
     *   student:Student,
     *   major_label:string,
     *   applicable_subjects:int,
     *   filled_semesters:int,
     *   expected_semesters:int,
     *   completion_percentage:int,
     *   final_subjects_count:int,
     *   status:string,
     *   status_label:string,
     *   status_class:string
     * }
     */
    public function buildStudentStatus(Student $student, Collection|EloquentCollection|null $subjects = null): array
    {
        $student->loadMissing(['school', 'major:id,name,code', 'grades']);
        $subjects = $subjects instanceof Collection || $subjects instanceof EloquentCollection
            ? $subjects
            : $this->getSubjects();

        $applicableSubjects = $this->filterApplicableSubjects($subjects, $student);
        $applicableSubjectIds = $applicableSubjects->pluck('id')->map(static fn (mixed $id): int => (int) $id)->all();
        $grades = $student->grades
            ->whereIn('semester', range(1, 6))
            ->whereIn('subject_id', $applicableSubjectIds);

        $expectedSemesters = $applicableSubjects->count() * 6;
        $filledSemesters = $grades->count();
        $finalSubjectsCount = $grades->groupBy('subject_id')->count();
        $completionPercentage = $expectedSemesters > 0
            ? (int) round(($filledSemesters / $expectedSemesters) * 100)
            : 0;
        $isComplete = $expectedSemesters > 0 && $filledSemesters >= $expectedSemesters;

        return [
            'student' => $student,
            'major_label' => $student->major?->code ?? '-',
            'applicable_subjects' => $applicableSubjects->count(),
            'filled_semesters' => $filledSemesters,
            'expected_semesters' => $expectedSemesters,
            'completion_percentage' => $completionPercentage,
            'final_subjects_count' => $finalSubjectsCount,
            'status' => $isComplete ? 'complete' : 'incomplete',
            'status_label' => $isComplete ? 'Lengkap' : 'Belum Lengkap',
            'status_class' => $isComplete ? 'success' : 'warning',
        ];
    }

    /**
     * @return array{
     *   student:Student,
     *   rows:Collection<int, array{
     *     subject:Subject,
     *     semesters:array<int, array{id:int|null, score:float|null}>,
     *     average:float,
     *     completion_percentage:int,
     *     is_complete:bool
     *   }>,
     *   summary:array{
     *     applicable_subjects:int,
     *     filled_semesters:int,
     *     expected_semesters:int,
     *     completion_percentage:int,
     *     status_label:string,
     *     status_class:string,
     *     final_average:float
     *   }
     * }
     */
    public function getStudentLedger(Student $student): array
    {
        $student->loadMissing(['school', 'major:id,name,code', 'grades']);
        $subjects = $this->filterApplicableSubjects($this->getSubjects(), $student);
        $gradeMap = $student->grades
            ->whereIn('semester', range(1, 6))
            ->groupBy('subject_id');

        $rows = $subjects->map(function (Subject $subject) use ($gradeMap): array {
            /** @var Collection<int, Grade> $subjectGrades */
            $subjectGrades = $gradeMap->get($subject->id, collect());
            $semesters = [];
            $scores = [];

            foreach (range(1, 6) as $semester) {
                /** @var Grade|null $grade */
                $grade = $subjectGrades->firstWhere('semester', $semester);
                $score = $grade instanceof Grade ? (float) $grade->score : null;
                $semesters[$semester] = [
                    'id' => $grade?->id,
                    'score' => $score,
                ];

                if ($score !== null) {
                    $scores[] = $score;
                }
            }

            $completionPercentage = (int) round((count($scores) / 6) * 100);

            return [
                'subject' => $subject,
                'semesters' => $semesters,
                'average' => $scores === [] ? 0.0 : round(array_sum($scores) / count($scores), 2),
                'completion_percentage' => $completionPercentage,
                'is_complete' => count($scores) === 6,
            ];
        })->values();

        $status = $this->buildStudentStatus($student, $subjects);
        $finalAverage = (float) round((float) $rows->avg('average'), 2);

        return [
            'student' => $student,
            'rows' => $rows,
            'summary' => [
                'applicable_subjects' => $status['applicable_subjects'],
                'filled_semesters' => $status['filled_semesters'],
                'expected_semesters' => $status['expected_semesters'],
                'completion_percentage' => $status['completion_percentage'],
                'status_label' => $status['status_label'],
                'status_class' => $status['status_class'],
                'final_average' => $rows->isEmpty() ? 0.0 : $finalAverage,
            ],
        ];
    }

    /**
     * @return array{
     *   success:bool,
     *   score:float|null,
     *   average:float,
     *   completion_percentage:int,
     *   is_complete:bool,
     *   overall:array{
     *     filled_semesters:int,
     *     expected_semesters:int,
     *     completion_percentage:int,
     *     status_label:string,
     *     status_class:string,
     *     final_average:float
     *   }
     * }
     */
    public function saveSemesterScore(Student $student, Subject $subject, int $semester, ?float $score): array
    {
        if ($semester < 1 || $semester > 6) {
            throw new InvalidArgumentException('Semester harus berada pada rentang 1 sampai 6.');
        }

        if ($score !== null && ($score < 0 || $score > 100)) {
            throw new InvalidArgumentException('Nilai harus berada pada rentang 0 sampai 100.');
        }

        $student->loadMissing(['school', 'major:id,name,code']);
        $subject->loadMissing('majors:id');

        if (!$this->isSubjectApplicableForStudent($subject, $student)) {
            throw new InvalidArgumentException('Mata pelajaran tidak berlaku untuk jurusan siswa ini.');
        }

        DB::transaction(function () use ($student, $subject, $semester, $score): void {
            $attributes = [
                'student_id' => $student->id,
                'subject_id' => $subject->id,
                'semester' => $semester,
            ];

            if ($score === null) {
                Grade::query()->where($attributes)->delete();

                return;
            }

            Grade::query()->updateOrCreate($attributes, [
                'score' => round($score, 2),
            ]);
        });

        $student->unsetRelation('grades');
        $ledger = $this->getStudentLedger($student->fresh(['school', 'major:id,name,code', 'grades']));
        $row = $ledger['rows']->first(
            static fn (array $item): bool => (int) $item['subject']->id === (int) $subject->id
        );

        if (!is_array($row)) {
            throw new InvalidArgumentException('Baris mata pelajaran tidak ditemukan setelah penyimpanan.');
        }

        return [
            'success' => true,
            'score' => $score === null ? null : round($score, 2),
            'average' => (float) $row['average'],
            'completion_percentage' => (int) $row['completion_percentage'],
            'is_complete' => (bool) $row['is_complete'],
            'overall' => $ledger['summary'],
        ];
    }

    /**
     * @return Collection<int, Subject>
     */
    public function getSubjects(): Collection
    {
        return Subject::query()
            ->with('majors:id')
            ->orderBy('name')
            ->get(['id', 'name', 'category']);
    }

    /**
     * @param Collection<int, Subject>|EloquentCollection<int, Subject> $subjects
     * @return Collection<int, Subject>
     */
    public function filterApplicableSubjects(Collection|EloquentCollection $subjects, Student $student): Collection
    {
        $isSmk = ($student->school?->tipe_sekolah === 'SMK');

        return collect($subjects)->filter(function (Subject $subject) use ($student, $isSmk): bool {
            if (!$isSmk) {
                return true;
            }

            $subjectMajorIds = $subject->majors
                ->pluck('id')
                ->map(static fn (mixed $id): int => (int) $id)
                ->all();

            if ($subjectMajorIds === []) {
                return true;
            }

            if ($student->major_id === null) {
                return false;
            }

            return in_array((int) $student->major_id, $subjectMajorIds, true);
        })->values();
    }

    public function isSubjectApplicableForStudent(Subject $subject, Student $student): bool
    {
        return $this->filterApplicableSubjects(collect([$subject]), $student)->isNotEmpty();
    }
}
