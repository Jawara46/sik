<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmkRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'company_name',
        'pkl_score',
        'ukk_internal_score',
        'ukk_external_score',
        'ukk_final_score',
        'ukk_status',
        'certificate_number',
        'exam_date',
    ];

    protected function casts(): array
    {
        return [
            'pkl_score' => 'float',
            'ukk_internal_score' => 'float',
            'ukk_external_score' => 'float',
            'ukk_final_score' => 'float',
            'exam_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * @return HasMany<SmkRecordUnit, $this>
     */
    public function units(): HasMany
    {
        return $this->hasMany(SmkRecordUnit::class);
    }
}
