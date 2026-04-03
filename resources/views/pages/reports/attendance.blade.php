<?php

use App\Models\StudentAttendance;
use App\Models\EmployeeAttendance;
use App\Models\Student;
use App\Models\Employee;
use App\Models\Classroom;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('layouts.app')] #[Title('Laporan Kehadiran')] class extends Component {
    public $month;
    public $year;
    public $type = 'student'; // student or employee

    public function mount()
    {
        $this->month = now()->month;
        $this->year = now()->year;
    }

    public function with(): array
    {
        $startDate = \Carbon\Carbon::create($this->year, $this->month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        $workingDays = $this->getWorkingDays($startDate, $endDate);

        if ($this->type === 'student') {
            $attendance = StudentAttendance::whereBetween('date', [$startDate, $endDate])
                ->selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            $totalStudents = Student::where('status', 'aktif')->count();
            $expectedRecords = $totalStudents * $workingDays;

            // Per Classroom
            $perClassroom = Classroom::withCount(['students as present_count' => function ($q) use ($startDate, $endDate) {
                $q->join('student_attendances', 'students.id', '=', 'student_attendances.student_id')
                    ->whereBetween('student_attendances.date', [$startDate, $endDate])
                    ->where('student_attendances.status', 'present');
            }])->get();
        } else {
            $attendance = EmployeeAttendance::whereBetween('date', [$startDate, $endDate])
                ->selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            $totalEmployees = Employee::where('is_active', true)->count();
            $expectedRecords = $totalEmployees * $workingDays;
            $perClassroom = collect();
        }

        $present = $attendance['present'] ?? 0;
        $late = $attendance['late'] ?? 0;
        $sick = $attendance['sick'] ?? 0;
        $excused = $attendance['excused'] ?? 0;
        $absent = $attendance['absent'] ?? 0;
        $total = array_sum($attendance);

        $attendanceRate = $total > 0 ? (($present + $late) / $total) * 100 : 0;

        return [
            'present' => $present,
            'late' => $late,
            'sick' => $sick,
            'excused' => $excused,
            'absent' => $absent,
            'total' => $total,
            'expectedRecords' => $expectedRecords,
            'attendanceRate' => $attendanceRate,
            'workingDays' => $workingDays,
            'perClassroom' => $perClassroom,
            'months' => [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
            ],
        ];
    }

    private function getWorkingDays($start, $end)
    {
        $count = 0;
        $current = $start->copy();
        while ($current <= $end) {
            if ($current->isWeekday()) {
                $count++;
            }
            $current->addDay();
        }
        return $count;
    }
}; ?>

