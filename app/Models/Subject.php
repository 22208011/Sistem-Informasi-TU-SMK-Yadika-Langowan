<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'department_id',
        'grade_level',
        'credits',
        'minimum_score',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'credits' => 'integer',
            'minimum_score' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Grade Level Constants
     */
    public const GRADE_ALL = 'all';
    public const GRADE_X = 'X';
    public const GRADE_XI = 'XI';
    public const GRADE_XII = 'XII';

    public const GRADE_LEVELS = [
        self::GRADE_ALL => 'Semua Tingkat',
        self::GRADE_X => 'Kelas X',
        self::GRADE_XI => 'Kelas XI',
        self::GRADE_XII => 'Kelas XII',
    ];

    /**
     * Subject Type Constants
     */
    public const TYPE_GENERAL = 'general';
    public const TYPE_VOCATIONAL = 'vocational';
    public const TYPE_LOCAL = 'local';

    public const TYPES = [
        self::TYPE_GENERAL => 'Mata Pelajaran Umum',
        self::TYPE_VOCATIONAL => 'Mata Pelajaran Kejuruan',
        self::TYPE_LOCAL => 'Muatan Lokal',
    ];

    /**
     * Get the department this subject belongs to
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get teachers who teach this subject
     */
    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'subject_teacher')
            ->withPivot('academic_year_id', 'is_active')
            ->withTimestamps();
    }

    /**
     * Get grades for this subject
     */
    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }

    /**
     * Get exams for this subject
     */
    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

    /**
     * Get schedules for this subject
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    /**
     * Scope for active subjects
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for grade level
     */
    public function scopeForGrade($query, string $grade)
    {
        return $query->where(function ($q) use ($grade) {
            $q->where('grade_level', $grade)
              ->orWhere('grade_level', self::GRADE_ALL);
        });
    }

    /**
     * Get full display name with code
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->code} - {$this->name}";
    }
}
