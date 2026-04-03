<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'module',
        'description',
    ];

    /**
     * Module constants
     */
    public const MODULE_USERS = 'users';
    public const MODULE_ROLES = 'roles';
    public const MODULE_MASTER = 'master';
    public const MODULE_STUDENTS = 'students';
    public const MODULE_EMPLOYEES = 'employees';
    public const MODULE_ATTENDANCE = 'attendance';
    public const MODULE_LETTERS = 'letters';
    public const MODULE_INVENTORY = 'inventory';
    public const MODULE_FINANCE = 'finance';
    public const MODULE_REPORTS = 'reports';

    // Academic modules
    public const MODULE_ACADEMIC = 'academic';
    public const MODULE_GRADES = 'grades';
    public const MODULE_EXAMS = 'exams';
    public const MODULE_SUBJECTS = 'subjects';
    public const MODULE_SCHEDULE = 'schedule';

    // Communication modules
    public const MODULE_ANNOUNCEMENTS = 'announcements';
    public const MODULE_NOTIFICATIONS = 'notifications';

    // Parent portal modules
    public const MODULE_PARENT_PORTAL = 'parent_portal';

    // Dashboard modules
    public const MODULE_DASHBOARD = 'dashboard';

    /**
     * All available modules for UI grouping
     */
    public const ALL_MODULES = [
        self::MODULE_DASHBOARD => 'Dashboard',
        self::MODULE_USERS => 'Manajemen User',
        self::MODULE_ROLES => 'Manajemen Role',
        self::MODULE_MASTER => 'Master Data',
        self::MODULE_STUDENTS => 'Kesiswaan',
        self::MODULE_EMPLOYEES => 'Kepegawaian',
        self::MODULE_ATTENDANCE => 'Kehadiran',
        self::MODULE_ACADEMIC => 'Akademik',
        self::MODULE_SUBJECTS => 'Mata Pelajaran',
        self::MODULE_GRADES => 'Nilai',
        self::MODULE_EXAMS => 'Ujian',
        self::MODULE_SCHEDULE => 'Jadwal',
        self::MODULE_LETTERS => 'Surat Menyurat',
        self::MODULE_INVENTORY => 'Inventaris',
        self::MODULE_FINANCE => 'Keuangan',
        self::MODULE_ANNOUNCEMENTS => 'Pengumuman',
        self::MODULE_NOTIFICATIONS => 'Notifikasi',
        self::MODULE_PARENT_PORTAL => 'Portal Orang Tua',
        self::MODULE_REPORTS => 'Laporan',
    ];

    /**
     * Get roles that have this permission
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permission')
            ->withTimestamps();
    }

    /**
     * Scope by module
     */
    public function scopeByModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    /**
     * Get grouped permissions by module
     */
    public static function getGroupedByModule(): array
    {
        return static::all()
            ->groupBy('module')
            ->toArray();
    }
}
