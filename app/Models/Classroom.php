<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classroom extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'grade',
        'department_id',
        'academic_year_id',
        'homeroom_teacher_id',
        'capacity',
        'room',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Grade constants
     */
    public const GRADE_X = 'X';
    public const GRADE_XI = 'XI';
    public const GRADE_XII = 'XII';

    public const GRADES = [
        self::GRADE_X => 'Kelas X',
        self::GRADE_XI => 'Kelas XI',
        self::GRADE_XII => 'Kelas XII',
    ];

    /**
     * Get the department
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the academic year
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Get the homeroom teacher (Wali Kelas)
     */
    public function homeroomTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'homeroom_teacher_id');
    }

    /**
     * Get students in this classroom
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    /**
     * Scope for active classrooms
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for current academic year
     */
    public function scopeCurrentYear($query)
    {
        $activeYear = AcademicYear::getActive();
        if ($activeYear) {
            return $query->where('academic_year_id', $activeYear->id);
        }
        return $query;
    }

    /**
     * Get full display name
     */
    public function getDisplayNameAttribute(): string
    {
        return "{$this->grade} {$this->department?->code} - {$this->name}";
    }
}
