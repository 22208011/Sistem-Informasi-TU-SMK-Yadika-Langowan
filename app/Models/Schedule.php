<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject_id',
        'classroom_id',
        'teacher_id',
        'academic_year_id',
        'semester',
        'day_of_week',
        'start_time',
        'end_time',
        'room',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'semester' => 'integer',
            'day_of_week' => 'integer',
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Day of Week Constants (0 = Sunday, 1 = Monday, etc.)
     */
    public const DAY_SUNDAY = 0;
    public const DAY_MONDAY = 1;
    public const DAY_TUESDAY = 2;
    public const DAY_WEDNESDAY = 3;
    public const DAY_THURSDAY = 4;
    public const DAY_FRIDAY = 5;
    public const DAY_SATURDAY = 6;

    public const DAYS = [
        self::DAY_SUNDAY => 'Minggu',
        self::DAY_MONDAY => 'Senin',
        self::DAY_TUESDAY => 'Selasa',
        self::DAY_WEDNESDAY => 'Rabu',
        self::DAY_THURSDAY => 'Kamis',
        self::DAY_FRIDAY => 'Jumat',
        self::DAY_SATURDAY => 'Sabtu',
    ];

    public const SCHOOL_DAYS = [
        self::DAY_MONDAY => 'Senin',
        self::DAY_TUESDAY => 'Selasa',
        self::DAY_WEDNESDAY => 'Rabu',
        self::DAY_THURSDAY => 'Kamis',
        self::DAY_FRIDAY => 'Jumat',
        self::DAY_SATURDAY => 'Sabtu',
    ];

    /**
     * Get the subject
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Get the classroom
     */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    /**
     * Get the teacher
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'teacher_id');
    }

    /**
     * Get the academic year
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Scope for active schedules
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for day of week
     */
    public function scopeForDay($query, int $day)
    {
        return $query->where('day_of_week', $day);
    }

    /**
     * Scope for today
     */
    public function scopeToday($query)
    {
        return $query->where('day_of_week', now()->dayOfWeek);
    }

    /**
     * Scope for teacher
     */
    public function scopeForTeacher($query, int $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    /**
     * Scope for classroom
     */
    public function scopeForClassroom($query, int $classroomId)
    {
        return $query->where('classroom_id', $classroomId);
    }

    /**
     * Get day name
     */
    public function getDayNameAttribute(): string
    {
        return self::DAYS[$this->day_of_week] ?? '';
    }

    /**
     * Get time range display
     */
    public function getTimeRangeAttribute(): string
    {
        return $this->start_time->format('H:i') . ' - ' . $this->end_time->format('H:i');
    }
}
