<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GraduationDocument;
use App\Models\School;
use App\Models\Student;

class DocumentReadinessService
{
    public function __construct(
        private readonly GradeRecapService $gradeRecapService,
        private readonly GraduationStatusService $graduationStatusService,
    ) {
    }

    /**
     * @return array{
     *   student_status:string,
     *   can_download:bool,
     *   access_locked:bool,
     *   documents:array{
     *     skl:array<string,mixed>,
     *     transcript:array<string,mixed>
     *   }
     * }
     */
    public function assess(Student $student): array
    {
        $student->loadMissing(['school', 'major', 'grades.subject', 'graduationDocuments']);

        return [
            'student_status' => (string) ($student->status ?? 'Pending'),
            'can_download' => $this->graduationStatusService->canDownloadDocument($student),
            'access_locked' => (bool) ($student->access_locked ?? false),
            'documents' => [
                'skl' => $this->assessSkl($student),
                'transcript' => $this->assessTranscript($student),
            ],
        ];
    }

    /**
     * @return array{
     *   ready:bool,
     *   warnings:array<int,string>,
     *   blocking_errors:array<int,string>,
     *   document_exists:bool,
     *   document_status:?string
     * }
     */
    private function assessSkl(Student $student): array
    {
        $warnings = [];
        $blockingErrors = [];
        $school = $student->school;

        $this->assertSchoolCoreData($school, $blockingErrors);

        if (($student->status ?? 'Pending') !== 'Lulus') {
            $blockingErrors[] = 'Status siswa harus "Lulus" sebelum SKL dapat dipublish.';
        }

        if (!is_string($student->tempat_lahir) || trim($student->tempat_lahir) === '') {
            $blockingErrors[] = 'Tempat lahir siswa belum diisi.';
        }

        if ($student->tanggal_lahir === null) {
            $blockingErrors[] = 'Tanggal lahir siswa belum diisi.';
        }

        if (!is_string($student->nama_orang_tua) || trim($student->nama_orang_tua) === '') {
            $warnings[] = 'Nama orang tua/wali belum diisi.';
        }

        if (($school?->show_student_photo_on_skl ?? false) && (!is_string($student->photo) || trim($student->photo) === '')) {
            $blockingErrors[] = 'Pas foto siswa wajib diunggah karena opsi foto pada SKL sedang aktif.';
        }

        if (!$this->graduationStatusService->canDownloadDocument($student)) {
            $warnings[] = 'Akses unduh siswa masih terkunci oleh status administrasi atau status kelulusan belum final.';
        }

        if (!$school instanceof School || (!is_string($school->kop_surat) || trim($school->kop_surat) === '')) {
            $warnings[] = 'Kop surat sekolah belum diunggah. Dokumen akan memakai header fallback.';
        }

        return $this->buildDocumentState($student, GraduationDocumentType::SKL, $warnings, $blockingErrors);
    }

    /**
     * @return array{
     *   ready:bool,
     *   warnings:array<int,string>,
     *   blocking_errors:array<int,string>,
     *   document_exists:bool,
     *   document_status:?string
     * }
     */
    private function assessTranscript(Student $student): array
    {
        $warnings = [];
        $blockingErrors = [];
        $school = $student->school;

        $this->assertSchoolCoreData($school, $blockingErrors);

        if (($student->status ?? 'Pending') === 'Pending') {
            $blockingErrors[] = 'Status kelulusan siswa masih Pending sehingga transkrip belum boleh dipublish.';
        }

        if ($this->gradeRecapService->getFinalGrades($student)->isEmpty()) {
            $blockingErrors[] = 'Belum ada nilai akhir yang dapat dipakai untuk transkrip.';
        }

        if ($student->major_id === null && ($school?->tipe_sekolah === 'SMK')) {
            $warnings[] = 'Jurusan siswa belum dipilih. Mapping mapel SMK berpotensi tidak akurat.';
        }

        if (!$this->graduationStatusService->canDownloadDocument($student)) {
            $warnings[] = 'Akses unduh siswa masih terkunci oleh status administrasi atau status kelulusan belum final.';
        }

        if (!$school instanceof School || (!is_string($school->kop_surat) || trim($school->kop_surat) === '')) {
            $warnings[] = 'Kop surat sekolah belum diunggah. Dokumen akan memakai header fallback.';
        }

        return $this->buildDocumentState($student, GraduationDocumentType::TRANSCRIPT, $warnings, $blockingErrors);
    }

    /**
     * @param array<int, string> $blockingErrors
     */
    private function assertSchoolCoreData(?School $school, array &$blockingErrors): void
    {
        if (!$school instanceof School) {
            $blockingErrors[] = 'Profil sekolah belum tersedia.';

            return;
        }

        if (!is_string($school->nama_sekolah) || trim($school->nama_sekolah) === '') {
            $blockingErrors[] = 'Nama sekolah belum diisi.';
        }

        if (!is_string($school->npsn) || trim($school->npsn) === '') {
            $blockingErrors[] = 'NPSN sekolah belum diisi.';
        }

        if (!is_string($school->nama_kepsek) || trim($school->nama_kepsek) === '') {
            $blockingErrors[] = 'Nama kepala sekolah belum diisi.';
        }

        if (!is_string($school->ttd_kepsek) || trim($school->ttd_kepsek) === '') {
            $blockingErrors[] = 'Tanda tangan kepala sekolah belum diunggah.';
        }

        if ((bool) ($school->use_digital_stamp ?? false) && (!is_string($school->stempel_sekolah) || trim($school->stempel_sekolah) === '')) {
            $blockingErrors[] = 'Stempel sekolah belum diunggah padahal opsi digital stempel aktif.';
        }

        if (!is_string($school->tempat_surat) || trim($school->tempat_surat) === '') {
            $blockingErrors[] = 'Tempat surat belum diisi.';
        }

        if ($school->tanggal_surat === null) {
            $blockingErrors[] = 'Tanggal surat belum diisi.';
        }
    }

    /**
     * @param array<int, string> $warnings
     * @param array<int, string> $blockingErrors
     * @return array{
     *   ready:bool,
     *   warnings:array<int,string>,
     *   blocking_errors:array<int,string>,
     *   document_exists:bool,
     *   document_status:?string
     * }
     */
    private function buildDocumentState(Student $student, string $documentType, array $warnings, array $blockingErrors): array
    {
        $document = $student->graduationDocuments
            ->firstWhere('document_type', $documentType);

        return [
            'ready' => $blockingErrors === [],
            'warnings' => array_values(array_unique($warnings)),
            'blocking_errors' => array_values(array_unique($blockingErrors)),
            'document_exists' => $document instanceof GraduationDocument,
            'document_status' => $document?->status,
        ];
    }
}
