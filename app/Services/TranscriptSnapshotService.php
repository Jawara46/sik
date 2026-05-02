<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Student;

class TranscriptSnapshotService
{
    public function __construct(
        private readonly GradeRecapService $gradeRecapService,
        private readonly DocumentTemplateService $documentTemplateService,
    ) {
    }

    public function buildPayload(Student $student): array
    {
        $student->loadMissing(['school', 'major', 'grades.subject']);
        $school = $student->school;
        $summary = $this->gradeRecapService->getTranscriptSummary($student);
        $showPklTranscript = (bool) ($summary['show_pkl_transcript'] ?? true);
        $template = $school !== null
            ? $this->documentTemplateService->forSchool($school, DocumentTemplateService::TYPE_TRANSCRIPT)
            : null;
        $templateSections = $template !== null
            ? $this->documentTemplateService->renderSections(
                $template,
                $this->documentTemplateService->previewVariables($school, DocumentTemplateService::TYPE_TRANSCRIPT, $student)
            )
            : [
                'title_html' => '',
                'intro_html' => '',
                'body_html' => '',
                'closing_html' => '',
            ];

        $subjects = $student->grades
            ->whereIn('semester', range(1, 6))
            ->filter(static function ($grade) use ($showPklTranscript): bool {
                if ($grade->subject === null) {
                    return false;
                }

                return $showPklTranscript || $grade->subject->category !== 'PKL';
            })
            ->groupBy('subject_id')
            ->map(function ($group): array {
                $first = $group->first();

                return [
                    'subject_id' => $first->subject_id,
                    'subject_name' => $first->subject?->name,
                    'subject_code' => $first->subject?->code ?? null,
                    'category' => $first->subject?->category,
                    'semester_scores' => collect(range(1, 6))
                        ->mapWithKeys(static fn (int $semester): array => [
                            (string) $semester => optional($group->firstWhere('semester', $semester))->score,
                        ])
                        ->all(),
                    'final_score' => round((float) $group->avg('score'), 2),
                ];
            })
            ->sortBy('subject_name')
            ->values()
            ->all();

        return [
            'student' => [
                'nisn' => $student->nisn,
                'name' => $student->name,
                'tempat_lahir' => $student->tempat_lahir,
                'tanggal_lahir' => optional($student->tanggal_lahir)->format('Y-m-d'),
                'nama_orang_tua' => $student->nama_orang_tua,
                'major_name' => $student->major?->name,
                'major_code' => $student->major?->code,
            ],
            'school' => [
                'nama_sekolah' => $school?->nama_sekolah ?? $school?->name,
                'npsn' => $school?->npsn,
                'alamat_sekolah' => $school?->alamat_sekolah,
                'telepon_sekolah' => $school?->telepon_sekolah,
                'email_sekolah' => $school?->email_sekolah,
                'web_sekolah' => $school?->web_sekolah,
                'tahun_pelajaran' => $school?->tahun_pelajaran,
                'kop_surat' => $school?->kop_surat,
                'logo' => $school?->logo,
                'nama_kepsek' => $school?->nama_kepsek,
                'nip_kepsek' => $school?->nip_kepsek,
                'ttd_kepsek' => $school?->ttd_kepsek,
                'stempel_sekolah' => $school?->stempel_sekolah,
            ],
            'document' => [
                'document_type' => GraduationDocumentType::TRANSCRIPT,
                'graduation_status' => $student->status,
                'administration_status' => (bool) ($student->status_administrasi ?? false),
                'access_locked' => (bool) ($student->access_locked ?? false),
                'issued_place' => $school?->tempat_surat,
                'issued_date' => optional($school?->tanggal_surat)->format('Y-m-d'),
                'show_pkl_transcript' => $summary['show_pkl_transcript'],
                'show_signature' => is_string($school?->ttd_kepsek) && trim((string) $school?->ttd_kepsek) !== '',
                'show_stamp' => (bool) ($school?->use_digital_stamp ?? false),
                'use_letterhead' => is_string($school?->kop_surat) && trim((string) $school?->kop_surat) !== '',
            ],
            'subjects' => $subjects,
            'summary' => [
                'category_averages' => $summary['category_averages'],
                'overall_average' => $summary['overall_average'],
            ],
            'template' => $templateSections,
        ];
    }
}
