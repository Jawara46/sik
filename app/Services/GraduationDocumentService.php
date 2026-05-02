<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GraduationDocument;
use App\Models\Student;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class GraduationDocumentService
{
    public function __construct(
        private readonly SklSnapshotService $sklSnapshotService,
        private readonly TranscriptSnapshotService $transcriptSnapshotService,
        private readonly DocumentReadinessService $documentReadinessService,
        private readonly DocumentNumberService $documentNumberService,
    ) {
    }

    public function syncDraft(Student $student, string $documentType, ?User $admin = null): GraduationDocument
    {
        $student->loadMissing('school');

        $payload = match ($documentType) {
            GraduationDocumentType::SKL => $this->sklSnapshotService->buildPayload($student),
            GraduationDocumentType::TRANSCRIPT => $this->transcriptSnapshotService->buildPayload($student),
            default => throw new \InvalidArgumentException('Unsupported graduation document type: ' . $documentType),
        };

        $snapshotHash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));

        /** @var GraduationDocument $document */
        $document = GraduationDocument::query()->firstOrNew([
            'student_id' => $student->id,
            'document_type' => $documentType,
        ]);

        $isNew = !$document->exists;
        $currentHash = is_string($document->pdf_hash) ? $document->pdf_hash : null;

        $document->school_id = (int) $student->school_id;
        $document->snapshot_payload = $payload;
        $document->verification_token = $document->verification_token ?: (string) Str::uuid();
        $document->status = $document->status ?: 'draft';

        if ($isNew) {
            $document->created_by = $admin?->id;
        }

        $document->updated_by = $admin?->id;

        if ($currentHash !== $snapshotHash) {
            $document->pdf_hash = $snapshotHash;
            $document->pdf_path = null;
            $document->generated_at = null;
        }

        $document->save();

        return $document->refresh();
    }

    public function findForStudent(Student $student, string $documentType): ?GraduationDocument
    {
        return GraduationDocument::query()
            ->where('student_id', $student->id)
            ->where('document_type', $documentType)
            ->first();
    }

    public function publish(Student $student, string $documentType, ?User $admin = null): GraduationDocument
    {
        $document = $this->syncDraft($student, $documentType, $admin);
        $readiness = $this->documentReadinessService->assess($student);
        $state = match ($documentType) {
            GraduationDocumentType::SKL => $readiness['documents']['skl'] ?? [],
            GraduationDocumentType::TRANSCRIPT => $readiness['documents']['transcript'] ?? [],
            default => [],
        };

        $blockingErrors = $state['blocking_errors'] ?? [];
        if ($blockingErrors !== []) {
            throw ValidationException::withMessages([
                'document' => $blockingErrors,
            ]);
        }

        $documentMeta = is_array($document->snapshot_payload)
            ? (array) data_get($document->snapshot_payload, 'document', [])
            : [];

        $issuedAt = !empty($documentMeta['issued_date'])
            ? CarbonImmutable::parse((string) $documentMeta['issued_date'])->startOfDay()
            : CarbonImmutable::now();

        $document->forceFill([
            'document_number' => $this->documentNumberService->generate($document),
            'status' => 'published',
            'issued_at' => $issuedAt,
            'published_at' => now(),
            'updated_by' => $admin?->id,
        ])->save();

        return $document->refresh();
    }

    public function revoke(GraduationDocument $document, ?User $admin = null): GraduationDocument
    {
        $document->forceFill([
            'status' => 'revoked',
            'updated_by' => $admin?->id,
        ])->save();

        return $document->refresh();
    }
}
