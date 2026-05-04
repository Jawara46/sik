<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Student;

class SklSnapshotService
{
    public function __construct(
        private readonly DocumentTemplateService $documentTemplateService,
        private readonly GradeRecapService $gradeRecapService,
    ) {
    }

    public function buildPayload(Student $student): array
    {
        $student->loadMissing(['school', 'major']);
        $school = $student->school;
        $template = $school !== null
            ? $this->documentTemplateService->forSchool($school, DocumentTemplateService::TYPE_SKL)
            : null;
        $templateSections = $template !== null
            ? $this->documentTemplateService->renderSections(
                $template,
                $this->documentTemplateService->previewVariables($school, DocumentTemplateService::TYPE_SKL, $student)
            )
            : [
                'title_html' => '',
                'intro_html' => '',
                'body_html' => '',
                'closing_html' => '',
            ];

        $showGradesOnSkl = (bool) ($school?->show_grades_on_skl ?? false);
        $subjects = [];
        $summary = null;

        if ($showGradesOnSkl) {
            $student->loadMissing(['grades.subject']);
            $subjects = $this->gradeRecapService->getFinalGrades($student)
                ->map(function ($item) {
                    $item['final_score'] = $item['score'];
                    return $item;
                })
                ->toArray();
            $summary = $this->gradeRecapService->getTranscriptSummary($student);
        }

        return [
            'student' => [
                'nisn' => $student->nisn,
                'name' => $student->name,
                'tempat_lahir' => $student->tempat_lahir,
                'tanggal_lahir' => optional($student->tanggal_lahir)->format('Y-m-d'),
                'nama_orang_tua' => $student->nama_orang_tua,
                'major_name' => $student->major?->name,
                'major_code' => $student->major?->code,
                'photo_path' => $student->photo,
            ],
            'school' => [
                'nama_sekolah' => $school?->nama_sekolah ?? $school?->name,
                'npsn' => $school?->npsn,
                'alamat_sekolah' => $school?->alamat_sekolah,
                'telepon_sekolah' => $school?->telepon_sekolah,
                'email_sekolah' => $school?->email_sekolah,
                'web_sekolah' => $school?->web_sekolah,
                'kop_surat' => $school?->kop_surat,
                'logo' => $school?->logo,
                'nama_kepsek' => $school?->nama_kepsek,
                'nip_kepsek' => $school?->nip_kepsek,
                'ttd_kepsek' => $school?->ttd_kepsek,
                'stempel_sekolah' => $school?->stempel_sekolah,
            ],
            'document' => [
                'document_type' => GraduationDocumentType::SKL,
                'graduation_status' => $student->status,
                'administration_status' => (bool) ($student->status_administrasi ?? false),
                'access_locked' => (bool) ($student->access_locked ?? false),
                'issued_place' => $school?->tempat_surat,
                'issued_date' => optional($school?->tanggal_surat)->format('Y-m-d'),
                'show_photo' => (bool) ($school?->show_student_photo_on_skl ?? false),
                'show_grades' => $showGradesOnSkl,
                'show_signature' => is_string($school?->ttd_kepsek) && trim((string) $school?->ttd_kepsek) !== '',
                'show_stamp' => (bool) ($school?->use_digital_stamp ?? false),
                'use_letterhead' => is_string($school?->kop_surat) && trim((string) $school?->kop_surat) !== '',
            ],
            'subjects' => $subjects,
            'summary' => $summary,
            'template' => array_merge($templateSections, [
                'paper_size' => $template->paper_size ?? 'a4',
                'orientation' => $template->orientation ?? 'portrait',
            ]),
        ];
    }
}
