<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
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
     * Role constants for 4 main user types
     */
    public const ADMIN = 'admin';

    public const KEPALA_SEKOLAH = 'kepala_sekolah';

    public const GURU = 'guru';

    public const ORANG_TUA = 'orang_tua';

    /**
     * Additional operational roles
     */
    public const SISWA = 'siswa';

    /**
     * Role groups for easy access control
     */
    public const STAFF_ROLES = [
        self::ADMIN,
        self::KEPALA_SEKOLAH,
    ];

    public const TEACHER_ROLES = [
        self::GURU,
    ];

    public const ALL_ROLES = [
        self::ADMIN,
        self::KEPALA_SEKOLAH,
        self::GURU,
        self::ORANG_TUA,
        self::SISWA,
    ];

    /**
     * Get users with this role
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get permissions for this role
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permission')
            ->withTimestamps();
    }

    /**
     * Check if role has a specific permission
     */
    public function hasPermission(string $permission): bool
    {
        return $this->permissions->contains('name', $permission);
    }

    /**
     * Grant permission to role
     */
    public function grantPermission(Permission $permission): void
    {
        $this->permissions()->syncWithoutDetaching($permission);
    }

    /**
     * Revoke permission from role
     */
    public function revokePermission(Permission $permission): void
    {
        $this->permissions()->detach($permission);
    }

    /**
     * Scope for active roles
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if this is an admin role
     */
    public function isAdmin(): bool
    {
        return $this->name === self::ADMIN;
    }

    /**
     * Check if this is a staff role
     */
    public function isStaff(): bool
    {
        return in_array($this->name, self::STAFF_ROLES);
    }

    /**
     * Check if this is a teacher role
     */
    public function isTeacher(): bool
    {
        return in_array($this->name, self::TEACHER_ROLES);
    }

    /**
     * Check if this is a parent role
     */
    public function isParent(): bool
    {
        return $this->name === self::ORANG_TUA;
    }

    /**
     * Get display name for role
     */
    public static function getDisplayNames(): array
    {
        return [
            self::ADMIN => 'Administrator',
            self::KEPALA_SEKOLAH => 'Kepala Sekolah',
            self::GURU => 'Guru',
            self::ORANG_TUA => 'Orang Tua/Wali Murid',
            self::SISWA => 'Siswa',
        ];
    }
}
