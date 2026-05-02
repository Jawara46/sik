<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaSession extends Model
{
    protected $fillable = [
        'school_id',
        'session_id',
        'status',
        'qr_code',
    ];

    /**
     * Get the school that owns the WA session.
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
