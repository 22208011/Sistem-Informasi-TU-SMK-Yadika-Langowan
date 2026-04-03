<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'nis',
        'nisn',
        'name',
        'gender',
        'place_of_birth',
        'date_of_birth',
        'religion',
        'address',
        'phone',
        'email',
        'previous_school',
        'entry_year',
        'status',
        'classroom_id',
        'department_id',
        'academic_year_id',
        'photo',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'entry_year' => 'integer',
        ];
    }

    /**
     * Status Constants
     */
    public const STATUS_AKTIF = 'aktif';
    public const STATUS_LULUS = 'lulus';
    public const STATUS_PINDAH = 'pindah';
    public const STATUS_KELUAR = 'keluar';
    public const STATUS_DO = 'do';

    public const STATUSES = [
        self::STATUS_AKTIF => 'Aktif',
        self::STATUS_LULUS => 'Lulus',
        self::STATUS_PINDAH => 'Pindah',
        self::STATUS_KELUAR => 'Keluar',
        self::STATUS_DO => 'Dikeluarkan',
    ];

    public const STATUS_COLORS = [
        self::STATUS_AKTIF => 'green',
        self::STATUS_LULUS => 'blue',
        self::STATUS_PINDAH => 'yellow',
        self::STATUS_KELUAR => 'zinc',
        self::STATUS_DO => 'red',
    ];

    /**
     * Gender Constants
     */
    public const GENDER_MALE = 'L';
    public const GENDER_FEMALE = 'P';

    public const GENDERS = [
        self::GENDER_MALE => 'Laki-laki',
        self::GENDER_FEMALE => 'Perempuan',
    ];

    /**
     * Religion Constants
     */
    public const RELIGIONS = [
        'islam' => 'Islam',
        'kristen' => 'Kristen',
        'katolik' => 'Katolik',
        'hindu' => 'Hindu',
        'buddha' => 'Buddha',
        'konghucu' => 'Konghucu',
    ];

    /**
     * Get the classroom
     */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    /**
     * Get the department
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the academic year
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Get guardians
     */
    public function guardians(): HasMany
    {
        return $this->hasMany(Guardian::class);
    }

    /**
     * Get attendances
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(StudentAttendance::class);
    }

    /**
     * Get mutations
     */
    public function mutations(): HasMany
    {
        return $this->hasMany(StudentMutation::class);
    }

    /**
     * Get primary guardian (hasOne relationship for easy access)
     */
    public function guardian(): HasOne
    {
        return $this->hasOne(Guardian::class)->where('is_primary', true)
            ->withDefault(function () {
                // Return first guardian if no primary guardian
                return $this->guardians()->first();
            });
    }

    /**
     * Get primary guardian (method for backward compatibility)
     */
    public function primaryGuardian()
    {
        return $this->guardians()->where('is_primary', true)->first()
            ?? $this->guardians()->first();
    }

    /**
     * Scope for active students
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_AKTIF);
    }

    /**
     * Scope for specific classroom
     */
    public function scopeInClassroom($query, $classroomId)
    {
        return $query->where('classroom_id', $classroomId);
    }

    /**
     * Scope for specific department
     */
    public function scopeInDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * Scope for entry year
     */
    public function scopeEntryYear($query, $year)
    {
        return $query->where('entry_year', $year);
    }

    /**
     * Get photo URL
     */
    public function getPhotoUrlAttribute(): ?string
    {
        if ($this->photo) {
            return asset('storage/' . $this->photo);
        }
        return null;
    }

    /**
     * Get age
     */
    public function getAgeAttribute(): ?int
    {
        if ($this->date_of_birth) {
            return $this->date_of_birth->age;
        }
        return null;
    }

    /**
     * Get full identity
     */
    public function getFullIdentityAttribute(): string
    {
        return "{$this->name} (NIS: {$this->nis})";
    }
}
