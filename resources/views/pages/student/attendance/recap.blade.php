<?php

use App\Models\Classroom;
use App\Models\Student;
use App\Models\StudentAttendance;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Rekap Kehadiran Siswa')] class extends Component {
    public int $selectedYear;
    public int $selectedMonth;
    public ?int $selectedClassroom = null;

    public function mount(): void
    {
        $this->selectedYear = now()->year;
        $this->selectedMonth = now()->month;

        // Auto-select first classroom
        $firstClassroom = Classroom::active()->first();
        if ($firstClassroom) {
            $this->selectedClassroom = $firstClassroom->id;
        }
    }

    #[Computed]
    public function classrooms()
    {
        return Classroom::active()->orderBy('name')->get();
    }

    #[Computed]
    public function students()
    {
        if (!$this->selectedClassroom) {
            return collect();
        }

        return Student::query()
            ->active()
            ->where('classroom_id', $this->selectedClassroom)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function daysInMonth(): int
    {
        return Carbon::create($this->selectedYear, $this->selectedMonth)->daysInMonth;
    }

    #[Computed]
    public function attendanceData(): array
    {
        if (!$this->selectedClassroom || $this->students->isEmpty()) {
            return [];
        }

        $startDate = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $attendances = StudentAttendance::query()
            ->whereIn('student_id', $this->students->pluck('id'))
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy('student_id');

        $data = [];
        foreach ($this->students as $student) {
            $studentAttendances = $attendances->get($student->id, collect());
            $dailyData = [];
            $summary = [
                'hadir' => 0,
                'izin' => 0,
                'sakit' => 0,
                'alpha' => 0,
            ];

            for ($day = 1; $day <= $this->daysInMonth; $day++) {
                $date = Carbon::create($this->selectedYear, $this->selectedMonth, $day);
                $attendance = $studentAttendances->firstWhere('date', $date->toDateString());

                $status = $attendance?->status ?? null;
                $dailyData[$day] = [
                    'status' => $status,
                    'is_weekend' => $date->isWeekend(),
                ];

                if ($status && isset($summary[$status])) {
                    $summary[$status]++;
                }
            }

            $data[$student->id] = [
                'student' => $student,
                'daily' => $dailyData,
                'summary' => $summary,
            ];
        }

        return $data;
    }

    #[Computed]
    public function monthlyStatistics(): array
    {
        if (!$this->selectedClassroom) {
            return [
                'total_students' => 0,
                'working_days' => 0,
                'hadir' => 0,
                'izin' => 0,
                'sakit' => 0,
                'alpha' => 0,
                'attendance_rate' => 0,
            ];
        }

        $startDate = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $attendances = StudentAttendance::query()
            ->where('classroom_id', $this->selectedClassroom)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $workingDays = 0;
        for ($day = 1; $day <= $this->daysInMonth; $day++) {
            $date = Carbon::create($this->selectedYear, $this->selectedMonth, $day);
            if (!$date->isWeekend()) {
                $workingDays++;
            }
        }

        $totalStudents = $this->students->count();

        return [
            'total_students' => $totalStudents,
            'working_days' => $workingDays,
            'hadir' => $attendances->where('status', 'hadir')->count(),
            'izin' => $attendances->where('status', 'izin')->count(),
            'sakit' => $attendances->where('status', 'sakit')->count(),
            'alpha' => $attendances->where('status', 'alpha')->count(),
            'attendance_rate' => $workingDays > 0 && $totalStudents > 0
                ? round(($attendances->where('status', 'hadir')->count() / ($workingDays * $totalStudents)) * 100, 1)
                : 0,
        ];
    }

    #[Computed]
    public function years(): array
    {
        $currentYear = now()->year;
        return range($currentYear - 2, $currentYear + 1);
    }

    #[Computed]
    public function months(): array
    {
        return [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];
    }

    public function previousMonth(): void
    {
        $date = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->subMonth();
        $this->selectedYear = $date->year;
        $this->selectedMonth = $date->month;
    }

    public function nextMonth(): void
    {
        $date = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->addMonth();
        $this->selectedYear = $date->year;
        $this->selectedMonth = $date->month;
    }

    public function getStatusSymbol(string $status): string
    {
        return match ($status) {
            'hadir' => 'H',
            'izin' => 'I',
            'sakit' => 'S',
            'alpha' => 'A',
            default => '-',
        };
    }
}; ?>

