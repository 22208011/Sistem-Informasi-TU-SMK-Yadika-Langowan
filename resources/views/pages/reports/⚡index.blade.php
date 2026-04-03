<?php

use App\Models\Student;
use App\Models\Employee;
use App\Models\Letter;
use App\Models\Classroom;
use App\Models\StudentAttendance;
use App\Models\EmployeeAttendance;

use App\Models\AcademicYear;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Ringkasan Laporan')] class extends Component {
    public function with(): array
    {
        $academicYear = AcademicYear::where('is_active', true)->first();
        $today = now()->toDateString();
        $thisMonth = now()->startOfMonth()->toDateString();
        $endOfMonth = now()->endOfMonth()->toDateString();

        // Student Statistics
        $totalStudents = Student::where('status', 'aktif')->count();
        $maleStudents = Student::where('status', 'aktif')->where('gender', 'L')->count();
        $femaleStudents = Student::where('status', 'aktif')->where('gender', 'P')->count();

        // Employee Statistics
        $totalEmployees = Employee::where('is_active', true)->count();
        $totalTeachers = Employee::where('is_active', true)
            ->where(fn($q) => $q->where('employee_type', 'guru')->orWhere('employee_type', 'keduanya'))
            ->count();

        // Classroom Statistics
        $totalClassrooms = Classroom::count();
        $studentsPerClass = $totalClassrooms > 0 ? round($totalStudents / $totalClassrooms, 1) : 0;

        // Attendance Statistics (Today)
        $studentAttendanceToday = StudentAttendance::whereDate('date', $today)->count();
        $studentPresentToday = StudentAttendance::whereDate('date', $today)->where('status', 'present')->count();

        $employeeAttendanceToday = EmployeeAttendance::whereDate('date', $today)->count();
        $employeePresentToday = EmployeeAttendance::whereDate('date', $today)->where('status', 'present')->count();

        // Monthly Attendance Summary
        $studentAttendanceMonth = StudentAttendance::whereBetween('date', [$thisMonth, $endOfMonth])
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Letter Statistics
        $pendingLetters = Letter::where('status', 'pending')->count();
        $totalLettersThisMonth = Letter::whereBetween('created_at', [$thisMonth, $endOfMonth])->count();



        // Students per Department
        $studentsPerDepartment = Classroom::withCount(['students' => fn($q) => $q->where('status', 'aktif')])
            ->with('department')
            ->get()
            ->groupBy('department_id')
            ->map(function ($classes) {
                $dept = $classes->first()->department;
                return [
                    'name' => $dept?->name ?? 'Tidak ada jurusan',
                    'total' => $classes->sum('students_count'),
                ];
            })
            ->values();

        return [
            'academicYear' => $academicYear,
            'totalStudents' => $totalStudents,
            'maleStudents' => $maleStudents,
            'femaleStudents' => $femaleStudents,
            'totalEmployees' => $totalEmployees,
            'totalTeachers' => $totalTeachers,
            'totalClassrooms' => $totalClassrooms,
            'studentsPerClass' => $studentsPerClass,
            'studentAttendanceToday' => $studentAttendanceToday,
            'studentPresentToday' => $studentPresentToday,
            'employeeAttendanceToday' => $employeeAttendanceToday,
            'employeePresentToday' => $employeePresentToday,
            'studentAttendanceMonth' => $studentAttendanceMonth,
            'pendingLetters' => $pendingLetters,
            'totalLettersThisMonth' => $totalLettersThisMonth,

            'studentsPerDepartment' => $studentsPerDepartment,
        ];
    }
}; ?>

