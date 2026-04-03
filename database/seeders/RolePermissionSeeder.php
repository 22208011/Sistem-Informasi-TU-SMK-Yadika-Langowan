<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Roles - 4 Main User Types + Additional Operational Roles
        $roles = [
            // 1. Admin/Operator Sekolah
            [
                'name' => Role::ADMIN,
                'display_name' => 'Administrator',
                'description' => 'Administrator sistem dengan akses penuh ke semua fitur dan data',
            ],
            // 2. Kepala Sekolah
            [
                'name' => Role::KEPALA_SEKOLAH,
                'display_name' => 'Kepala Sekolah',
                'description' => 'Akses monitoring, laporan keseluruhan operasional, dan persetujuan',
            ],
            // 3. Guru
            [
                'name' => Role::GURU,
                'display_name' => 'Guru',
                'description' => 'Akses terbatas ke fitur akademik dan pembelajaran',
            ],
            // 4. Orang Tua/Wali Murid
            [
                'name' => Role::ORANG_TUA,
                'display_name' => 'Orang Tua/Wali Murid',
                'description' => 'Akses sangat terbatas untuk notifikasi dan laporan nilai anak',
            ],
            // Siswa
            [
                'name' => Role::SISWA,
                'display_name' => 'Siswa',
                'description' => 'Siswa dengan akses portal siswa, permohonan surat, dan pengumuman',
            ],
        ];

        foreach ($roles as $roleData) {
            Role::firstOrCreate(['name' => $roleData['name']], $roleData);
        }

        $this->removeLegacyRoles();

        // Create All Permissions
        $permissions = $this->getAllPermissions();

        foreach ($permissions as $permissionData) {
            Permission::firstOrCreate(['name' => $permissionData['name']], $permissionData);
        }

        // Assign permissions to roles
        $this->assignPermissionsToRoles();
    }

    /**
     * Get all permissions for the system
     */
    private function getAllPermissions(): array
    {
        return [
            // Dashboard Module
            ['name' => 'dashboard.admin', 'display_name' => 'Dashboard Admin', 'module' => Permission::MODULE_DASHBOARD],
            ['name' => 'dashboard.kepala_sekolah', 'display_name' => 'Dashboard Kepala Sekolah', 'module' => Permission::MODULE_DASHBOARD],
            ['name' => 'dashboard.guru', 'display_name' => 'Dashboard Guru', 'module' => Permission::MODULE_DASHBOARD],
            ['name' => 'dashboard.orang_tua', 'display_name' => 'Dashboard Orang Tua', 'module' => Permission::MODULE_DASHBOARD],

            // Users Module
            ['name' => 'users.view', 'display_name' => 'Lihat User', 'module' => Permission::MODULE_USERS],
            ['name' => 'users.create', 'display_name' => 'Buat User', 'module' => Permission::MODULE_USERS],
            ['name' => 'users.edit', 'display_name' => 'Edit User', 'module' => Permission::MODULE_USERS],
            ['name' => 'users.delete', 'display_name' => 'Hapus User', 'module' => Permission::MODULE_USERS],

            // Roles Module
            ['name' => 'roles.view', 'display_name' => 'Lihat Role', 'module' => Permission::MODULE_ROLES],
            ['name' => 'roles.create', 'display_name' => 'Buat Role', 'module' => Permission::MODULE_ROLES],
            ['name' => 'roles.edit', 'display_name' => 'Edit Role', 'module' => Permission::MODULE_ROLES],
            ['name' => 'roles.delete', 'display_name' => 'Hapus Role', 'module' => Permission::MODULE_ROLES],

            // Master Data Module
            ['name' => 'master.view', 'display_name' => 'Lihat Master Data', 'module' => Permission::MODULE_MASTER],
            ['name' => 'master.create', 'display_name' => 'Buat Master Data', 'module' => Permission::MODULE_MASTER],
            ['name' => 'master.edit', 'display_name' => 'Edit Master Data', 'module' => Permission::MODULE_MASTER],
            ['name' => 'master.delete', 'display_name' => 'Hapus Master Data', 'module' => Permission::MODULE_MASTER],

            // Students Module
            ['name' => 'students.view', 'display_name' => 'Lihat Siswa', 'module' => Permission::MODULE_STUDENTS],
            ['name' => 'students.view_own', 'display_name' => 'Lihat Data Anak Sendiri', 'module' => Permission::MODULE_STUDENTS],
            ['name' => 'students.view_class', 'display_name' => 'Lihat Siswa di Kelas', 'module' => Permission::MODULE_STUDENTS],
            ['name' => 'students.create', 'display_name' => 'Buat Siswa', 'module' => Permission::MODULE_STUDENTS],
            ['name' => 'students.edit', 'display_name' => 'Edit Siswa', 'module' => Permission::MODULE_STUDENTS],
            ['name' => 'students.delete', 'display_name' => 'Hapus Siswa', 'module' => Permission::MODULE_STUDENTS],

            // Employees Module
            ['name' => 'employees.view', 'display_name' => 'Lihat Pegawai', 'module' => Permission::MODULE_EMPLOYEES],
            ['name' => 'employees.create', 'display_name' => 'Buat Pegawai', 'module' => Permission::MODULE_EMPLOYEES],
            ['name' => 'employees.edit', 'display_name' => 'Edit Pegawai', 'module' => Permission::MODULE_EMPLOYEES],
            ['name' => 'employees.delete', 'display_name' => 'Hapus Pegawai', 'module' => Permission::MODULE_EMPLOYEES],

            // Attendance Module
            ['name' => 'attendance.view', 'display_name' => 'Lihat Kehadiran', 'module' => Permission::MODULE_ATTENDANCE],
            ['name' => 'attendance.view_summary', 'display_name' => 'Lihat Rekap Kehadiran', 'module' => Permission::MODULE_ATTENDANCE],
            ['name' => 'attendance.create', 'display_name' => 'Input Kehadiran', 'module' => Permission::MODULE_ATTENDANCE],
            ['name' => 'attendance.edit', 'display_name' => 'Edit Kehadiran', 'module' => Permission::MODULE_ATTENDANCE],
            ['name' => 'attendance.delete', 'display_name' => 'Hapus Kehadiran', 'module' => Permission::MODULE_ATTENDANCE],

            // Subjects Module
            ['name' => 'subjects.view', 'display_name' => 'Lihat Mata Pelajaran', 'module' => Permission::MODULE_SUBJECTS],
            ['name' => 'subjects.create', 'display_name' => 'Buat Mata Pelajaran', 'module' => Permission::MODULE_SUBJECTS],
            ['name' => 'subjects.edit', 'display_name' => 'Edit Mata Pelajaran', 'module' => Permission::MODULE_SUBJECTS],
            ['name' => 'subjects.delete', 'display_name' => 'Hapus Mata Pelajaran', 'module' => Permission::MODULE_SUBJECTS],

            // Schedule Module
            ['name' => 'schedule.view', 'display_name' => 'Lihat Jadwal', 'module' => Permission::MODULE_SCHEDULE],
            ['name' => 'schedule.view_own', 'display_name' => 'Lihat Jadwal Mengajar Sendiri', 'module' => Permission::MODULE_SCHEDULE],
            ['name' => 'schedule.create', 'display_name' => 'Buat Jadwal', 'module' => Permission::MODULE_SCHEDULE],
            ['name' => 'schedule.edit', 'display_name' => 'Edit Jadwal', 'module' => Permission::MODULE_SCHEDULE],
            ['name' => 'schedule.delete', 'display_name' => 'Hapus Jadwal', 'module' => Permission::MODULE_SCHEDULE],

            // Grades Module
            ['name' => 'grades.view', 'display_name' => 'Lihat Nilai', 'module' => Permission::MODULE_GRADES],
            ['name' => 'grades.view_own', 'display_name' => 'Lihat Nilai Anak Sendiri', 'module' => Permission::MODULE_GRADES],
            ['name' => 'grades.view_class', 'display_name' => 'Lihat Nilai Kelas', 'module' => Permission::MODULE_GRADES],
            ['name' => 'grades.create', 'display_name' => 'Input Nilai', 'module' => Permission::MODULE_GRADES],
            ['name' => 'grades.edit', 'display_name' => 'Edit Nilai', 'module' => Permission::MODULE_GRADES],
            ['name' => 'grades.delete', 'display_name' => 'Hapus Nilai', 'module' => Permission::MODULE_GRADES],

            // Exams Module
            ['name' => 'exams.view', 'display_name' => 'Lihat Ujian', 'module' => Permission::MODULE_EXAMS],
            ['name' => 'exams.create', 'display_name' => 'Buat Ujian', 'module' => Permission::MODULE_EXAMS],
            ['name' => 'exams.edit', 'display_name' => 'Edit Ujian', 'module' => Permission::MODULE_EXAMS],
            ['name' => 'exams.delete', 'display_name' => 'Hapus Ujian', 'module' => Permission::MODULE_EXAMS],
            ['name' => 'exams.input_scores', 'display_name' => 'Input Nilai Ujian', 'module' => Permission::MODULE_EXAMS],

            // Letters Module
            ['name' => 'letters.view', 'display_name' => 'Lihat Surat', 'module' => Permission::MODULE_LETTERS],
            ['name' => 'letters.view_own', 'display_name' => 'Lihat Surat Sendiri', 'module' => Permission::MODULE_LETTERS],
            ['name' => 'letters.create', 'display_name' => 'Buat Surat', 'module' => Permission::MODULE_LETTERS],
            ['name' => 'letters.edit', 'display_name' => 'Edit Surat', 'module' => Permission::MODULE_LETTERS],
            ['name' => 'letters.delete', 'display_name' => 'Hapus Surat', 'module' => Permission::MODULE_LETTERS],
            ['name' => 'letters.approve', 'display_name' => 'Setujui Surat', 'module' => Permission::MODULE_LETTERS],

            // Inventory Module
            ['name' => 'inventory.view', 'display_name' => 'Lihat Inventaris', 'module' => Permission::MODULE_INVENTORY],
            ['name' => 'inventory.create', 'display_name' => 'Buat Inventaris', 'module' => Permission::MODULE_INVENTORY],
            ['name' => 'inventory.edit', 'display_name' => 'Edit Inventaris', 'module' => Permission::MODULE_INVENTORY],
            ['name' => 'inventory.delete', 'display_name' => 'Hapus Inventaris', 'module' => Permission::MODULE_INVENTORY],
            ['name' => 'inventory.borrow', 'display_name' => 'Pinjam Barang', 'module' => Permission::MODULE_INVENTORY],
            ['name' => 'inventory.return', 'display_name' => 'Kembalikan Barang', 'module' => Permission::MODULE_INVENTORY],

            // Finance Module
            ['name' => 'finance.view', 'display_name' => 'Lihat Keuangan', 'module' => Permission::MODULE_FINANCE],
            ['name' => 'finance.create', 'display_name' => 'Input Keuangan', 'module' => Permission::MODULE_FINANCE],
            ['name' => 'finance.edit', 'display_name' => 'Edit Keuangan', 'module' => Permission::MODULE_FINANCE],
            ['name' => 'finance.delete', 'display_name' => 'Hapus Keuangan', 'module' => Permission::MODULE_FINANCE],
            ['name' => 'finance.approve', 'display_name' => 'Setujui Keuangan', 'module' => Permission::MODULE_FINANCE],

            // Announcements Module
            ['name' => 'announcements.view', 'display_name' => 'Lihat Pengumuman', 'module' => Permission::MODULE_ANNOUNCEMENTS],
            ['name' => 'announcements.create', 'display_name' => 'Buat Pengumuman', 'module' => Permission::MODULE_ANNOUNCEMENTS],
            ['name' => 'announcements.edit', 'display_name' => 'Edit Pengumuman', 'module' => Permission::MODULE_ANNOUNCEMENTS],
            ['name' => 'announcements.delete', 'display_name' => 'Hapus Pengumuman', 'module' => Permission::MODULE_ANNOUNCEMENTS],

            // Extracurriculars Module
            ['name' => 'extracurriculars.view', 'display_name' => 'Lihat Ekstrakurikuler', 'module' => 'extracurriculars'],
            ['name' => 'extracurriculars.create', 'display_name' => 'Buat Ekstrakurikuler', 'module' => 'extracurriculars'],
            ['name' => 'extracurriculars.update', 'display_name' => 'Edit Ekstrakurikuler', 'module' => 'extracurriculars'],
            ['name' => 'extracurriculars.delete', 'display_name' => 'Hapus Ekstrakurikuler', 'module' => 'extracurriculars'],

            // Calling Letters Module (SP1/SP2/SP3)
            ['name' => 'calling-letters.view', 'display_name' => 'Lihat Surat Panggilan', 'module' => 'calling-letters'],
            ['name' => 'calling-letters.create', 'display_name' => 'Buat Surat Panggilan', 'module' => 'calling-letters'],
            ['name' => 'calling-letters.update', 'display_name' => 'Edit Surat Panggilan', 'module' => 'calling-letters'],
            ['name' => 'calling-letters.delete', 'display_name' => 'Hapus Surat Panggilan', 'module' => 'calling-letters'],

            // Graduates Module
            ['name' => 'graduates.view', 'display_name' => 'Lihat Data Lulusan', 'module' => 'graduates'],
            ['name' => 'graduates.create', 'display_name' => 'Buat Data Lulusan', 'module' => 'graduates'],
            ['name' => 'graduates.update', 'display_name' => 'Edit Data Lulusan', 'module' => 'graduates'],
            ['name' => 'graduates.delete', 'display_name' => 'Hapus Data Lulusan', 'module' => 'graduates'],
            ['name' => 'graduates.issue_skl', 'display_name' => 'Terbitkan SKL', 'module' => 'graduates'],

            // Sumative Finals Module (PAS/PAT/US)
            ['name' => 'sumative-finals.view', 'display_name' => 'Lihat Nilai Sumatif', 'module' => 'sumative-finals'],
            ['name' => 'sumative-finals.create', 'display_name' => 'Buat Nilai Sumatif', 'module' => 'sumative-finals'],
            ['name' => 'sumative-finals.update', 'display_name' => 'Edit Nilai Sumatif', 'module' => 'sumative-finals'],
            ['name' => 'sumative-finals.delete', 'display_name' => 'Hapus Nilai Sumatif', 'module' => 'sumative-finals'],
            ['name' => 'sumative-finals.verify', 'display_name' => 'Verifikasi Nilai Sumatif', 'module' => 'sumative-finals'],
            ['name' => 'sumative-finals.approve', 'display_name' => 'Setujui Nilai Sumatif', 'module' => 'sumative-finals'],

            // Eligible Scores Module
            ['name' => 'eligible-scores.view', 'display_name' => 'Lihat Nilai Eligible', 'module' => 'eligible-scores'],
            ['name' => 'eligible-scores.create', 'display_name' => 'Buat Nilai Eligible', 'module' => 'eligible-scores'],
            ['name' => 'eligible-scores.update', 'display_name' => 'Edit Nilai Eligible', 'module' => 'eligible-scores'],
            ['name' => 'eligible-scores.delete', 'display_name' => 'Hapus Nilai Eligible', 'module' => 'eligible-scores'],

            // Audit Logs Module
            ['name' => 'audit-logs.view', 'display_name' => 'Lihat Audit Log', 'module' => 'audit-logs'],

            // Reports Module
            ['name' => 'reports.view', 'display_name' => 'Lihat Laporan', 'module' => Permission::MODULE_REPORTS],
            ['name' => 'reports.students', 'display_name' => 'Laporan Kesiswaan', 'module' => Permission::MODULE_REPORTS],
            ['name' => 'reports.employees', 'display_name' => 'Laporan Kepegawaian', 'module' => Permission::MODULE_REPORTS],
            ['name' => 'reports.finance', 'display_name' => 'Laporan Keuangan', 'module' => Permission::MODULE_REPORTS],
            ['name' => 'reports.export', 'display_name' => 'Export Laporan (PDF/Excel)', 'module' => Permission::MODULE_REPORTS],

            // Parent Portal Module
            ['name' => 'parent_portal.view_child', 'display_name' => 'Lihat Data Anak', 'module' => Permission::MODULE_PARENT_PORTAL],
            ['name' => 'parent_portal.view_grades', 'display_name' => 'Lihat Nilai Anak', 'module' => Permission::MODULE_PARENT_PORTAL],
            ['name' => 'parent_portal.view_attendance', 'display_name' => 'Lihat Kehadiran Anak', 'module' => Permission::MODULE_PARENT_PORTAL],
            ['name' => 'parent_portal.view_letters', 'display_name' => 'Lihat Surat Panggilan', 'module' => Permission::MODULE_PARENT_PORTAL],
            ['name' => 'parent_portal.view_announcements', 'display_name' => 'Lihat Pengumuman Sekolah', 'module' => Permission::MODULE_PARENT_PORTAL],

            // Student Portal Module
            ['name' => 'student_portal.dashboard', 'display_name' => 'Dashboard Siswa', 'module' => 'student-portal'],
            ['name' => 'student_portal.letter_requests', 'display_name' => 'Permohonan Surat Siswa', 'module' => 'student-portal'],
            ['name' => 'student_portal.announcements', 'display_name' => 'Lihat Pengumuman (Siswa)', 'module' => 'student-portal'],
        ];
    }

    /**
     * Assign permissions to roles based on stakeholder requirements
     */
    private function assignPermissionsToRoles(): void
    {
        // 1. ADMIN - Full access to everything
        $this->assignAdminPermissions();

        // 2. KEPALA SEKOLAH - Monitoring, reports, approvals
        $this->assignKepalaSekolahPermissions();

        // 3. GURU - Academic and learning features
        $this->assignGuruPermissions();

        // 4. ORANG TUA - Very limited, only child info
        $this->assignOrangTuaPermissions();

        // Additional: SISWA - Student portal
        $this->assignSiswaPermissions();
    }

    /**
     * Admin gets ALL permissions
     */
    private function assignAdminPermissions(): void
    {
        $admin = Role::where('name', Role::ADMIN)->first();
        $allPermissions = Permission::all();
        $admin->permissions()->sync($allPermissions->pluck('id'));
    }

    /**
     * Kepala Sekolah permissions:
     * - Dashboard khusus dengan laporan keseluruhan operasional
     * - Rekap akademik sekolah
     * - Rekap data kesiswaan
     * - Export laporan (PDF/Excel)
     * - Monitoring kehadiran guru dan siswa
     */
    private function assignKepalaSekolahPermissions(): void
    {
        $kepalaSekolah = Role::where('name', Role::KEPALA_SEKOLAH)->first();
        $permissions = Permission::whereIn('name', [
            // Dashboard
            'dashboard.kepala_sekolah',

            // View all data (monitoring)
            'students.view',
            'employees.view',
            'attendance.view',
            'attendance.view_summary',
            'subjects.view',
            'schedule.view',
            'grades.view',
            'exams.view',
            'letters.view',
            'inventory.view',
            'finance.view',
            'announcements.view',

            // Approval permissions
            'letters.approve',
            'finance.approve',

            // All reports
            'reports.view',
            'reports.students',
            'reports.employees',
            'reports.finance',
            'reports.export',
        ])->get();

        $kepalaSekolah->permissions()->sync($permissions->pluck('id'));
    }

    /**
     * Guru permissions:
     * - Input nilai akademik siswa
     * - Input data sumatif akhir (PAS/UAS)
     * - Input ujian sekolah
     * - Melihat jadwal mengajar
     * - Membuat dan mengirim surat panggilan orang tua siswa
     * - Laporan pembelajaran
     * - Melihat data siswa di kelas mereka
     */
    private function assignGuruPermissions(): void
    {
        $guru = Role::where('name', Role::GURU)->first();
        $permissions = Permission::whereIn('name', [
            // Dashboard
            'dashboard.guru',

            // Students (limited to their classes)
            'students.view_class',

            // Schedule (own schedule)
            'schedule.view_own',

            // Grades - full CRUD for their subjects
            'grades.view_class',
            'grades.create',
            'grades.edit',

            // Exams - create and input scores
            'exams.view',
            'exams.create',
            'exams.edit',
            'exams.input_scores',

            // Attendance for their classes
            'attendance.view',
            'attendance.create',
            'attendance.edit',

            // Calling letters for parents
            'calling-letters.view',
            'calling-letters.create',
            'calling-letters.update',

            // View reports
            'reports.view',
        ])->get();

        $guru->permissions()->sync($permissions->pluck('id'));
    }

    /**
     * Orang Tua permissions:
     * - Melihat nilai anak
     * - Melihat surat panggilan dari sekolah
     * - Melihat pengumuman sekolah
     * - Dashboard sederhana hanya untuk anak mereka
     */
    private function assignOrangTuaPermissions(): void
    {
        $orangTua = Role::where('name', Role::ORANG_TUA)->first();
        $permissions = Permission::whereIn('name', [
            // Dashboard
            'dashboard.orang_tua',

            // Parent Portal specific
            'parent_portal.view_child',
            'parent_portal.view_grades',
            'parent_portal.view_attendance',
            'parent_portal.view_letters',
            'parent_portal.view_announcements',

            // Limited view permissions
            'students.view_own',
            'grades.view_own',
            'letters.view_own',
            'announcements.view',
        ])->get();

        $orangTua->permissions()->sync($permissions->pluck('id'));
    }

    /**
     * Siswa permissions - access based on prompt: dashboard, akademik, kesiswaan, pengumuman, surat menyurat
     */
    private function assignSiswaPermissions(): void
    {
        $siswa = Role::where('name', Role::SISWA)->first();
        if (! $siswa) {
            return;
        }

        $permissions = Permission::whereIn('name', [
            // Dashboard
            'student_portal.dashboard',

            // Master data (view only)
            'master.view',

            // Akademik (view only)
            'subjects.view',
            'schedule.view_own',
            'extracurriculars.view',

            // Inventaris (view only)
            'inventory.view',

            // Pengumuman
            'announcements.view',
            'student_portal.announcements',

            // Surat menyurat
            'student_portal.letter_requests',
        ])->get();

        $siswa->permissions()->sync($permissions->pluck('id'));
    }

    /**
     * Remove legacy roles (tata_usaha, wali_kelas) and reassign existing users.
     */
    private function removeLegacyRoles(): void
    {
        $legacyRoles = Role::whereIn('name', ['tata_usaha', 'wali_kelas'])->get(['id']);

        if ($legacyRoles->isEmpty()) {
            return;
        }

        $fallbackRole = Role::where('name', Role::GURU)->first()
            ?? Role::where('name', Role::ADMIN)->first();

        if ($fallbackRole) {
            User::whereIn('role_id', $legacyRoles->pluck('id'))->update([
                'role_id' => $fallbackRole->id,
            ]);
        }

        Role::whereIn('id', $legacyRoles->pluck('id'))->get()->each(function (Role $role): void {
            $role->permissions()->detach();
        });

        Role::whereIn('id', $legacyRoles->pluck('id'))->delete();
    }
}
