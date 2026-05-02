<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GraduationDocument;

class DocumentVerificationService
{
    public function findPublishedByToken(string $token): ?GraduationDocument
    {
        return GraduationDocument::query()
            ->with(['school', 'student'])
            ->where('verification_token', $token)
            ->where('status', 'published')
            ->first();
    }
}
