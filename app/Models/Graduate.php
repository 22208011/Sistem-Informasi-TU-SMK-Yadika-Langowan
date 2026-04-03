<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Graduate extends Model
{
    protected $fillable = [
        'student_id',
        'academic_year_id',
        'certificate_number',
        'skl_number',
        'graduation_date',
        'final_score',
        'graduation_status',
        'predicate',
        'achievements',
        'notes',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'graduation_date' => 'date',
        'final_score' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public const STATUSES = [
        'lulus' => 'Lulus',
        'tidak_lulus' => 'Tidak Lulus',
        'pending' => 'Menunggu Keputusan',
    ];

    public const PREDICATES = [
        'Sangat Baik' => 'Sangat Baik',
        'Baik' => 'Baik',
        'Cukup' => 'Cukup',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function graduationLetter(): HasOne
    {
        return $this->hasOne(GraduationLetter::class);
    }

    public static function generateSklNumber(): string
    {
        $year = now()->year;
        $count = self::whereYear('created_at', $year)->count() + 1;
        $number = str_pad($count, 4, '0', STR_PAD_LEFT);

        return "SKL-{$number}/SMK-YL/{$year}";
    }

    public static function generateCertificateNumber(): string
    {
        $year = now()->year;
        $count = self::whereYear('created_at', $year)
            ->whereNotNull('certificate_number')
            ->count() + 1;
        $number = str_pad($count, 4, '0', STR_PAD_LEFT);

        return "DN-09/Dd/{$number}/{$year}";
    }

    public function scopePassed($query)
    {
        return $query->where('graduation_status', 'lulus');
    }

    public function scopeNotPassed($query)
    {
        return $query->where('graduation_status', 'tidak_lulus');
    }

    public function scopePending($query)
    {
        return $query->where('graduation_status', 'pending');
    }

    public function getStatusNameAttribute(): string
    {
        return self::STATUSES[$this->graduation_status] ?? $this->graduation_status;
    }

    public function isPassed(): bool
    {
        return $this->graduation_status === 'lulus';
    }
}
