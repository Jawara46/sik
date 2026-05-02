<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'subject_type',
        'subject_id',
        'subject_name',
        'event',
        'description',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Shortcut to log an activity
     */
    public static function record(
        string $event,
        string $description,
        string $subjectName,
        ?Model $subject = null,
        ?string $ip = null,
        ?string $ua = null
    ): void {
        static::create([
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id'   => $subject?->id,
            'subject_name' => $subjectName,
            'event'        => $event,
            'description'  => $description,
            'ip_address'   => $ip,
            'user_agent'   => $ua,
        ]);
    }
}
