<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Letter extends Model
{
    use HasFactory;

    protected $fillable = [
        'letter_number',
        'letter_type',
        'subject',
        'content',
        'recipient_type',
        'recipient_id',
        'student_id',
        'author_id',
        'approver_id',
        'status',
        'issued_at',
        'approved_at',
        'notes',
        'attachment',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    /**
     * Letter Type Constants
     * =====================
     * ADMINISTRASI KESISWAAN
     */
    public const TYPE_SUMMONS = 'summons';                   // Surat Panggilan Orang Tua
    public const TYPE_WARNING = 'warning';                   // Surat Peringatan
    public const TYPE_TRANSFER = 'transfer';                 // Surat Pindah
    public const TYPE_GRADUATION = 'graduation';             // Surat Keterangan Lulus
    public const TYPE_ACTIVE_STUDENT = 'active_student';     // Surat Keterangan Siswa Aktif
    public const TYPE_GOOD_BEHAVIOR = 'good_behavior';       // Surat Keterangan Kelakuan Baik (SKKB)
    public const TYPE_PIP = 'pip';                           // Surat Usulan PIP/Beasiswa
    public const TYPE_INTERNSHIP = 'internship';             // Surat Pengantar Prakerin/PKL
    public const TYPE_ALUMNI = 'alumni';                     // Surat Keterangan Alumni

    /**
     * ADMINISTRASI KEPEGAWAIAN
     */
    public const TYPE_RECOMMENDATION = 'recommendation';      // Surat Rekomendasi
    public const TYPE_ASSIGNMENT = 'assignment';              // Surat Tugas
    public const TYPE_PERMIT = 'permit';                      // Surat Izin
    public const TYPE_TRAVEL_ORDER = 'travel_order';          // SPPD (Surat Perintah Perjalanan Dinas)
    public const TYPE_LEAVE = 'leave';                        // Surat Cuti Pegawai
    public const TYPE_DECREE = 'decree';                      // SK Kepala Sekolah

    /**
     * PERSURATAN UMUM
     */
    public const TYPE_CIRCULAR = 'circular';                  // Surat Edaran
    public const TYPE_NOTIFICATION = 'notification';          // Surat Pemberitahuan
    public const TYPE_INVITATION = 'invitation';              // Surat Undangan
    public const TYPE_OTHER = 'other';                        // Lainnya

    public const TYPES = [
        // Administrasi Kesiswaan
        self::TYPE_SUMMONS => 'Surat Panggilan Orang Tua',
        self::TYPE_WARNING => 'Surat Peringatan',
        self::TYPE_TRANSFER => 'Surat Pindah',
        self::TYPE_GRADUATION => 'Surat Keterangan Lulus',
        self::TYPE_ACTIVE_STUDENT => 'Surat Keterangan Siswa Aktif',
        self::TYPE_GOOD_BEHAVIOR => 'Surat Keterangan Kelakuan Baik (SKKB)',
        self::TYPE_PIP => 'Surat Usulan PIP/Beasiswa',
        self::TYPE_INTERNSHIP => 'Surat Pengantar Prakerin/PKL',
        self::TYPE_ALUMNI => 'Surat Keterangan Alumni',

        // Administrasi Kepegawaian
        self::TYPE_RECOMMENDATION => 'Surat Rekomendasi',
        self::TYPE_ASSIGNMENT => 'Surat Tugas',
        self::TYPE_PERMIT => 'Surat Izin',
        self::TYPE_TRAVEL_ORDER => 'SPPD (Surat Perintah Perjalanan Dinas)',
        self::TYPE_LEAVE => 'Surat Cuti Pegawai',
        self::TYPE_DECREE => 'SK Kepala Sekolah',

        // Persuratan Umum
        self::TYPE_CIRCULAR => 'Surat Edaran',
        self::TYPE_NOTIFICATION => 'Surat Pemberitahuan',
        self::TYPE_INVITATION => 'Surat Undangan',
        self::TYPE_OTHER => 'Lainnya',
    ];

    /**
     * Letter Type Colors
     */
    public const TYPE_COLORS = [
        // Kesiswaan - Warna Biru/Cyan
        self::TYPE_SUMMONS => 'yellow',
        self::TYPE_WARNING => 'red',
        self::TYPE_TRANSFER => 'orange',
        self::TYPE_GRADUATION => 'green',
        self::TYPE_ACTIVE_STUDENT => 'blue',
        self::TYPE_GOOD_BEHAVIOR => 'cyan',
        self::TYPE_PIP => 'lime',
        self::TYPE_INTERNSHIP => 'teal',
        self::TYPE_ALUMNI => 'sky',

        // Kepegawaian - Warna Ungu/Indigo
        self::TYPE_RECOMMENDATION => 'indigo',
        self::TYPE_ASSIGNMENT => 'violet',
        self::TYPE_PERMIT => 'purple',
        self::TYPE_TRAVEL_ORDER => 'fuchsia',
        self::TYPE_LEAVE => 'pink',
        self::TYPE_DECREE => 'rose',

        // Umum - Warna Netral
        self::TYPE_CIRCULAR => 'amber',
        self::TYPE_NOTIFICATION => 'emerald',
        self::TYPE_INVITATION => 'slate',
        self::TYPE_OTHER => 'zinc',
    ];

    /**
     * Status Constants
     */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_SENT = 'sent';

    public const STATUSES = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_PENDING => 'Menunggu Persetujuan',
        self::STATUS_APPROVED => 'Disetujui',
        self::STATUS_REJECTED => 'Ditolak',
        self::STATUS_SENT => 'Terkirim',
    ];

    public const STATUS_COLORS = [
        self::STATUS_DRAFT => 'zinc',
        self::STATUS_PENDING => 'yellow',
        self::STATUS_APPROVED => 'green',
        self::STATUS_REJECTED => 'red',
        self::STATUS_SENT => 'blue',
    ];

    /**
     * Recipient Type Constants
     */
    public const RECIPIENT_STUDENT = 'student';
    public const RECIPIENT_GUARDIAN = 'guardian';
    public const RECIPIENT_EMPLOYEE = 'employee';
    public const RECIPIENT_EXTERNAL = 'external';

    public const RECIPIENT_TYPES = [
        self::RECIPIENT_STUDENT => 'Siswa',
        self::RECIPIENT_GUARDIAN => 'Orang Tua/Wali',
        self::RECIPIENT_EMPLOYEE => 'Pegawai',
        self::RECIPIENT_EXTERNAL => 'Eksternal',
    ];

    /**
     * Get the student (if letter is for a student)
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the author
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Backward-compatible alias for author relation.
     */
    public function createdBy(): BelongsTo
    {
        return $this->author();
    }

    /**
     * Get the approver
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /**
     * Backward-compatible alias for approver relation.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->approver();
    }

    /**
     * Scope for status
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('letter_type', $type);
    }

    /**
     * Scope for pending approval
     */
    public function scopePendingApproval($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for student letters
     */
    public function scopeForStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Check if letter is editable
     */
    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_REJECTED]);
    }

    /**
     * Check if letter can be approved
     */
    public function canBeApproved(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Generate letter number
     */
    public static function generateLetterNumber(string $type): string
    {
        $prefix = match ($type) {
            // Administrasi Kesiswaan
            self::TYPE_SUMMONS => 'PGL',
            self::TYPE_WARNING => 'PRG',
            self::TYPE_TRANSFER => 'PND',
            self::TYPE_GRADUATION => 'KLS',
            self::TYPE_ACTIVE_STUDENT => 'KSA',
            self::TYPE_GOOD_BEHAVIOR => 'SKKB',
            self::TYPE_PIP => 'PIP',
            self::TYPE_INTERNSHIP => 'PKL',
            self::TYPE_ALUMNI => 'ALM',

            // Administrasi Kepegawaian
            self::TYPE_RECOMMENDATION => 'REK',
            self::TYPE_ASSIGNMENT => 'TGS',
            self::TYPE_PERMIT => 'IZN',
            self::TYPE_TRAVEL_ORDER => 'SPPD',
            self::TYPE_LEAVE => 'CTI',
            self::TYPE_DECREE => 'SK',

            // Persuratan Umum
            self::TYPE_CIRCULAR => 'EDR',
            self::TYPE_NOTIFICATION => 'PBT',
            self::TYPE_INVITATION => 'UND',
            default => 'SRT',
        };

        $year = now()->year;
        $month = str_pad(now()->month, 2, '0', STR_PAD_LEFT);
        $count = static::whereYear('created_at', $year)
                       ->whereMonth('created_at', now()->month)
                       ->where('letter_type', $type)
                       ->count() + 1;

        return sprintf('%s/%s/%s/%04d', $prefix, $month, $year, $count);
    }
}
