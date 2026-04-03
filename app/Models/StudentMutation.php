<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentMutation extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'type',
        'mutation_date',
        'effective_date',
        'reason',
        'previous_school',
        'destination_school',
        'previous_classroom_id',
        'new_classroom_id',
        'document_number',
        'notes',
        'status',
        'approved_by',
        'approved_at',
        'academic_year_id',
    ];

    protected function casts(): array
    {
        return [
            'mutation_date' => 'date',
            'effective_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    /**
     * Mutation Type Constants
     */
    public const TYPE_MASUK = 'masuk';

    public const TYPE_KELUAR = 'keluar';

    public const TYPE_PINDAH_KELAS = 'pindah_kelas';

    public const TYPE_NAIK_KELAS = 'naik_kelas';

    public const TYPE_LULUS = 'lulus';

    public const TYPE_DO = 'do';

    public const TYPES = [
        self::TYPE_MASUK => 'Masuk (Siswa Baru/Pindahan)',
        self::TYPE_KELUAR => 'Keluar (Pindah Sekolah)',
        self::TYPE_PINDAH_KELAS => 'Pindah Kelas',
        self::TYPE_NAIK_KELAS => 'Naik Kelas',
        self::TYPE_LULUS => 'Lulus',
        self::TYPE_DO => 'Dikeluarkan (DO)',
    ];

    public const TYPE_COLORS = [
        self::TYPE_MASUK => 'green',
        self::TYPE_KELUAR => 'yellow',
        self::TYPE_PINDAH_KELAS => 'blue',
        self::TYPE_NAIK_KELAS => 'cyan',
        self::TYPE_LULUS => 'indigo',
        self::TYPE_DO => 'red',
    ];

    /**
     * Status Constants
     */
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [
        self::STATUS_PENDING => 'Menunggu Persetujuan',
        self::STATUS_APPROVED => 'Disetujui',
        self::STATUS_REJECTED => 'Ditolak',
    ];

    public const STATUS_COLORS = [
        self::STATUS_PENDING => 'yellow',
        self::STATUS_APPROVED => 'green',
        self::STATUS_REJECTED => 'red',
    ];

    /**
     * Entry Type for new students
     */
    public const ENTRY_TYPE_NEW = 'baru';

    public const ENTRY_TYPE_TRANSFER = 'pindahan';

    public const ENTRY_TYPES = [
        self::ENTRY_TYPE_NEW => 'Siswa Baru',
        self::ENTRY_TYPE_TRANSFER => 'Pindahan',
    ];

    /**
     * Exit Reason Constants
     */
    public const EXIT_REASONS = [
        'pindah_sekolah' => 'Pindah Sekolah',
        'orang_tua' => 'Permintaan Orang Tua',
        'kesehatan' => 'Alasan Kesehatan',
        'ekonomi' => 'Alasan Ekonomi',
        'pelanggaran' => 'Pelanggaran Tata Tertib',
        'lainnya' => 'Lainnya',
    ];

    /**
     * Get the student
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the previous classroom
     */
    public function previousClassroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'previous_classroom_id');
    }

    /**
     * Get the new classroom
     */
    public function newClassroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'new_classroom_id');
    }

    /**
     * Get the academic year
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Get the user who approved
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scope for pending mutations
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for approved mutations
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope for specific type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for entry mutations (masuk)
     */
    public function scopeEntry($query)
    {
        return $query->where('type', self::TYPE_MASUK);
    }

    /**
     * Scope for exit mutations (keluar/pindah/do)
     */
    public function scopeExit($query)
    {
        return $query->whereIn('type', [self::TYPE_KELUAR, self::TYPE_DO, self::TYPE_LULUS]);
    }

    /**
     * Get formatted date
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->mutation_date?->format('d M Y') ?? '-';
    }

    /**
     * Check if mutation is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if mutation is approved
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }
}
