<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    protected $fillable = [
        'npsn',
        'name',
        'nama_sekolah',
        'email_sekolah',
        'telepon_sekolah',
        'web_sekolah',
        'alamat_sekolah',
        'tempat_surat',
        'tipe_sekolah',
        'nama_kepsek',
        'nip_kepsek',
        'tahun_pelajaran',
        'semester',
        'tanggal_surat',
        'logo',
        'kop_surat',
        'ttd_kepsek',
        'stempel_sekolah',
        'bg_countdown',
        'show_pkl_transcript',
        'show_student_photo_on_skl',
        'logo_path',
        'signature_path',
        'stamp_path',
        'use_digital_stamp',
        'skl_number_pattern',
        'skl_number_mode',
        'transcript_number_pattern',
        'transcript_number_mode',
        'certificate_number_pattern',
        'certificate_number_mode',
        'use_envelope_animation',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'tanggal_surat' => 'date',
            'show_pkl_transcript' => 'boolean',
            'show_student_photo_on_skl' => 'boolean',
            'use_digital_stamp' => 'boolean',
            'use_envelope_animation' => 'boolean',
        ];
    }

    /**
     * Get the students for the school.
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    /**
     * Get the majors for the school.
     */
    public function majors(): HasMany
    {
        return $this->hasMany(Major::class);
    }

    /**
     * Get the WA sessions for the school.
     */
    public function waSessions(): HasMany
    {
        return $this->hasMany(WaSession::class);
    }

    /**
     * Get the graduation documents for the school.
     */
    public function graduationDocuments(): HasMany
    {
        return $this->hasMany(GraduationDocument::class);
    }

    /**
     * Get the document templates for the school.
     */
    public function documentTemplates(): HasMany
    {
        return $this->hasMany(DocumentTemplate::class);
    }
}
