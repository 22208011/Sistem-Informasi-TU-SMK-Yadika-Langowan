<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'date',
        'status',
        'check_in',
        'check_out',
        'late_minutes',
        'early_leave_minutes',
        'overtime_minutes',
        'notes',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'check_in' => 'datetime',
            'check_out' => 'datetime',
            'late_minutes' => 'integer',
            'early_leave_minutes' => 'integer',
            'overtime_minutes' => 'integer',
        ];
    }

    /**
     * Attendance Status Constants
     */
    public const STATUS_HADIR = 'hadir';

    public const STATUS_IZIN = 'izin';

    public const STATUS_SAKIT = 'sakit';

    public const STATUS_CUTI = 'cuti';

    public const STATUS_DINAS_LUAR = 'dinas_luar';

    public const STATUS_ALPHA = 'alpha';

    public const STATUSES = [
        self::STATUS_HADIR => 'Hadir',
        self::STATUS_IZIN => 'Izin',
        self::STATUS_SAKIT => 'Sakit',
        self::STATUS_CUTI => 'Cuti',
        self::STATUS_DINAS_LUAR => 'Dinas Luar',
        self::STATUS_ALPHA => 'Tanpa Keterangan',
    ];

    public const STATUS_COLORS = [
        self::STATUS_HADIR => 'green',
        self::STATUS_IZIN => 'blue',
        self::STATUS_SAKIT => 'yellow',
        self::STATUS_CUTI => 'purple',
        self::STATUS_DINAS_LUAR => 'cyan',
        self::STATUS_ALPHA => 'red',
    ];

    /**
     * Get the employee
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the user who recorded this attendance
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Scope for specific date
     */
    public function scopeForDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }

    /**
     * Scope for date range
     */
    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope for specific month
     */
    public function scopeForMonth($query, $year, $month)
    {
        return $query->whereYear('date', $year)
            ->whereMonth('date', $month);
    }

    /**
     * Check if present (hadir)
     */
    public function isPresent(): bool
    {
        return $this->status === self::STATUS_HADIR;
    }

    /**
     * Get formatted check in time
     */
    public function getFormattedCheckInAttribute(): ?string
    {
        return $this->check_in?->format('H:i');
    }

    /**
     * Get formatted check out time
     */
    public function getFormattedCheckOutAttribute(): ?string
    {
        return $this->check_out?->format('H:i');
    }
}
