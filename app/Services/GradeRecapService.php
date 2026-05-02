<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\School;
use App\Models\Student;
use Illuminate\Support\Collection;

class GradeRecapService
{
    /**
     * @return Collection<int, array{id:int,subject_name:string,score:float,location:?string,notes:?string,category:string}>
     */
    public function getFinalGrades(Student $student): Collection
    {
        $student->loadMissing(['grades.subject', 'school']);
        $showPklTranscript = $this->shouldShowPklTranscript($student->school);

        return $student->grades
            ->filter(static function ($grade) use ($showPklTranscript): bool {
                if ($grade->subject === null) {
                    return false;
                }

                return $showPklTranscript || $grade->subject->category !== 'PKL';
            })
            ->whereIn('semester', range(1, 6))
            ->groupBy('subject_id')
            ->map(function (Collection $group): array {
                $first = $group->first();
                $subject = $first->subject;

                return [
                    'id' => (int) $first->id,
                    'subject_name' => (string) $subject->name,
                    'score' => round((float) $group->avg('score'), 2),
                    'location' => $group->pluck('location')->filter()->join(', '),
                    'notes' => $group->pluck('notes')->filter()->join(' | '),
                    'category' => (string) $subject->category,
                ];
            })
            ->sortBy('subject_name')
            ->values();
    }

    public function getGroupedGrades(Student $student): Collection
    {
        return $this->getFinalGrades($student)
            ->groupBy('category')
            ->map(static function (Collection $group): Collection {
                return $group->map(static function (array $grade): array {
                    unset($grade['category']);

                    return $grade;
                })->values();
            });
    }

    public function getAverageScore(Student $student): float
    {
        $finalGrades = $this->getFinalGrades($student);
        if ($finalGrades->isEmpty()) {
            return 0.0;
        }

        return round((float) $finalGrades->avg('score'), 2);
    }

    /**
     * @return array{
     *   show_pkl_transcript:bool,
     *   category_averages:array<string,float>,
     *   overall_average:float
     * }
     */
    public function getTranscriptSummary(Student $student): array
    {
        $student->loadMissing('school');
        $finalGrades = $this->getFinalGrades($student);
        $showPklTranscript = $this->shouldShowPklTranscript($student->school);

        $kelompokA = $finalGrades->whereIn('category', ['Umum', 'Muatan Nasional']);
        $kelompokB = $finalGrades->whereIn('category', ['Kewilayahan', 'C1', 'C2', 'C3', 'UKK']);
        $kelompokC = $finalGrades->where('category', 'PKL');

        return [
            'show_pkl_transcript' => $showPklTranscript,
            'category_averages' => [
                'kelompok_a' => $this->safeAverage($kelompokA),
                'kelompok_b' => $this->safeAverage($kelompokB),
                'kelompok_c' => $this->safeAverage($kelompokC),
            ],
            'overall_average' => $this->safeAverage($finalGrades),
        ];
    }

    private function safeAverage(Collection $grades): float
    {
        if ($grades->isEmpty()) {
            return 0.0;
        }

        return round((float) $grades->avg('score'), 2);
    }

    private function shouldShowPklTranscript(?School $school): bool
    {
        if (!$school instanceof School) {
            return true;
        }

        return (bool) ($school->show_pkl_transcript ?? true);
    }
}
