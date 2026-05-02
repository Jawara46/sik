<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    protected $fillable = [
        'name',
        'category',
    ];

    /**
     * Get the grades for the subject.
     */
    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }

    /**
     * Majors mapped to this subject (SMK).
     */
    public function majors(): BelongsToMany
    {
        return $this->belongsToMany(Major::class, 'major_subject')
            ->withTimestamps();
    }
}
