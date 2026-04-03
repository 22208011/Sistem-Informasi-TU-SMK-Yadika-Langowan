<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Extracurricular extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'category',
        'schedule',
        'location',
        'coach_id',
        'academic_year_id',
        'max_members',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'max_members' => 'integer',
    ];

    public const CATEGORIES = [
        'olahraga' => 'Olahraga',
        'seni' => 'Seni & Budaya',
        'akademik' => 'Akademik',
        'keagamaan' => 'Keagamaan',
        'keterampilan' => 'Keterampilan',
        'lainnya' => 'Lainnya',
    ];

    public function coach(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'coach_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(ExtracurricularMember::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'extracurricular_members')
            ->withPivot(['role', 'join_date', 'leave_date', 'status', 'notes', 'academic_year_id'])
            ->withTimestamps();
    }

    public function activeMembers(): HasMany
    {
        return $this->hasMany(ExtracurricularMember::class)->where('status', 'aktif');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function getMemberCountAttribute(): int
    {
        return $this->activeMembers()->count();
    }

    public function getAvailableSlotsAttribute(): int
    {
        return max(0, $this->max_members - $this->member_count);
    }

    public function isFull(): bool
    {
        return $this->available_slots <= 0;
    }
}
