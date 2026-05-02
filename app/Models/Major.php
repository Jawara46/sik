<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\SmkUnit;

class Major extends Model
{
    protected $fillable = [
        'school_id',
        'name',
        'code',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'major_subject')
            ->withTimestamps();
    }

    public function smkUnits(): HasMany
    {
        return $this->hasMany(SmkUnit::class);
    }

    public function smkAssessor(): HasOne
    {
        return $this->hasOne(SmkAssessor::class);
    }
}

