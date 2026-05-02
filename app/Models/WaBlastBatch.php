<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\School;
use App\Models\User;

class WaBlastBatch extends Model
{
    protected $fillable = [
        'school_id',
        'admin_user_id',
        'status',
        'total_count',
        'processed_count',
        'sent_count',
        'failed_count',
        'filters',
        'content',
    ];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }
}
