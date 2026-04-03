<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'employee_id',
        'student_id',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the user's role
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the employee associated with this user
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the student profile (for student users)
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the guardian profile (for parent users)
     */
    public function guardian(): HasOne
    {
        return $this->hasOne(Guardian::class);
    }

    /**
     * Get children (students) for parent users
     * A parent can have multiple children through multiple guardian records
     */
    public function children()
    {
        return $this->hasManyThrough(
            Student::class,
            Guardian::class,
            'user_id',      // Foreign key on guardians table
            'id',           // Foreign key on students table
            'id',           // Local key on users table
            'student_id'    // Local key on guardians table
        );
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole(string $roleName): bool
    {
        return $this->role?->name === $roleName;
    }

    /**
     * Check if user has any of the given roles
     */
    public function hasAnyRole(array $roleNames): bool
    {
        return in_array($this->role?->name, $roleNames);
    }

    /**
     * Check if user has a specific permission
     */
    public function hasPermission(string $permission): bool
    {
        return $this->role?->hasPermission($permission) ?? false;
    }

    /**
     * Check if user has any of the given permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(Role::ADMIN);
    }

    /**
     * Scope for active users
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * Check if user is a teacher
     */
    public function isTeacher(): bool
    {
        return $this->hasRole(Role::GURU);
    }

    /**
     * Check if user is a parent
     */
    public function isParent(): bool
    {
        return $this->hasRole(Role::ORANG_TUA);
    }

    /**
     * Check if user is a student
     */
    public function isStudent(): bool
    {
        return $this->hasRole(Role::SISWA);
    }

    /**
     * Check if user is kepala sekolah
     */
    public function isKepalaSekolah(): bool
    {
        return $this->hasRole(Role::KEPALA_SEKOLAH);
    }

    /**
     * Check if user is staff (admin, kepala sekolah)
     */
    public function isStaff(): bool
    {
        return $this->hasAnyRole(Role::STAFF_ROLES);
    }

    /**
     * Get dashboard route based on role
     */
    public function getDashboardRoute(): string
    {
        return match ($this->role?->name) {
            Role::ADMIN => 'dashboard',
            Role::KEPALA_SEKOLAH => 'dashboard',
            Role::GURU => 'dashboard',
            Role::ORANG_TUA => 'parent.dashboard',
            Role::SISWA => 'student-portal.dashboard',
            default => 'dashboard',
        };
    }

    /**
     * Get classrooms where user is homeroom teacher
     */
    public function homeroomClassrooms(): HasMany
    {
        return $this->hasMany(Classroom::class, 'homeroom_teacher_id');
    }

    /**
     * Check if user can access student data
     */
    public function canAccessStudent(Student $student): bool
    {
        // Admin and staff can access all
        if ($this->isAdmin() || $this->isStaff()) {
            return true;
        }

        // Teachers can access students in their classes
        if ($this->isTeacher() && $this->employee) {
            // Check if teacher teaches in student's classroom
            $teachesStudent = Schedule::where('teacher_id', $this->employee->id)
                ->where('classroom_id', $student->classroom_id)
                ->exists();

            // Or is homeroom teacher
            $isHomeroom = $this->homeroomClassrooms()
                ->where('id', $student->classroom_id)
                ->exists();

            return $teachesStudent || $isHomeroom;
        }

        // Parents can only access their children
        if ($this->isParent()) {
            return $this->guardian?->student_id === $student->id;
        }

        return false;
    }
}
