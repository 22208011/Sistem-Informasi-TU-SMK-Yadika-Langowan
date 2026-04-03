<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LetterRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_number',
        'letter_type',
        'student_id',
        'requested_by',
        'purpose',
        'notes',
        'attachment',
        'status',
        'processed_by',
        'admin_notes',
        'result_file',
        'processed_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'processed_at' => 'datetime',
            'completed_at' => 'datetime',
            'attachment' => 'array',
        ];
    }

    /**
     * Letter Types that students can request
     */
    public const TYPE_ACTIVE_STUDENT = 'active_student';
    public const TYPE_INTERNSHIP = 'internship';
    public const TYPE_GOOD_BEHAVIOR = 'good_behavior';
    public const TYPE_TRANSFER = 'transfer';

    public const TYPES = [
        self::TYPE_ACTIVE_STUDENT => 'Surat Keterangan Aktif',
        self::TYPE_INTERNSHIP => 'Surat Izin PKL',
        self::TYPE_GOOD_BEHAVIOR => 'SKBB (Surat Keterangan Berkelakuan Baik)',
        self::TYPE_TRANSFER => 'Surat Mutasi',
    ];

    public const TYPE_DESCRIPTIONS = [
        self::TYPE_ACTIVE_STUDENT => 'Surat keterangan sebagai siswa aktif di sekolah',
        self::TYPE_INTERNSHIP => 'Surat izin untuk Praktek Kerja Lapangan (PKL)',
        self::TYPE_GOOD_BEHAVIOR => 'Surat keterangan berkelakuan baik dari sekolah',
        self::TYPE_TRANSFER => 'Surat keterangan pindah/mutasi ke sekolah lain',
    ];

    public const TYPE_REQUIREMENTS = [
        self::TYPE_ACTIVE_STUDENT => [
            'Mengisi formulir permohonan di TU.',
            'Fotokopi kartu pelajar.',
            'Keperluan surat (misal: daftar kuliah, beasiswa, atau bank).',
        ],
        self::TYPE_INTERNSHIP => [
            'Mengisi formulir permohonan.',
            'Surat penerimaan dari perusahaan/industri (jika sudah ada).',
            'Fotokopi rapor atau nilai terakhir.',
        ],
        self::TYPE_GOOD_BEHAVIOR => [
            'Mengisi formulir permohonan.',
            'Surat pengantar dari wali kelas.',
            'Fotokopi kartu pelajar.',
        ],
        self::TYPE_TRANSFER => [
            'Surat permohonan dari orang tua.',
            'Fotokopi rapor atau buku induk.',
            'Surat keterangan pindah dari sekolah asal.',
            'Surat rekomendasi dari dinas pendidikan (jika pindah antar kota/provinsi).',
        ],
    ];

    public const TYPE_ATTACHMENT_REQUIRED = [
        self::TYPE_ACTIVE_STUDENT => true,
        self::TYPE_INTERNSHIP => true,
        self::TYPE_GOOD_BEHAVIOR => true,
        self::TYPE_TRANSFER => true,
    ];

    public const TYPE_REQUIRED_ATTACHMENT_COUNT = [
        self::TYPE_ACTIVE_STUDENT => 3,
        self::TYPE_INTERNSHIP => 3,
        self::TYPE_GOOD_BEHAVIOR => 3,
        self::TYPE_TRANSFER => 4,
    ];

    public const TYPE_COLORS = [
        self::TYPE_ACTIVE_STUDENT => 'blue',
        self::TYPE_INTERNSHIP => 'teal',
        self::TYPE_GOOD_BEHAVIOR => 'cyan',
        self::TYPE_TRANSFER => 'orange',
    ];

    public const TYPE_ICONS = [
        self::TYPE_ACTIVE_STUDENT => 'academic-cap',
        self::TYPE_INTERNSHIP => 'building-office-2',
        self::TYPE_GOOD_BEHAVIOR => 'shield-check',
        self::TYPE_TRANSFER => 'arrows-right-left',
    ];

    /**
     * Status Constants
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [
        self::STATUS_PENDING => 'Menunggu',
        self::STATUS_PROCESSING => 'Diproses',
        self::STATUS_COMPLETED => 'Selesai',
        self::STATUS_REJECTED => 'Ditolak',
    ];

    public const STATUS_COLORS = [
        self::STATUS_PENDING => 'amber',
        self::STATUS_PROCESSING => 'yellow',
        self::STATUS_COMPLETED => 'green',
        self::STATUS_REJECTED => 'red',
    ];

    /**
     * Relationships
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Scopes
     */
    public function scopeForStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Generate request number
     */
    public static function generateRequestNumber(): string
    {
        $year = now()->year;
        $month = str_pad(now()->month, 2, '0', STR_PAD_LEFT);
        $count = static::whereYear('created_at', $year)
                       ->whereMonth('created_at', now()->month)
                       ->count() + 1;

        return sprintf('REQ/%s/%s/%04d', $month, $year, $count);
    }

    /**
     * Check if request can be cancelled
     */
    public function canBeCancelled(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if result file can be downloaded
     */
    public function canBeDownloaded(): bool
    {
        return $this->status === self::STATUS_COMPLETED && !empty($this->result_file);
    }

    /**
     * Normalize attachment data for backward compatibility.
     * Old records may still contain a single string path.
     */
    public function attachmentFiles(): array
    {
        if (empty($this->attachment)) {
            return [];
        }

        if (is_array($this->attachment)) {
            return array_values(array_filter($this->attachment));
        }

        return [(string) $this->attachment];
    }

    /**
     * Get type label
     */
    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->letter_type] ?? $this->letter_type;
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
