<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GraduationDocumentLog extends Model
{
    protected $fillable = [
        'graduation_document_id',
        'student_id',
        'admin_user_id',
        'actor_type',
        'action',
        'ip_address',
        'user_agent',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    /**
     * @return BelongsTo<GraduationDocument, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(GraduationDocument::class, 'graduation_document_id');
    }

    /**
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }
}
