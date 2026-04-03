<?php

namespace App\Livewire\Pages;

use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\Classroom;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\Letter;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    #[Computed]
    public function user(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }

    #[Computed]
    public function isAdmin(): bool
    {
        return $this->user->isAdmin();
    }

    #[Computed]
    public function isKepalaSekolah(): bool
    {
        return $this->user->hasRole(Role::KEPALA_SEKOLAH);
    }

    #[Computed]
    public function isGuru(): bool
    {
        return $this->user->hasRole(Role::GURU);
    }

    #[Computed]
    public function stats(): array
    {
        $stats = [];

        if ($this->isAdmin || $this->isKepalaSekolah) {
            $stats = [
                'students' => [
                    'total' => Student::count(),
                    'active' => Student::where('status', 'aktif')->count(),
                    'icon' => 'academic-cap',
                    'color' => 'blue',
                    'route' => $this->user->hasAnyPermission(['students.view']) ? route('students.index') : null,
                ],
                'employees' => [
                    'total' => Employee::count(),
                    'active' => Employee::where('is_active', true)->count(),
                    'icon' => 'users',
                    'color' => 'green',
                    'route' => $this->user->hasAnyPermission(['employees.view']) ? route('employees.index') : null,
                ],
                'classrooms' => [
                    'total' => Classroom::count(),
                    'active' => Classroom::where('is_active', true)->count(),
                    'icon' => 'building-office',
                    'color' => 'purple',
                    'route' => $this->user->hasAnyPermission(['master.view']) ? route('master.classrooms') : null,
                ],
                'users' => [
                    'total' => User::count(),
                    'active' => User::where('is_active', true)->count(),
                    'icon' => 'user-group',
                    'color' => 'amber',
                    'route' => $this->user->hasAnyPermission(['users.view']) ? route('admin.users') : null,
                ],
            ];
        } elseif ($this->isGuru) {
            $teacherClassrooms = $this->getTeacherClassrooms();
            $studentCount = Student::whereIn('classroom_id', $teacherClassrooms)
                ->where('status', 'aktif')
                ->count();

            $stats = [
                'my_students' => [
                    'total' => $studentCount,
                    'active' => $studentCount,
                    'icon' => 'academic-cap',
                    'color' => 'blue',
                    'route' => null,
                ],
                'my_classrooms' => [
                    'total' => count($teacherClassrooms),
                    'active' => count($teacherClassrooms),
                    'icon' => 'building-office',
                    'color' => 'purple',
                    'route' => null,
                ],
            ];
        }

        return $stats;
    }

    #[Computed]
    public function activeAcademicYear(): ?AcademicYear
    {
        return AcademicYear::where('is_active', true)->first();
    }

    #[Computed]
    public function recentStudents(): Collection
    {
        if (! $this->user->hasAnyPermission(['students.view']) && ! $this->user->isAdmin()) {
            return collect();
        }

        return Student::with(['classroom', 'department'])
            ->latest()
            ->take(5)
            ->get();
    }

    #[Computed]
    public function recentEmployees(): Collection
    {
        if (! $this->user->hasAnyPermission(['employees.view']) && ! $this->user->isAdmin()) {
            return collect();
        }

        return Employee::with('position')
            ->latest()
            ->take(5)
            ->get();
    }

    #[Computed]
    public function announcements(): Collection
    {
        return Announcement::query()
            ->active()
            ->published()
            ->visibleTo($this->user)
            ->orderBy('is_pinned', 'desc')
            ->orderBy('published_at', 'desc')
            ->take(5)
            ->get();
    }

    #[Computed]
    public function pendingLetters(): Collection
    {
        if (! $this->user->hasAnyPermission(['letters.approve'])) {
            return collect();
        }

        return Letter::where('status', 'pending')
            ->with('author')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
    }

    #[Computed]
    public function todayAttendanceSummary(): array
    {
        $today = now()->toDateString();

        return [
            'students' => [
                'present' => StudentAttendance::where('date', $today)->where('status', 'hadir')->count(),
                'absent' => StudentAttendance::where('date', $today)->whereIn('status', ['sakit', 'izin', 'alpha'])->count(),
                'total' => Student::where('status', 'aktif')->count(),
            ],
            'employees' => [
                'present' => EmployeeAttendance::where('date', $today)->where('status', 'hadir')->count(),
                'absent' => EmployeeAttendance::where('date', $today)->whereIn('status', ['sakit', 'izin', 'alpha'])->count(),
                'total' => Employee::where('is_active', true)->count(),
            ],
        ];
    }

    #[Computed]
    public function studentStatusChart(): array
    {
        $statuses = Student::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'labels' => array_map(fn ($status) => Student::STATUSES[$status] ?? $status, array_keys($statuses)),
            'data' => array_values($statuses),
        ];
    }

    #[Computed]
    public function employeeTypeChart(): array
    {
        $types = Employee::selectRaw('employee_type, count(*) as count')
            ->groupBy('employee_type')
            ->pluck('count', 'employee_type')
            ->toArray();

        return [
            'labels' => array_map(fn ($type) => Employee::TYPES[$type] ?? $type, array_keys($types)),
            'data' => array_values($types),
        ];
    }

    public function render(): View
    {
        return view('livewire.pages.dashboard');
    }

    private function getTeacherClassrooms(): array
    {
        if (! $this->user->employee_id) {
            return [];
        }

        return Classroom::where('homeroom_teacher_id', $this->user->id)
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();
    }
}
