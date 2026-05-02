<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GraduationDocument;
use App\Models\School;
use Carbon\CarbonImmutable;

class DocumentNumberService
{
    public function generate(GraduationDocument $document): string
    {
        if (is_string($document->document_number) && trim($document->document_number) !== '') {
            return $document->document_number;
        }

        $year = CarbonImmutable::parse($document->issued_at ?? now())->format('Y');
        $code = match ($document->document_type) {
            GraduationDocumentType::SKL => 'SKL',
            GraduationDocumentType::TRANSCRIPT => 'TRS',
            default => 'DOC',
        };

        $document->loadMissing('school');
        $school = $document->school;
        $pattern = $this->resolvePattern($document->document_type, $school);
        $mode = $this->resolveMode($document->document_type, $school);

        $replacements = [
            '{YEAR}' => $year,
            '{NPSN}' => (string) ($school?->npsn ?? ''),
            '{TYPE}' => $code,
        ];

        if ($mode !== 'static') {
            $replacements['{NO}'] = str_pad((string) $document->id, 3, '0', STR_PAD_LEFT);
        }

        return strtr($pattern, $replacements);
    }

    private function resolvePattern(string $documentType, ?School $school): string
    {
        $configured = match ($documentType) {
            GraduationDocumentType::SKL => $school?->skl_number_pattern,
            GraduationDocumentType::TRANSCRIPT => $school?->transcript_number_pattern,
            default => null,
        };

        if (is_string($configured) && trim($configured) !== '') {
            return $configured;
        }

        return match ($documentType) {
            GraduationDocumentType::SKL => '421.5/SKL/{YEAR}/{NO}',
            GraduationDocumentType::TRANSCRIPT => '421.5/TRS/{YEAR}/{NO}',
            default => '421.5/DOC/{YEAR}/{NO}',
        };
    }

    private function resolveMode(string $documentType, ?School $school): string
    {
        return match ($documentType) {
            GraduationDocumentType::SKL => $school?->skl_number_mode ?? 'dynamic',
            GraduationDocumentType::TRANSCRIPT => $school?->transcript_number_mode ?? 'dynamic',
            default => 'dynamic',
        };
    }
}
