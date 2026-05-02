<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\School;
use App\Models\Student;

class WaAutoRespondLog extends Model
{
    protected $fillable = [
        'school_id',
        'sender_number',
        'sender_name',
        'nisn_queried',
        'student_id',
        'request_message',
        'response_message',
        'status',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
