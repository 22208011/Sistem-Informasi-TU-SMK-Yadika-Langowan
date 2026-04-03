<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Position extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'category',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Category constants
     */
    public const CATEGORY_STRUKTURAL = 'struktural';

    public const CATEGORY_FUNGSIONAL = 'fungsional';

    public const CATEGORIES = [
        self::CATEGORY_STRUKTURAL => 'Struktural',
        self::CATEGORY_FUNGSIONAL => 'Fungsional',
    ];

    /**
     * Get employees with this position
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Scope for active positions
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
