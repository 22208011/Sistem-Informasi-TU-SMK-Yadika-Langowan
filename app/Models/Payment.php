<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    protected $fillable = [
        'payment_type_id',
        'student_id',
        'academic_year_id',
        'invoice_number',
        'amount',
        'discount',
        'total_amount',
        'paid_amount',
        'payment_status',
        'due_date',
        'month',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_date' => 'date',
        'month' => 'integer',
    ];

    public const STATUSES = [
        'belum_bayar' => 'Belum Bayar',
        'sebagian' => 'Bayar Sebagian',
        'lunas' => 'Lunas',
    ];

    public const MONTHS = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    public function paymentType(): BelongsTo
    {
        return $this->belongsTo(PaymentType::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function scopeUnpaid($query)
    {
        return $query->where('payment_status', 'belum_bayar');
    }

    public function scopePartiallyPaid($query)
    {
        return $query->where('payment_status', 'sebagian');
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', 'lunas');
    }

    public function scopeOverdue($query)
    {
        return $query->whereIn('payment_status', ['belum_bayar', 'sebagian'])
            ->where('due_date', '<', now()->toDateString());
    }

    public function scopeByStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeByAcademicYear($query, $academicYearId)
    {
        return $query->where('academic_year_id', $academicYearId);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->payment_status] ?? $this->payment_status;
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->payment_status) {
            'belum_bayar' => 'red',
            'sebagian' => 'yellow',
            'lunas' => 'green',
            default => 'zinc',
        };
    }

    public function getMonthLabelAttribute(): string
    {
        return self::MONTHS[$this->month] ?? '-';
    }

    public function getRemainingAmountAttribute(): float
    {
        return max(0, $this->total_amount - $this->paid_amount);
    }

    public function getFormattedAmountAttribute(): string
    {
        return 'Rp '.number_format($this->amount, 0, ',', '.');
    }

    public function getFormattedTotalAttribute(): string
    {
        return 'Rp '.number_format($this->total_amount, 0, ',', '.');
    }

    public function getFormattedPaidAttribute(): string
    {
        return 'Rp '.number_format($this->paid_amount, 0, ',', '.');
    }

    public function getFormattedRemainingAttribute(): string
    {
        return 'Rp '.number_format($this->remaining_amount, 0, ',', '.');
    }

    public function isOverdue(): bool
    {
        return $this->payment_status !== 'lunas' && $this->due_date && $this->due_date->isPast();
    }

    public function updateStatus(): void
    {
        $this->paid_amount = $this->transactions()->sum('amount');

        if ($this->paid_amount <= 0) {
            $this->payment_status = 'belum_bayar';
        } elseif ($this->paid_amount >= $this->total_amount) {
            $this->payment_status = 'lunas';
            $this->paid_amount = $this->total_amount; // Cap at total
        } else {
            $this->payment_status = 'sebagian';
        }

        $this->save();
    }

    public static function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $date = now()->format('Ymd');
        $last = static::whereDate('created_at', today())->count() + 1;

        return $prefix.$date.str_pad($last, 4, '0', STR_PAD_LEFT);
    }
}
