<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GraduationDocument extends Model
{
    protected $fillable = [
        'school_id',
        'student_id',
        'document_type',
        'document_number',
        'status',
        'issued_at',
        'published_at',
        'snapshot_payload',
        'verification_token',
        'pdf_path',
        'pdf_hash',
        'generated_at',
        'last_accessed_at',
        'created_by',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'published_at' => 'datetime',
            'generated_at' => 'datetime',
            'last_accessed_at' => 'datetime',
            'snapshot_payload' => 'array',
        ];
    }

    /**
     * @return BelongsTo<School, $this>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
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
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * @return HasMany<GraduationDocumentLog, $this>
     */
    public function logs(): HasMany
    {
        return $this->hasMany(GraduationDocumentLog::class);
    }
}