<div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-4">
                <flux:button icon="arrow-left" variant="ghost" :href="route('reports.index')" wire:navigate />
                <div>
                    <flux:heading size="xl">Laporan Kehadiran</flux:heading>
                    <flux:subheading>Statistik kehadiran siswa dan pegawai</flux:subheading>
                </div>
            </div>
            @can('reports.export')
            <div class="flex gap-2">
                <flux:dropdown>
                    <flux:button icon="arrow-down-tray" variant="primary">Ekspor Laporan</flux:button>
                    <flux:menu>
                        <flux:menu.item icon="document-text" :href="route('reports.export.attendance', ['format' => 'pdf', 'type' => $type, 'month' => $month, 'year' => $year])" target="_blank">Laporan PDF</flux:menu.item>
                        <flux:menu.item icon="document" :href="route('reports.export.attendance', ['format' => 'word', 'type' => $type, 'month' => $month, 'year' => $year])" target="_blank">Microsoft Word (.doc)</flux:menu.item>
                        <flux:menu.item icon="table-cells" :href="route('reports.export.attendance', ['format' => 'excel', 'type' => $type, 'month' => $month, 'year' => $year])" target="_blank">Microsoft Excel (.xls)</flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
            </div>
            @endcan
        </div>

        <!-- Filters -->
        <flux:card>
            <div class="grid gap-4 sm:grid-cols-3">
                <flux:select wire:model.live="type">
                    <option value="student">Kehadiran Siswa</option>
                    <option value="employee">Kehadiran Pegawai</option>
                </flux:select>
                <flux:select wire:model.live="month">
                    @foreach ($months as $num => $name)
                        <option value="{{ $num }}">{{ $name }}</option>
                    @endforeach
                </flux:select>
                <flux:select wire:model.live="year">
                    @for ($y = now()->year; $y >= now()->year - 3; $y--)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endfor
                </flux:select>
            </div>
        </flux:card>

        <!-- Summary -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-6">
            <flux:card class="border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20">
                <div class="text-center">
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($present) }}</p>
                    <p class="text-sm text-green-700 dark:text-green-300">Hadir</p>
                </div>
            </flux:card>

            <flux:card class="border-yellow-200 bg-yellow-50 dark:border-yellow-800 dark:bg-yellow-900/20">
                <div class="text-center">
                    <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ number_format($late) }}</p>
                    <p class="text-sm text-yellow-700 dark:text-yellow-300">Terlambat</p>
                </div>
            </flux:card>

            <flux:card class="border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-900/20">
                <div class="text-center">
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($sick) }}</p>
                    <p class="text-sm text-blue-700 dark:text-blue-300">Sakit</p>
                </div>
            </flux:card>

            <flux:card class="border-purple-200 bg-purple-50 dark:border-purple-800 dark:bg-purple-900/20">
                <div class="text-center">
                    <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($excused) }}</p>
                    <p class="text-sm text-purple-700 dark:text-purple-300">Izin</p>
                </div>
            </flux:card>

            <flux:card class="border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20">
                <div class="text-center">
                    <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ number_format($absent) }}</p>
                    <p class="text-sm text-red-700 dark:text-red-300">Tanpa Keterangan</p>
                </div>
            </flux:card>

            <flux:card class="border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800">
                <div class="text-center">
                    <p class="text-2xl font-bold text-gray-600 dark:text-gray-400">{{ number_format($total) }}</p>
                    <p class="text-sm text-gray-700 dark:text-gray-300">Total Tercatat</p>
                </div>
            </flux:card>
        </div>

        <!-- Attendance Rate -->
        <flux:card>
            <flux:heading size="sm" class="mb-4">Tingkat Kehadiran - {{ $months[$month] }} {{ $year }}</flux:heading>
            <div class="flex items-center gap-6">
                <div class="relative size-32">
                    @php
                        $rateColor = $attendanceRate >= 90 ? 'green' : ($attendanceRate >= 75 ? 'yellow' : 'red');
                    @endphp
                    <svg class="size-full -rotate-90" viewBox="0 0 36 36">
                        <circle cx="18" cy="18" r="16" fill="none" stroke="currentColor" stroke-width="3" class="text-gray-200 dark:text-gray-700"></circle>
                        <circle cx="18" cy="18" r="16" fill="none" stroke="currentColor" stroke-width="3" stroke-dasharray="{{ $attendanceRate }}, 100" class="text-{{ $rateColor }}-500"></circle>
                    </svg>
                    <span class="absolute inset-0 flex items-center justify-center text-xl font-bold text-{{ $rateColor }}-600 dark:text-{{ $rateColor }}-400">
                        {{ number_format($attendanceRate, 1) }}%
                    </span>
                </div>
                <div class="flex-1">
                    <p class="text-lg font-semibold text-gray-900 dark:text-white">
                        @if ($attendanceRate >= 90)
                            Tingkat Kehadiran Sangat Baik
                        @elseif ($attendanceRate >= 75)
                            Tingkat Kehadiran Cukup Baik
                        @else
                            Tingkat Kehadiran Perlu Perhatian
                        @endif
                    </p>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Total hari kerja: {{ $workingDays }} hari<br>
                        Total kehadiran tercatat: {{ $total }} dari {{ $expectedRecords }} yang diharapkan
                    </p>
                </div>
            </div>
        </flux:card>

        <!-- Distribution Chart -->
        <flux:card>
            <flux:heading size="sm" class="mb-4">Distribusi Status Kehadiran</flux:heading>
            @if ($total > 0)
                <div class="space-y-4">
                    @php
                        $statuses = [
                            ['label' => 'Hadir', 'count' => $present, 'color' => 'green'],
                            ['label' => 'Terlambat', 'count' => $late, 'color' => 'yellow'],
                            ['label' => 'Sakit', 'count' => $sick, 'color' => 'blue'],
                            ['label' => 'Izin', 'count' => $excused, 'color' => 'purple'],
                            ['label' => 'Tanpa Keterangan', 'count' => $absent, 'color' => 'red'],
                        ];
                    @endphp
                    @foreach ($statuses as $status)
                        @php
                            $percentage = $total > 0 ? ($status['count'] / $total) * 100 : 0;
                        @endphp
                        <div>
                            <div class="mb-1 flex items-center justify-between text-sm">
                                <span class="font-medium text-gray-900 dark:text-white">{{ $status['label'] }}</span>
                                <span class="text-gray-500">{{ $status['count'] }} ({{ number_format($percentage, 1) }}%)</span>
                            </div>
                            <div class="h-3 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                <div class="h-full rounded-full bg-{{ $status['color'] }}-500" style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500">Belum ada data kehadiran untuk periode ini.</p>
            @endif
        </flux:card>

        <!-- Per Classroom (Students only) -->
        @if ($type === 'student' && $perClassroom->count() > 0)
        <flux:card>
            <flux:heading size="sm" class="mb-4">Kehadiran per Kelas</flux:heading>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($perClassroom->sortByDesc('present_count')->take(12) as $class)
                    <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <span class="font-medium text-gray-900 dark:text-white">{{ $class->name }}</span>
                            <flux:badge color="green" size="sm">{{ $class->present_count }} hadir</flux:badge>
                        </div>
                    </div>
                @endforeach
            </div>
        </flux:card>
        @endif
    </div>
</div>
