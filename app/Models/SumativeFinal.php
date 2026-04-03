<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SumativeFinal extends Model
{
    protected $fillable = [
        'student_id',
        'subject_id',
        'academic_year_id',
        'classroom_id',
        'semester',
        'type',
        'score',
        'status',
        'submitted_by',
        'submitted_at',
        'verified_by',
        'verified_at',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'notes',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'submitted_at' => 'datetime',
        'verified_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public const EXAM_TYPES = [
        'PAS' => 'Penilaian Akhir Semester',
        'PAT' => 'Penilaian Akhir Tahun',
        'UAS' => 'Ujian Akhir Semester',
        'UKK' => 'Ujian Kenaikan Kelas',
    ];

    public const STATUSES = [
        'draft' => 'Draft',
        'submitted' => 'Diajukan',
        'verified' => 'Terverifikasi',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
    ];

    public const SEMESTERS = [
        1 => 'Semester 1 (Ganjil)',
        2 => 'Semester 2 (Genap)',
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

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(SumativeFinalHistory::class);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopeVerified($query)
    {
        return $query->where('status', 'verified');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeByExamType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeBySemester($query, int $semester)
    {
        return $query->where('semester', $semester);
    }

    public function getExamTypeNameAttribute(): string
    {
        return self::EXAM_TYPES[$this->type] ?? $this->type;
    }

    public function getStatusNameAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getSemesterNameAttribute(): string
    {
        return self::SEMESTERS[$this->semester] ?? "Semester {$this->semester}";
    }

    public function canBeSubmitted(): bool
    {
        return $this->status === 'draft';
    }

    public function canBeVerified(): bool
    {
        return $this->status === 'submitted';
    }

    public function canBeApproved(): bool
    {
        return $this->status === 'verified';
    }

    public function submit(int $userId): void
    {
        $this->update([
            'status' => 'submitted',
            'submitted_by' => $userId,
            'submitted_at' => now(),
        ]);

        $this->logHistory('submitted', $userId, 'Nilai diajukan untuk verifikasi');
    }

    public function verify(int $userId): void
    {
        $this->update([
            'status' => 'verified',
            'verified_by' => $userId,
            'verified_at' => now(),
        ]);

        $this->logHistory('verified', $userId, 'Nilai terverifikasi');
    }

    public function approve(int $userId): void
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);

        $this->logHistory('approved', $userId, 'Nilai disetujui');
    }

    public function reject(int $userId, string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);

        $this->logHistory('rejected', $userId, "Nilai ditolak: {$reason}");
    }

    protected function logHistory(string $action, int $userId, ?string $notes = null): void
    {
        $this->histories()->create([
            'action' => $action,
            'performed_by' => $userId,
            'notes' => $notes,
            'old_status' => $this->getOriginal('status'),
            'new_status' => $this->status,
        ]);
    }
}
