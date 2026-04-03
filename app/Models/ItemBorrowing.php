<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ItemBorrowing extends Model
{
    protected $fillable = [
        'inventory_item_id',
        'borrower_type',
        'borrower_id',
        'borrower_name',
        'borrower_contact',
        'quantity_borrowed',
        'borrow_date',
        'expected_return_date',
        'actual_return_date',
        'status',
        'return_condition',
        'purpose',
        'notes',
        'approved_by',
        'received_by',
    ];

    protected $casts = [
        'borrow_date' => 'date',
        'expected_return_date' => 'date',
        'actual_return_date' => 'date',
        'quantity_borrowed' => 'integer',
    ];

    public const BORROWER_TYPES = [
        'employee' => 'Pegawai',
        'student' => 'Siswa',
        'external' => 'Lainnya',
    ];

    public const STATUSES = [
        'dipinjam' => 'Dipinjam',
        'dikembalikan' => 'Dikembalikan',
        'terlambat' => 'Terlambat',
        'hilang' => 'Hilang',
    ];

    public const RETURN_CONDITIONS = [
        'baik' => 'Baik',
        'rusak_ringan' => 'Rusak Ringan',
        'rusak_berat' => 'Rusak Berat',
        'hilang' => 'Hilang',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function borrower()
    {
        if ($this->borrower_type === 'employee' && $this->borrower_id) {
            return Employee::find($this->borrower_id);
        } elseif ($this->borrower_type === 'student' && $this->borrower_id) {
            return Student::find($this->borrower_id);
        }
        return null;
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'dipinjam');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'dipinjam')
            ->where('expected_return_date', '<', now()->toDateString());
    }

    public function scopeReturned($query)
    {
        return $query->where('status', 'dikembalikan');
    }

    public function scopeByBorrowerType($query, $type)
    {
        return $query->where('borrower_type', $type);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'dipinjam' => 'blue',
            'dikembalikan' => 'green',
            'terlambat' => 'yellow',
            'hilang' => 'red',
            default => 'zinc',
        };
    }

    public function getBorrowerTypeLabelAttribute(): string
    {
        return self::BORROWER_TYPES[$this->borrower_type] ?? $this->borrower_type;
    }

    public function isOverdue(): bool
    {
        return $this->status === 'dipinjam' && $this->expected_return_date->isPast();
    }

    public function getDaysOverdueAttribute(): int
    {
        if (!$this->isOverdue()) {
            return 0;
        }
        return $this->expected_return_date->diffInDays(now());
    }

    public function getDaysRemainingAttribute(): int
    {
        if ($this->status !== 'dipinjam') {
            return 0;
        }
        return now()->diffInDays($this->expected_return_date, false);
    }

    public function markAsReturned(string $condition = 'baik', ?int $receivedBy = null, ?string $notes = null): void
    {
        $this->update([
            'status' => 'dikembalikan',
            'actual_return_date' => now()->toDateString(),
            'return_condition' => $condition,
            'received_by' => $receivedBy ?? auth()->id(),
            'notes' => $notes ?? $this->notes,
        ]);

        // Update item's available quantity
        $this->item->increment('available_quantity', $this->quantity_borrowed);
    }
}
