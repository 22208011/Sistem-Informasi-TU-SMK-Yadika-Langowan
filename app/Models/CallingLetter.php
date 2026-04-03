<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CallingLetter extends Model
{
    protected $fillable = [
        'letter_number',
        'type',
        'subject',
        'content',
        'letter_date',
        'meeting_date',
        'meeting_time',
        'meeting_place',
        'academic_year_id',
        'created_by',
        'status',
        'notes',
    ];

    protected $casts = [
        'letter_date' => 'date',
        'meeting_date' => 'date',
        'meeting_time' => 'datetime:H:i',
    ];

    public const TYPES = [
        'SP1' => 'Surat Panggilan 1',
        'SP2' => 'Surat Panggilan 2',
        'SP3' => 'Surat Panggilan 3',
    ];

    public const STATUSES = [
        'draft' => 'Draft',
        'sent' => 'Terkirim',
        'completed' => 'Selesai',
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function callingLetterStudents(): HasMany
    {
        return $this->hasMany(CallingLetterStudent::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'calling_letter_students')
            ->withPivot(['attendance_status', 'reason', 'result'])
            ->withTimestamps();
    }

    public static function generateLetterNumber(string $type): string
    {
        $year = now()->year;
        $month = str_pad(now()->month, 2, '0', STR_PAD_LEFT);
        $count = self::whereYear('created_at', $year)->count() + 1;
        $number = str_pad($count, 3, '0', STR_PAD_LEFT);
        
        return "{$number}/{$type}/SMK-YL/{$month}/{$year}";
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function getTypeNameAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getStatusNameAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
