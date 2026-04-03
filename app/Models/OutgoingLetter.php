<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutgoingLetter extends Model
{
    use HasFactory;

    protected $fillable = [
        'agenda_number',
        'letter_number',
        'letter_date',
        'sent_date',
        'recipient',
        'recipient_address',
        'subject',
        'classification',
        'nature',
        'attachment_count',
        'attachment_type',
        'content_summary',
        'notes',
        'file_path',
        'created_by',
        'signed_by',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'letter_date' => 'date',
            'sent_date' => 'date',
        ];
    }

    /**
     * Classification Constants (Tujuan Surat)
     */
    public const CLASS_DINAS_PENDIDIKAN = 'dinas_pendidikan';

    public const CLASS_YAYASAN = 'yayasan';

    public const CLASS_INSTANSI_LAIN = 'instansi_lain';

    public const CLASS_ORANG_TUA = 'orang_tua';

    public const CLASS_PERUSAHAAN = 'perusahaan';

    public const CLASS_ALUMNI = 'alumni';

    public const CLASS_LAINNYA = 'lainnya';

    public const CLASSIFICATIONS = [
        self::CLASS_DINAS_PENDIDIKAN => 'Dinas Pendidikan',
        self::CLASS_YAYASAN => 'Yayasan',
        self::CLASS_INSTANSI_LAIN => 'Instansi Lain',
        self::CLASS_ORANG_TUA => 'Orang Tua/Wali',
        self::CLASS_PERUSAHAAN => 'Perusahaan/Industri',
        self::CLASS_ALUMNI => 'Alumni',
        self::CLASS_LAINNYA => 'Lainnya',
    ];

    /**
     * Nature Constants (Sifat Surat)
     */
    public const NATURE_BIASA = 'biasa';

    public const NATURE_PENTING = 'penting';

    public const NATURE_RAHASIA = 'rahasia';

    public const NATURE_SANGAT_RAHASIA = 'sangat_rahasia';

    public const NATURES = [
        self::NATURE_BIASA => 'Biasa',
        self::NATURE_PENTING => 'Penting',
        self::NATURE_RAHASIA => 'Rahasia',
        self::NATURE_SANGAT_RAHASIA => 'Sangat Rahasia',
    ];

    public const NATURE_COLORS = [
        self::NATURE_BIASA => 'zinc',
        self::NATURE_PENTING => 'yellow',
        self::NATURE_RAHASIA => 'orange',
        self::NATURE_SANGAT_RAHASIA => 'red',
    ];

    /**
     * Status Constants
     */
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SENT = 'sent';

    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_SENT => 'Terkirim',
        self::STATUS_ARCHIVED => 'Diarsipkan',
    ];

    public const STATUS_COLORS = [
        self::STATUS_DRAFT => 'zinc',
        self::STATUS_SENT => 'green',
        self::STATUS_ARCHIVED => 'slate',
    ];

    /**
     * Get the user who created this letter
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who signed this letter
     */
    public function signer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by');
    }

    /**
     * Scope for status
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for classification
     */
    public function scopeByClassification($query, string $classification)
    {
        return $query->where('classification', $classification);
    }

    /**
     * Scope for nature
     */
    public function scopeByNature($query, string $nature)
    {
        return $query->where('nature', $nature);
    }

    /**
     * Scope for date range
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('letter_date', [$startDate, $endDate]);
    }

    /**
     * Generate agenda number
     */
    public static function generateAgendaNumber(): string
    {
        $year = now()->year;
        $month = str_pad(now()->month, 2, '0', STR_PAD_LEFT);
        $count = static::whereYear('created_at', $year)
            ->whereMonth('created_at', now()->month)
            ->count() + 1;

        return sprintf('SK/%s/%s/%04d', $month, $year, $count);
    }

    /**
     * Generate letter number for outgoing letters
     */
    public static function generateLetterNumber(): string
    {
        $year = now()->year;
        $month = str_pad(now()->month, 2, '0', STR_PAD_LEFT);
        $count = static::whereYear('created_at', $year)
            ->whereMonth('created_at', now()->month)
            ->count() + 1;

        return sprintf('%04d/SMK-YL/%s/%s', $count, $month, $year);
    }

    /**
     * Check if letter can be edited
     */
    public function isEditable(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Get classification label
     */
    public function getClassificationLabelAttribute(): string
    {
        return self::CLASSIFICATIONS[$this->classification] ?? $this->classification;
    }

    /**
     * Get nature label
     */
    public function getNatureLabelAttribute(): string
    {
        return self::NATURES[$this->nature] ?? $this->nature;
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
