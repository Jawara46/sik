<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmkRecordUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'smk_record_id',
        'smk_unit_id',
        'score',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'float',
        ];
    }

    /**
     * @return BelongsTo<SmkRecord, $this>
     */
    public function smkRecord(): BelongsTo
    {
        return $this->belongsTo(SmkRecord::class);
    }

    /**
     * @return BelongsTo<SmkUnit, $this>
     */
    public function smkUnit(): BelongsTo
    {
        return $this->belongsTo(SmkUnit::class);
    }
}
