<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallingLetterStudent extends Model
{
    protected $fillable = [
        'calling_letter_id',
        'student_id',
        'attendance_status',
        'reason',
        'result',
    ];

    public const ATTENDANCE_STATUSES = [
        'pending' => 'Menunggu',
        'attended' => 'Hadir',
        'not_attended' => 'Tidak Hadir',
    ];

    public function callingLetter(): BelongsTo
    {
        return $this->belongsTo(CallingLetter::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function getAttendanceStatusNameAttribute(): string
    {
        return self::ATTENDANCE_STATUSES[$this->attendance_status] ?? $this->attendance_status;
    }
}