<div>
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <flux:button :href="route('students.attendance.index')" variant="ghost" icon="arrow-left" wire:navigate class="mb-4">
                {{ __('Kembali ke Input Harian') }}
            </flux:button>

            <flux:heading size="xl">{{ __('Rekap Kehadiran Siswa') }}</flux:heading>
            <flux:text class="mt-2">{{ __('Ringkasan kehadiran siswa per kelas per bulan.') }}</flux:text>
        </div>
    </div>

    <!-- Month & Class Selection -->
    <flux:card class="mb-6">
        <flux:card.body>
            <div class="flex flex-col md:flex-row md:items-center gap-4">
                <div class="flex items-center gap-2">
                    <flux:button wire:click="previousMonth" variant="ghost" icon="chevron-left" size="sm" />

                    <flux:select wire:model.live="selectedMonth" class="w-auto">
                        @foreach ($this->months as $num => $name)
                            <option value="{{ $num }}">{{ $name }}</option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model.live="selectedYear" class="w-auto">
                        @foreach ($this->years as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </flux:select>

                    <flux:button wire:click="nextMonth" variant="ghost" icon="chevron-right" size="sm" />
                </div>

                <div class="flex-1">
                    <flux:select wire:model.live="selectedClassroom" class="w-full md:max-w-xs">
                        <option value="">-- Pilih Kelas --</option>
                        @foreach ($this->classrooms as $classroom)
                            <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:text class="font-medium">
                    {{ $this->months[$selectedMonth] }} {{ $selectedYear }}
                </flux:text>
            </div>
        </flux:card.body>
    </flux:card>

    @if ($selectedClassroom)
        <!-- Statistics -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
            <flux:card class="p-4">
                <flux:text class="text-xs text-zinc-500">Total Siswa</flux:text>
                <flux:heading size="lg">{{ $this->monthlyStatistics['total_students'] }}</flux:heading>
            </flux:card>
            <flux:card class="p-4">
                <flux:text class="text-xs text-zinc-500">Hari Efektif</flux:text>
                <flux:heading size="lg">{{ $this->monthlyStatistics['working_days'] }}</flux:heading>
            </flux:card>
            <flux:card class="p-4">
                <flux:text class="text-xs text-zinc-500">Total Hadir</flux:text>
                <flux:heading size="lg">{{ $this->monthlyStatistics['hadir'] }}</flux:heading>
            </flux:card>
            <flux:card class="p-4">
                <flux:text class="text-xs text-zinc-500">Total Absen</flux:text>
                <flux:heading size="lg">{{ $this->monthlyStatistics['izin'] + $this->monthlyStatistics['sakit'] + $this->monthlyStatistics['alpha'] }}</flux:heading>
            </flux:card>
            <flux:card class="p-4 border-green-200 dark:border-green-800">
                <flux:text class="text-xs text-green-600">% Kehadiran</flux:text>
                <flux:heading size="lg" class="text-green-600">{{ $this->monthlyStatistics['attendance_rate'] }}%</flux:heading>
            </flux:card>
        </div>

        <!-- Legend -->
        <flux:card class="mb-6">
            <flux:card.body>
                <div class="flex flex-wrap gap-4">
                    <flux:text class="font-medium">Keterangan:</flux:text>
                    @foreach (App\Models\StudentAttendance::STATUSES as $key => $label)
                        <div class="flex items-center gap-1">
                            <flux:badge size="sm" color="{{ App\Models\StudentAttendance::STATUS_COLORS[$key] }}">
                                {{ $this->getStatusSymbol($key) }}
                            </flux:badge>
                            <flux:text size="sm">{{ $label }}</flux:text>
                        </div>
                    @endforeach
                    <div class="flex items-center gap-1">
                        <span class="w-5 h-5 bg-zinc-100 dark:bg-zinc-800 rounded text-center text-xs leading-5">-</span>
                        <flux:text size="sm">Belum Tercatat</flux:text>
                    </div>
                </div>
            </flux:card.body>
        </flux:card>

        <!-- Attendance Table -->
        <flux:card>
            <flux:card.body class="p-0 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                        <tr>
                            <th class="px-2 py-3 text-left font-medium text-zinc-900 dark:text-zinc-100 sticky left-0 bg-zinc-50 dark:bg-zinc-800 z-10 min-w-[40px]">
                                {{ __('No') }}
                            </th>
                            <th class="px-4 py-3 text-left font-medium text-zinc-900 dark:text-zinc-100 sticky left-10 bg-zinc-50 dark:bg-zinc-800 z-10 min-w-[180px]">
                                {{ __('Nama Siswa') }}
                            </th>
                            @for ($day = 1; $day <= $this->daysInMonth; $day++)
                                @php
                                    $date = \Carbon\Carbon::create($selectedYear, $selectedMonth, $day);
                                    $isWeekend = $date->isWeekend();
                                @endphp
                                <th class="px-1 py-3 text-center font-medium min-w-[28px] {{ $isWeekend ? 'bg-zinc-200 dark:bg-zinc-700' : '' }}">
                                    <span class="text-xs {{ $isWeekend ? 'text-red-500' : 'text-zinc-900 dark:text-zinc-100' }}">{{ $day }}</span>
                                </th>
                            @endfor
                            <th class="px-2 py-3 text-center font-medium text-zinc-900 dark:text-zinc-100 bg-green-50 dark:bg-green-900/30">H</th>
                            <th class="px-2 py-3 text-center font-medium text-zinc-900 dark:text-zinc-100 bg-blue-50 dark:bg-blue-900/30">I</th>
                            <th class="px-2 py-3 text-center font-medium text-zinc-900 dark:text-zinc-100 bg-yellow-50 dark:bg-yellow-900/30">S</th>
                            <th class="px-2 py-3 text-center font-medium text-zinc-900 dark:text-zinc-100 bg-red-50 dark:bg-red-900/30">A</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @php $no = 1; @endphp
                        @forelse ($this->attendanceData as $studentId => $data)
                            <tr wire:key="recap-{{ $studentId }}">
                                <td class="px-2 py-2 text-center sticky left-0 bg-white dark:bg-zinc-900 z-10">{{ $no++ }}</td>
                                <td class="px-4 py-2 sticky left-10 bg-white dark:bg-zinc-900 z-10">
                                    <span class="font-medium">{{ $data['student']->name }}</span>
                                </td>
                                @for ($day = 1; $day <= $this->daysInMonth; $day++)
                                    @php
                                        $dayData = $data['daily'][$day];
                                        $status = $dayData['status'];
                                        $isWeekend = $dayData['is_weekend'];
                                    @endphp
                                    <td class="px-1 py-2 text-center {{ $isWeekend ? 'bg-zinc-100 dark:bg-zinc-800' : '' }}">
                                        @if ($status)
                                            <span class="inline-flex items-center justify-center w-5 h-5 rounded text-xs font-medium
                                                {{ match($status) {
                                                    'hadir' => 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300',
                                                    'izin' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
                                                    'sakit' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-300',
                                                    'alpha' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
                                                    default => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300',
                                                } }}">
                                                {{ $this->getStatusSymbol($status) }}
                                            </span>
                                        @elseif (!$isWeekend)
                                            <span class="text-zinc-300 dark:text-zinc-600">-</span>
                                        @endif
                                    </td>
                                @endfor
                                <td class="px-2 py-2 text-center bg-green-50 dark:bg-green-900/30 font-medium">{{ $data['summary']['hadir'] }}</td>
                                <td class="px-2 py-2 text-center bg-blue-50 dark:bg-blue-900/30 font-medium">{{ $data['summary']['izin'] }}</td>
                                <td class="px-2 py-2 text-center bg-yellow-50 dark:bg-yellow-900/30 font-medium">{{ $data['summary']['sakit'] }}</td>
                                <td class="px-2 py-2 text-center bg-red-50 dark:bg-red-900/30 font-medium">{{ $data['summary']['alpha'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $this->daysInMonth + 6 }}" class="px-4 py-8 text-center text-zinc-500">
                                    {{ __('Tidak ada data siswa di kelas ini.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </flux:card.body>
        </flux:card>
    @else
        <flux:card>
            <flux:card.body class="text-center py-12">
                <flux:icon name="academic-cap" class="w-12 h-12 mx-auto text-zinc-400 mb-4" />
                <flux:heading size="lg">{{ __('Pilih Kelas') }}</flux:heading>
                <flux:text class="text-zinc-500 mt-2">{{ __('Pilih kelas untuk menampilkan rekap kehadiran siswa.') }}</flux:text>
            </flux:card.body>
        </flux:card>
    @endif
</div>
