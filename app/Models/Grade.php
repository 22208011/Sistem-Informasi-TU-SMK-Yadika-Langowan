<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Grade extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'subject_id',
        'classroom_id',
        'academic_year_id',
        'teacher_id',
        'semester',
        'grade_type',
        'score',
        'description',
        'graded_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'graded_at' => 'datetime',
        ];
    }

    /**
     * Semester Constants
     */
    public const SEMESTER_GANJIL = 1;

    public const SEMESTER_GENAP = 2;

    public const SEMESTERS = [
        self::SEMESTER_GANJIL => 'Ganjil',
        self::SEMESTER_GENAP => 'Genap',
    ];

    /**
     * Grade Type Constants
     */
    public const TYPE_DAILY = 'daily';           // Nilai Harian

    public const TYPE_ASSIGNMENT = 'assignment';  // Tugas

    public const TYPE_QUIZ = 'quiz';             // Kuis

    public const TYPE_MIDTERM = 'midterm';       // PTS (Penilaian Tengah Semester)

    public const TYPE_FINAL = 'final';           // PAS/UAS (Penilaian Akhir Semester)

    public const TYPE_PRACTICAL = 'practical';    // Nilai Praktik

    public const TYPE_PROJECT = 'project';        // Nilai Proyek

    public const TYPES = [
        self::TYPE_DAILY => 'Nilai Harian',
        self::TYPE_ASSIGNMENT => 'Tugas',
        self::TYPE_QUIZ => 'Kuis',
        self::TYPE_MIDTERM => 'PTS (Penilaian Tengah Semester)',
        self::TYPE_FINAL => 'PAS/UAS (Penilaian Akhir Semester)',
        self::TYPE_PRACTICAL => 'Nilai Praktik',
        self::TYPE_PROJECT => 'Nilai Proyek',
    ];

    /**
     * Get the student
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

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
     * Get the academic year
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Get the teacher who graded
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'teacher_id');
    }

    /**
     * Scope for semester
     */
    public function scopeForSemester($query, int $semester)
    {
        return $query->where('semester', $semester);
    }

    /**
     * Scope for grade type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('grade_type', $type);
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
     * Get letter grade based on score
     */
    public function getLetterGradeAttribute(): string
    {
        if ($this->score >= 90) {
            return 'A';
        }
        if ($this->score >= 80) {
            return 'B';
        }
        if ($this->score >= 70) {
            return 'C';
        }
        if ($this->score >= 60) {
            return 'D';
        }

        return 'E';
    }

    /**
     * Get predicate based on score
     */
    public function getPredicateAttribute(): string
    {
        if ($this->score >= 90) {
            return 'Sangat Baik';
        }
        if ($this->score >= 80) {
            return 'Baik';
        }
        if ($this->score >= 70) {
            return 'Cukup';
        }
        if ($this->score >= 60) {
            return 'Kurang';
        }

        return 'Sangat Kurang';
    }
}
