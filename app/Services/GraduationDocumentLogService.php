<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GraduationDocument;
use App\Models\GraduationDocumentLog;
use App\Models\User;

class GraduationDocumentLogService
{
    public function log(
        GraduationDocument $document,
        string $action,
        string $actorType,
        ?User $admin = null,
        ?array $meta = null,
    ): GraduationDocumentLog {
        return GraduationDocumentLog::query()->create([
            'graduation_document_id' => $document->id,
            'student_id' => $document->student_id,
            'admin_user_id' => $admin?->id,
            'actor_type' => $actorType,
            'action' => $action,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'meta' => $meta,
        ]);
    }
}
