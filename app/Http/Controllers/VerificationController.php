<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\SmkRecord;
use App\Services\GraduationDocumentLogService;
use App\Services\DocumentVerificationService;
use Illuminate\Contracts\View\View;

class VerificationController extends Controller
{
    public function __construct(
        private readonly DocumentVerificationService $documentVerificationService,
        private readonly GraduationDocumentLogService $graduationDocumentLogService,
    ) {
    }

    public function verifyDocument(string $token): View
    {
        $document = $this->documentVerificationService->findPublishedByToken($token);

        if ($document === null) {
            return view('verification.document-invalid', [
                'reference' => $token,
            ]);
        }

        $payload = is_array($document->snapshot_payload) ? $document->snapshot_payload : [];
        $this->graduationDocumentLogService->log(
            $document,
            'verify',
            'public',
            null,
            ['document_type' => $document->document_type]
        );

        return view('verification.document-valid', [
            'document' => $document,
            'student' => (array) data_get($payload, 'student', []),
            'school' => (array) data_get($payload, 'school', []),
            'documentMeta' => (array) data_get($payload, 'document', []),
            'summary' => (array) data_get($payload, 'summary', []),
            'schoolLogoUrl' => $this->resolveMediaUrl((string) data_get($payload, 'school.logo', '')),
            'letterheadUrl' => $this->resolveMediaUrl((string) data_get($payload, 'school.kop_surat', '')),
            'verificationUrl' => route('verification.document', $document->verification_token),
        ]);
    }

    public function verifyCertificate(string $nomorSertifikat): View
    {
        $nomor = urldecode($nomorSertifikat);
        $record = SmkRecord::with(['student.major'])->where('certificate_number', $nomor)->first();

        if ($record === null) {
            return view('verification.invalid', ['nomor' => $nomor]);
        }

        $school = School::query()->first();

        return view('verification.valid', [
            'record' => $record,
            'student' => $record->student,
            'major' => $record->student->major,
            'school' => $school,
        ]);
    }

    private function resolveMediaUrl(string $path): ?string
    {
        if ($path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, 'data:')) {
            return $path;
        }

        $normalized = ltrim($path, '/');

        if (str_starts_with($normalized, 'assets/') || str_starts_with($normalized, 'storage/')) {
            return asset($normalized);
        }

        return asset('storage/' . $normalized);
    }
}
