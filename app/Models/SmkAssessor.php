<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmkAssessor extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'major_id',
        'internal_name',
        'internal_nip',
        'external_name',
        'external_company',
        'external_position',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function major(): BelongsTo
    {
        return $this->belongsTo(Major::class);
    }
}
