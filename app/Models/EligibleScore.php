<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EligibleScore extends Model
{
    protected $fillable = [
        'student_id',
        'subject_id',
        'academic_year_id',
        'semester_1_score',
        'semester_2_score',
        'semester_3_score',
        'semester_4_score',
        'semester_5_score',
        'semester_6_score',
        'final_score',
        'eligible_score',
        'grade',
        'is_final',
        'notes',
    ];

    protected $casts = [
        'semester_1_score' => 'decimal:2',
        'semester_2_score' => 'decimal:2',
        'semester_3_score' => 'decimal:2',
        'semester_4_score' => 'decimal:2',
        'semester_5_score' => 'decimal:2',
        'semester_6_score' => 'decimal:2',
        'final_score' => 'decimal:2',
        'eligible_score' => 'decimal:2',
        'is_final' => 'boolean',
    ];

    public const GRADES = [
        'A' => ['min' => 90, 'max' => 100, 'label' => 'Sangat Baik'],
        'B' => ['min' => 80, 'max' => 89, 'label' => 'Baik'],
        'C' => ['min' => 70, 'max' => 79, 'label' => 'Cukup'],
        'D' => ['min' => 60, 'max' => 69, 'label' => 'Kurang'],
        'E' => ['min' => 0, 'max' => 59, 'label' => 'Sangat Kurang'],
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function calculateFinalScore(): float
    {
        $scores = array_filter([
            $this->semester_1_score,
            $this->semester_2_score,
            $this->semester_3_score,
            $this->semester_4_score,
            $this->semester_5_score,
            $this->semester_6_score,
        ], fn($score) => $score !== null);

        if (count($scores) === 0) {
            return 0;
        }

        return round(array_sum($scores) / count($scores), 2);
    }

    public function calculateEligibleScore(): float
    {
        // Rumus nilai eligible: 60% nilai rapor + 40% nilai ujian
        // Atau bisa disesuaikan sesuai kebijakan sekolah
        $finalScore = $this->final_score ?? $this->calculateFinalScore();
        
        // Untuk simplicity, eligible = final score
        // Bisa dimodifikasi untuk menambahkan bobot ujian dll
        return round($finalScore, 2);
    }

    public function calculateGrade(): string
    {
        $score = $this->eligible_score ?? $this->calculateEligibleScore();

        foreach (self::GRADES as $grade => $range) {
            if ($score >= $range['min'] && $score <= $range['max']) {
                return $grade;
            }
        }

        return 'E';
    }

    public function recalculate(): void
    {
        $this->final_score = $this->calculateFinalScore();
        $this->eligible_score = $this->calculateEligibleScore();
        $this->grade = $this->calculateGrade();
        $this->save();
    }

    public function scopeFinal($query)
    {
        return $query->where('is_final', true);
    }

    public function scopeByGrade($query, string $grade)
    {
        return $query->where('grade', $grade);
    }

    public function getGradeLabelAttribute(): string
    {
        return self::GRADES[$this->grade]['label'] ?? 'Tidak Diketahui';
    }

    public function getSemesterScoresAttribute(): array
    {
        return [
            1 => $this->semester_1_score,
            2 => $this->semester_2_score,
            3 => $this->semester_3_score,
            4 => $this->semester_4_score,
            5 => $this->semester_5_score,
            6 => $this->semester_6_score,
        ];
    }
}
