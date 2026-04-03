<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Guardian extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'student_id',
        'relationship',
        'name',
        'nik',
        'place_of_birth',
        'date_of_birth',
        'religion',
        'education',
        'occupation',
        'income',
        'address',
        'phone',
        'email',
        'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'is_primary' => 'boolean',
        ];
    }

    /**
     * Relationship Constants
     */
    public const RELATIONSHIP_AYAH = 'ayah';

    public const RELATIONSHIP_IBU = 'ibu';

    public const RELATIONSHIP_WALI = 'wali';

    public const RELATIONSHIPS = [
        self::RELATIONSHIP_AYAH => 'Ayah',
        self::RELATIONSHIP_IBU => 'Ibu',
        self::RELATIONSHIP_WALI => 'Wali',
    ];

    /**
     * Education Constants
     */
    public const EDUCATIONS = [
        'sd' => 'SD/Sederajat',
        'smp' => 'SMP/Sederajat',
        'sma' => 'SMA/SMK/Sederajat',
        'd1' => 'D1',
        'd2' => 'D2',
        'd3' => 'D3',
        'd4' => 'D4',
        's1' => 'S1',
        's2' => 'S2',
        's3' => 'S3',
    ];

    /**
     * Income Constants
    /**
     * Income Constants
     */
    public const INCOMES = [
        'below_1m' => 'Kurang dari Rp 1.000.000',
        '1m_3m' => 'Rp 1.000.000 - Rp 3.000.000',
        '3m_5m' => 'Rp 3.000.000 - Rp 5.000.000',
        '5m_10m' => 'Rp 5.000.000 - Rp 10.000.000',
        'above_10m' => 'Lebih dari Rp 10.000.000',
    ];

    /**
     * Get the user account (for parent login)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the student
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Check if guardian has user account
     */
    public function hasUserAccount(): bool
    {
        return $this->user_id !== null;
    }

    /**
     * Get full name with relationship
     */
    public function getFullNameAttribute(): string
    {
        $relationship = self::RELATIONSHIPS[$this->relationship] ?? $this->relationship;

        return "{$this->name} ({$relationship})";
    }
}