<div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <flux:heading size="xl">Ringkasan Laporan</flux:heading>
                <flux:subheading>
                    @if ($academicYear)
                        Tahun Ajaran {{ $academicYear->name }}
                    @else
                        Belum ada tahun ajaran aktif
                    @endif
                    - {{ now()->format('d F Y') }}
                </flux:subheading>
            </div>
            @can('reports.export')
            <div class="flex gap-2">
                <flux:button icon="arrow-down-tray" variant="outline" :href="route('reports.export.summary')" target="_blank">
                    Export CSV
                </flux:button>
                <flux:button icon="printer" variant="outline" onclick="window.print()">
                    Print
                </flux:button>
            </div>
            @endcan
        </div>

        <!-- Quick Stats -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <flux:card class="border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-900/20">
                <div class="flex items-center gap-4">
                    <div class="rounded-full bg-blue-100 p-3 dark:bg-blue-900/50">
                        <flux:icon.users class="size-6 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div>
                        <p class="text-sm text-blue-600 dark:text-blue-400">Total Siswa</p>
                        <p class="text-2xl font-bold text-blue-700 dark:text-blue-300">{{ number_format($totalStudents) }}</p>
                        <p class="text-xs text-blue-500">L: {{ $maleStudents }} | P: {{ $femaleStudents }}</p>
                    </div>
                </div>
            </flux:card>

            <flux:card class="border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20">
                <div class="flex items-center gap-4">
                    <div class="rounded-full bg-green-100 p-3 dark:bg-green-900/50">
                        <flux:icon.user-group class="size-6 text-green-600 dark:text-green-400" />
                    </div>
                    <div>
                        <p class="text-sm text-green-600 dark:text-green-400">Tenaga Pendidik</p>
                        <p class="text-2xl font-bold text-green-700 dark:text-green-300">{{ number_format($totalTeachers) }}</p>
                        <p class="text-xs text-green-500">dari {{ $totalEmployees }} pegawai</p>
                    </div>
                </div>
            </flux:card>

            <flux:card class="border-purple-200 bg-purple-50 dark:border-purple-800 dark:bg-purple-900/20">
                <div class="flex items-center gap-4">
                    <div class="rounded-full bg-purple-100 p-3 dark:bg-purple-900/50">
                        <flux:icon.academic-cap class="size-6 text-purple-600 dark:text-purple-400" />
                    </div>
                    <div>
                        <p class="text-sm text-purple-600 dark:text-purple-400">Total Kelas</p>
                        <p class="text-2xl font-bold text-purple-700 dark:text-purple-300">{{ $totalClassrooms }}</p>
                        <p class="text-xs text-purple-500">~{{ $studentsPerClass }} siswa/kelas</p>
                    </div>
                </div>
            </flux:card>

            <flux:card class="border-orange-200 bg-orange-50 dark:border-orange-800 dark:bg-orange-900/20">
                <div class="flex items-center gap-4">
                    <div class="rounded-full bg-orange-100 p-3 dark:bg-orange-900/50">
                        <flux:icon.envelope class="size-6 text-orange-600 dark:text-orange-400" />
                    </div>
                    <div>
                        <p class="text-sm text-orange-600 dark:text-orange-400">Surat Pending</p>
                        <p class="text-2xl font-bold text-orange-700 dark:text-orange-300">{{ $pendingLetters }}</p>
                        <p class="text-xs text-orange-500">{{ $totalLettersThisMonth }} surat bulan ini</p>
                    </div>
                </div>
            </flux:card>
        </div>

        <!-- Today's Attendance -->
        <div class="grid gap-4 lg:grid-cols-2">
            <flux:card>
                <flux:heading size="sm" class="mb-4">
                    <flux:icon.clipboard-document-check class="mr-2 inline size-5" />
                    Kehadiran Siswa Hari Ini
                </flux:heading>
                @if ($studentAttendanceToday > 0)
                    @php
                        $studentAttendanceRate = round(($studentPresentToday / $studentAttendanceToday) * 100, 1);
                    @endphp
                    <div class="flex items-center gap-6">
                        <div class="relative size-24">
                            <svg class="size-full -rotate-90" viewBox="0 0 36 36">
                                <circle cx="18" cy="18" r="16" fill="none" stroke="currentColor" stroke-width="2" class="text-gray-200 dark:text-gray-700"></circle>
                                <circle cx="18" cy="18" r="16" fill="none" stroke="currentColor" stroke-width="2" stroke-dasharray="{{ $studentAttendanceRate }}, 100" class="text-green-500"></circle>
                            </svg>
                            <span class="absolute inset-0 flex items-center justify-center text-lg font-semibold text-green-600 dark:text-green-400">
                                {{ $studentAttendanceRate }}%
                            </span>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Hadir: <span class="font-semibold text-green-600">{{ $studentPresentToday }}</span> siswa</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Tercatat: <span class="font-semibold">{{ $studentAttendanceToday }}</span> dari {{ $totalStudents }} siswa</p>
                        </div>
                    </div>
                @else
                    <p class="text-sm text-gray-500">Belum ada data kehadiran hari ini.</p>
                @endif
            </flux:card>

            <flux:card>
                <flux:heading size="sm" class="mb-4">
                    <flux:icon.clipboard-document-check class="mr-2 inline size-5" />
                    Kehadiran Pegawai Hari Ini
                </flux:heading>
                @if ($employeeAttendanceToday > 0)
                    @php
                        $employeeAttendanceRate = round(($employeePresentToday / $employeeAttendanceToday) * 100, 1);
                    @endphp
                    <div class="flex items-center gap-6">
                        <div class="relative size-24">
                            <svg class="size-full -rotate-90" viewBox="0 0 36 36">
                                <circle cx="18" cy="18" r="16" fill="none" stroke="currentColor" stroke-width="2" class="text-gray-200 dark:text-gray-700"></circle>
                                <circle cx="18" cy="18" r="16" fill="none" stroke="currentColor" stroke-width="2" stroke-dasharray="{{ $employeeAttendanceRate }}, 100" class="text-blue-500"></circle>
                            </svg>
                            <span class="absolute inset-0 flex items-center justify-center text-lg font-semibold text-blue-600 dark:text-blue-400">
                                {{ $employeeAttendanceRate }}%
                            </span>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Hadir: <span class="font-semibold text-blue-600">{{ $employeePresentToday }}</span> pegawai</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Tercatat: <span class="font-semibold">{{ $employeeAttendanceToday }}</span> dari {{ $totalEmployees }} pegawai</p>
                        </div>
                    </div>
                @else
                    <p class="text-sm text-gray-500">Belum ada data kehadiran hari ini.</p>
                @endif
            </flux:card>
        </div>



        <!-- Students per Department -->
        <flux:card>
            <flux:heading size="sm" class="mb-4">
                <flux:icon.building-office class="mr-2 inline size-5" />
                Distribusi Siswa per Jurusan
            </flux:heading>
            @if ($studentsPerDepartment->count() > 0)
                <div class="space-y-4">
                    @foreach ($studentsPerDepartment as $dept)
                        @php
                            $percentage = $totalStudents > 0 ? ($dept['total'] / $totalStudents) * 100 : 0;
                        @endphp
                        <div>
                            <div class="mb-1 flex items-center justify-between text-sm">
                                <span class="font-medium text-gray-900 dark:text-white">{{ $dept['name'] }}</span>
                                <span class="text-gray-500">{{ $dept['total'] }} siswa ({{ number_format($percentage, 1) }}%)</span>
                            </div>
                            <div class="h-3 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                <div class="h-full rounded-full bg-blue-500" style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-500">Belum ada data jurusan.</p>
            @endif
        </flux:card>

        <!-- Quick Links -->
        <flux:card>
            <flux:heading size="sm" class="mb-4">Akses Cepat Laporan</flux:heading>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <flux:button icon="users" variant="outline" :href="route('reports.students')" wire:navigate class="justify-start">
                    Laporan Siswa
                </flux:button>
                <flux:button icon="user-group" variant="outline" :href="route('reports.employees')" wire:navigate class="justify-start">
                    Laporan Pegawai
                </flux:button>
                <flux:button icon="clipboard-document-check" variant="outline" :href="route('reports.attendance')" wire:navigate class="justify-start">
                    Laporan Kehadiran
                </flux:button>
            </div>
    </flux:card>
</div>
