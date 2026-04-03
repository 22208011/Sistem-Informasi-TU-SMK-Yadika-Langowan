<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'subject_id',
        'classroom_id',
        'academic_year_id',
        'teacher_id',
        'exam_type',
        'semester',
        'exam_date',
        'start_time',
        'end_time',
        'duration_minutes',
        'max_score',
        'passing_score',
        'description',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'exam_date' => 'date',
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'duration_minutes' => 'integer',
            'max_score' => 'decimal:2',
            'passing_score' => 'decimal:2',
        ];
    }

    /**
     * Exam Type Constants
     */
    public const TYPE_DAILY = 'daily';           // Ulangan Harian
    public const TYPE_MIDTERM = 'midterm';       // PTS
    public const TYPE_FINAL = 'final';           // PAS/UAS
    public const TYPE_SCHOOL = 'school';         // Ujian Sekolah
    public const TYPE_PRACTICAL = 'practical';    // Ujian Praktik
    public const TYPE_NATIONAL = 'national';      // UN/ANBK

    public const TYPES = [
        self::TYPE_DAILY => 'Ulangan Harian',
        self::TYPE_MIDTERM => 'PTS (Penilaian Tengah Semester)',
        self::TYPE_FINAL => 'PAS/UAS (Penilaian Akhir Semester)',
        self::TYPE_SCHOOL => 'Ujian Sekolah',
        self::TYPE_PRACTICAL => 'Ujian Praktik',
        self::TYPE_NATIONAL => 'ANBK/Ujian Nasional',
    ];

    /**
     * Status Constants
     */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_ONGOING = 'ongoing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_GRADED = 'graded';

    public const STATUSES = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_SCHEDULED => 'Terjadwal',
        self::STATUS_ONGOING => 'Sedang Berlangsung',
        self::STATUS_COMPLETED => 'Selesai',
        self::STATUS_GRADED => 'Sudah Dinilai',
    ];

    public const STATUS_COLORS = [
        self::STATUS_DRAFT => 'zinc',
        self::STATUS_SCHEDULED => 'blue',
        self::STATUS_ONGOING => 'yellow',
        self::STATUS_COMPLETED => 'green',
        self::STATUS_GRADED => 'purple',
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
     * Get the academic year
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Get the teacher
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'teacher_id');
    }

    /**
     * Get exam scores
     */
    public function scores(): HasMany
    {
        return $this->hasMany(ExamScore::class);
    }

    /**
     * Scope for exam type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('exam_type', $type);
    }

    /**
     * Scope for status
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for upcoming exams
     */
    public function scopeUpcoming($query)
    {
        return $query->where('exam_date', '>=', now()->toDateString())
                     ->where('status', self::STATUS_SCHEDULED)
                     ->orderBy('exam_date');
    }

    /**
     * Check if exam is editable
     */
    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SCHEDULED]);
    }
}
