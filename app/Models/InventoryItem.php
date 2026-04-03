<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'code',
        'description',
        'brand',
        'model',
        'serial_number',
        'quantity',
        'available_quantity',
        'condition',
        'location',
        'purchase_date',
        'purchase_price',
        'supplier',
        'warranty_until',
        'photo',
        'notes',
        'created_by',
        'is_active',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'warranty_until' => 'date',
        'purchase_price' => 'decimal:2',
        'quantity' => 'integer',
        'available_quantity' => 'integer',
        'is_active' => 'boolean',
    ];

    public const CONDITIONS = [
        'baik' => 'Baik',
        'rusak_ringan' => 'Rusak Ringan',
        'rusak_berat' => 'Rusak Berat',
        'hilang' => 'Hilang',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function borrowings(): HasMany
    {
        return $this->hasMany(ItemBorrowing::class);
    }

    public function activeBorrowings(): HasMany
    {
        return $this->hasMany(ItemBorrowing::class)->where('status', 'dipinjam');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_active', true)->where('available_quantity', '>', 0);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByCondition($query, $condition)
    {
        return $query->where('condition', $condition);
    }

    public function getConditionLabelAttribute(): string
    {
        return self::CONDITIONS[$this->condition] ?? $this->condition;
    }

    public function getConditionColorAttribute(): string
    {
        return match ($this->condition) {
            'baik' => 'green',
            'rusak_ringan' => 'yellow',
            'rusak_berat' => 'red',
            'hilang' => 'gray',
            default => 'zinc',
        };
    }

    public function getBorrowedQuantityAttribute(): int
    {
        return $this->activeBorrowings()->sum('quantity_borrowed');
    }

    public function isAvailable(): bool
    {
        return $this->is_active && $this->available_quantity > 0;
    }

    public function hasWarranty(): bool
    {
        return $this->warranty_until && $this->warranty_until->isFuture();
    }

    public function getFormattedPriceAttribute(): string
    {
        return $this->purchase_price ? 'Rp '.number_format($this->purchase_price, 0, ',', '.') : '-';
    }
}
