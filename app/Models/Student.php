<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Student extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'school_id',
        'major_id',
        'nisn',
        'nis',
        'name',
        'photo',
        'tempat_lahir',
        'tanggal_lahir',
        'nama_orang_tua',
        'nomor_wa',
        'password',
        'phone_number',
        'status',
        'status_administrasi',
        'access_locked',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'tanggal_lahir' => 'date',
            'status_administrasi' => 'boolean',
            'access_locked' => 'boolean',
        ];
    }

    /**
     * Normalize WhatsApp number to Indonesian international format.
     */
    protected function nomorWa(): Attribute
    {
        return Attribute::make(
            set: fn (mixed $value): ?string => $this->sanitizeWhatsAppNumber($value),
        );
    }

    /**
     * Keep legacy column sanitized as well.
     */
    protected function phoneNumber(): Attribute
    {
        return Attribute::make(
            set: fn (mixed $value): ?string => $this->sanitizeWhatsAppNumber($value),
        );
    }

    private function sanitizeWhatsAppNumber(mixed $value): ?string
    {
        if (!is_string($value) && !is_numeric($value)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', (string) $value);
        if (!is_string($digits) || $digits === '') {
            return null;
        }

        if (str_starts_with($digits, '08')) {
            return '628' . substr($digits, 2);
        }

        return $digits;
    }

    /**
     * Get the school that owns the student.
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the major that owns the student.
     */
    public function major(): BelongsTo
    {
        return $this->belongsTo(Major::class);
    }

    /**
     * Get the grades for the student.
     */
    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }

    /**
     * Get the SMK record for the student (specifically for SMK students).
     */
    public function smkRecord(): HasOne
    {
        return $this->hasOne(SmkRecord::class);
    }

    /**
     * Get the graduation documents for the student.
     */
    public function graduationDocuments(): HasMany
    {
        return $this->hasMany(GraduationDocument::class);
    }

    /**
     * Get the graduation document logs for the student.
     */
    public function graduationDocumentLogs(): HasMany
    {
        return $this->hasMany(GraduationDocumentLog::class);
    }
}
