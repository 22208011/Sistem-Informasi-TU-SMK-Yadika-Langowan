<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'nip',
        'nuptk',
        'name',
        'gender',
        'place_of_birth',
        'date_of_birth',
        'religion',
        'address',
        'phone',
        'email',
        'employee_status',
        'employee_type',
        'join_date',
        'education_level',
        'education_major',
        'education_institution',
        'position_id',
        'department_id',
        'user_id',
        'photo',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'join_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Employee Status Constants
     */
    public const STATUS_PNS = 'pns';

    public const STATUS_PPPK = 'pppk';

    public const STATUS_HONORER = 'honorer';

    public const STATUS_KONTRAK = 'kontrak';

    public const STATUSES = [
        self::STATUS_PNS => 'PNS',
        self::STATUS_PPPK => 'PPPK',
        self::STATUS_HONORER => 'Honorer',
        self::STATUS_KONTRAK => 'Kontrak',
    ];

    /**
     * Employee Type Constants
     */
    public const TYPE_GURU = 'guru';

    public const TYPE_TENDIK = 'tendik';

    public const TYPES = [
        self::TYPE_GURU => 'Guru',
        self::TYPE_TENDIK => 'Tenaga Kependidikan',
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
     * Education Level Constants
     */
    public const EDUCATION_LEVELS = [
        'sma' => 'SMA/SMK',
        'd1' => 'D1',
        'd2' => 'D2',
        'd3' => 'D3',
        'd4' => 'D4',
        's1' => 'S1',
        's2' => 'S2',
        's3' => 'S3',
    ];

    /**
     * Get the position
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * Get the department (for productive teachers)
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the linked user account
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get attendances
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(EmployeeAttendance::class);
    }

    /**
     * Scope for active employees
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for teachers (guru)
     */
    public function scopeTeachers($query)
    {
        return $query->where('employee_type', self::TYPE_GURU);
    }

    /**
     * Scope for staff (tendik)
     */
    public function scopeStaff($query)
    {
        return $query->where('employee_type', self::TYPE_TENDIK);
    }

    /**
     * Get photo URL
     */
    public function getPhotoUrlAttribute(): ?string
    {
        if ($this->photo) {
            return asset('storage/'.$this->photo);
        }

        return null;
    }

    /**
     * Get full name with NIP
     */
    public function getFullIdentityAttribute(): string
    {
        $identity = $this->name;
        if ($this->nip) {
            $identity .= " (NIP: {$this->nip})";
        } elseif ($this->nuptk) {
            $identity .= " (NUPTK: {$this->nuptk})";
        }

        return $identity;
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
}
