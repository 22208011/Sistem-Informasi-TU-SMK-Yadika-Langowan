<?php

use App\Models\Employee;
use App\Models\EmployeeAttendance;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Rekap Kehadiran Pegawai')] class extends Component {
    public int $selectedYear;
    public int $selectedMonth;
    public string $filterType = '';
    public string $search = '';

    public function mount(): void
    {
        $this->selectedYear = now()->year;
        $this->selectedMonth = now()->month;
    }

    #[Computed]
    public function employees()
    {
        return Employee::query()
            ->active()
            ->with(['position'])
            ->when($this->filterType, fn($q) => $q->where('employee_type', $this->filterType))
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
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
        $startDate = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $attendances = EmployeeAttendance::query()
            ->whereIn('employee_id', $this->employees->pluck('id'))
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy('employee_id');

        $data = [];
        foreach ($this->employees as $employee) {
            $employeeAttendances = $attendances->get($employee->id, collect());
            $dailyData = [];
            $summary = [
                'hadir' => 0,
                'izin' => 0,
                'sakit' => 0,
                'cuti' => 0,
                'dinas_luar' => 0,
                'alpha' => 0,
            ];

            for ($day = 1; $day <= $this->daysInMonth; $day++) {
                $date = Carbon::create($this->selectedYear, $this->selectedMonth, $day);
                $attendance = $employeeAttendances->firstWhere('date', $date->toDateString());

                $status = $attendance?->status ?? null;
                $dailyData[$day] = [
                    'status' => $status,
                    'check_in' => $attendance?->formatted_check_in,
                    'check_out' => $attendance?->formatted_check_out,
                    'is_weekend' => $date->isWeekend(),
                ];

                if ($status && isset($summary[$status])) {
                    $summary[$status]++;
                }
            }

            $data[$employee->id] = [
                'employee' => $employee,
                'daily' => $dailyData,
                'summary' => $summary,
            ];
        }

        return $data;
    }

    #[Computed]
    public function monthlyStatistics(): array
    {
        $startDate = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $attendances = EmployeeAttendance::query()
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $workingDays = 0;
        for ($day = 1; $day <= $this->daysInMonth; $day++) {
            $date = Carbon::create($this->selectedYear, $this->selectedMonth, $day);
            if (!$date->isWeekend()) {
                $workingDays++;
            }
        }

        $totalEmployees = Employee::active()->count();

        return [
            'total_employees' => $totalEmployees,
            'working_days' => $workingDays,
            'total_records' => $attendances->count(),
            'hadir' => $attendances->where('status', 'hadir')->count(),
            'izin' => $attendances->where('status', 'izin')->count(),
            'sakit' => $attendances->where('status', 'sakit')->count(),
            'cuti' => $attendances->where('status', 'cuti')->count(),
            'dinas_luar' => $attendances->where('status', 'dinas_luar')->count(),
            'alpha' => $attendances->where('status', 'alpha')->count(),
            'attendance_rate' => $workingDays > 0 && $totalEmployees > 0
                ? round(($attendances->where('status', 'hadir')->count() / ($workingDays * $totalEmployees)) * 100, 1)
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

    public function goToCurrentMonth(): void
    {
        $this->selectedYear = now()->year;
        $this->selectedMonth = now()->month;
    }

    public function getStatusSymbol(string $status): string
    {
        return match ($status) {
            'hadir' => 'H',
            'izin' => 'I',
            'sakit' => 'S',
            'cuti' => 'C',
            'dinas_luar' => 'D',
            'alpha' => 'A',
            default => '-',
        };
    }

    public function getStatusColor(string $status): string
    {
        return EmployeeAttendance::STATUS_COLORS[$status] ?? 'zinc';
    }
}; ?>

<div>
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <flux:button :href="route('employees.attendance.index')" variant="ghost" icon="arrow-left" wire:navigate class="mb-4">
                {{ __('Kembali ke Input Harian') }}
            </flux:button>

            <flux:heading size="xl">{{ __('Rekap Kehadiran Bulanan') }}</flux:heading>
            <flux:text class="mt-2">{{ __('Ringkasan kehadiran pegawai per bulan.') }}</flux:text>
        </div>
    </div>

    <!-- Month Navigation -->
    <flux:card class="mb-6">
        <flux:card.body>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
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
                    <flux:button wire:click="goToCurrentMonth" variant="ghost" size="sm">
                        {{ __('Bulan Ini') }}
                    </flux:button>
                </div>

                <flux:text class="font-medium">
                    {{ $this->months[$selectedMonth] }} {{ $selectedYear }}
                </flux:text>
            </div>
        </flux:card.body>
    </flux:card>

    <!-- Statistics -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <flux:card class="p-4">
            <flux:text class="text-xs text-zinc-500">Total Pegawai</flux:text>
            <flux:heading size="lg">{{ $this->monthlyStatistics['total_employees'] }}</flux:heading>
        </flux:card>
        <flux:card class="p-4">
            <flux:text class="text-xs text-zinc-500">Hari Kerja</flux:text>
            <flux:heading size="lg">{{ $this->monthlyStatistics['working_days'] }}</flux:heading>
        </flux:card>
        <flux:card class="p-4">
            <flux:text class="text-xs text-zinc-500">Total Kehadiran</flux:text>
            <flux:heading size="lg">{{ $this->monthlyStatistics['hadir'] }}</flux:heading>
        </flux:card>
        <flux:card class="p-4">
            <flux:text class="text-xs text-zinc-500">Total Ketidakhadiran</flux:text>
            <flux:heading size="lg">{{ $this->monthlyStatistics['izin'] + $this->monthlyStatistics['sakit'] + $this->monthlyStatistics['cuti'] + $this->monthlyStatistics['alpha'] }}</flux:heading>
        </flux:card>
        <flux:card class="p-4 border-green-200 dark:border-green-800">
            <flux:text class="text-xs text-green-600">Tingkat Kehadiran</flux:text>
            <flux:heading size="lg" class="text-green-600">{{ $this->monthlyStatistics['attendance_rate'] }}%</flux:heading>
        </flux:card>
    </div>

    <!-- Legend -->
    <flux:card class="mb-6">
        <flux:card.body>
            <div class="flex flex-wrap gap-4">
                <flux:text class="font-medium">Keterangan:</flux:text>
                @foreach (App\Models\EmployeeAttendance::STATUSES as $key => $label)
                    <div class="flex items-center gap-1">
                        <flux:badge size="sm" color="{{ App\Models\EmployeeAttendance::STATUS_COLORS[$key] }}">
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

    <!-- Filter -->
    <flux:card class="mb-6">
        <flux:card.body>
            <div class="flex flex-col sm:flex-row gap-4">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('Cari pegawai...') }}"
                    icon="magnifying-glass"
                    class="sm:max-w-xs"
                />

                <flux:select wire:model.live="filterType" class="sm:max-w-xs">
                    <option value="">Semua Tipe</option>
                    @foreach (App\Models\Employee::TYPES as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </flux:select>
            </div>
        </flux:card.body>
    </flux:card>

    <!-- Attendance Table -->
    <flux:card>
        <flux:card.body class="p-0 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-zinc-900 dark:text-zinc-100 sticky left-0 bg-zinc-50 dark:bg-zinc-800 z-10 min-w-[200px]">
                            {{ __('Pegawai') }}
                        </th>
                        @for ($day = 1; $day <= $this->daysInMonth; $day++)
                            @php
                                $date = \Carbon\Carbon::create($selectedYear, $selectedMonth, $day);
                                $isWeekend = $date->isWeekend();
                            @endphp
                            <th class="px-1 py-3 text-center font-medium min-w-[30px] {{ $isWeekend ? 'bg-zinc-200 dark:bg-zinc-700' : '' }}">
                                <span class="text-xs {{ $isWeekend ? 'text-red-500' : 'text-zinc-900 dark:text-zinc-100' }}">{{ $day }}</span>
                            </th>
                        @endfor
                        <th class="px-2 py-3 text-center font-medium text-zinc-900 dark:text-zinc-100 bg-green-50 dark:bg-green-900/30">H</th>
                        <th class="px-2 py-3 text-center font-medium text-zinc-900 dark:text-zinc-100 bg-blue-50 dark:bg-blue-900/30">I</th>
                        <th class="px-2 py-3 text-center font-medium text-zinc-900 dark:text-zinc-100 bg-yellow-50 dark:bg-yellow-900/30">S</th>
                        <th class="px-2 py-3 text-center font-medium text-zinc-900 dark:text-zinc-100 bg-purple-50 dark:bg-purple-900/30">C</th>
                        <th class="px-2 py-3 text-center font-medium text-zinc-900 dark:text-zinc-100 bg-cyan-50 dark:bg-cyan-900/30">D</th>
                        <th class="px-2 py-3 text-center font-medium text-zinc-900 dark:text-zinc-100 bg-red-50 dark:bg-red-900/30">A</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($this->attendanceData as $employeeId => $data)
                        <tr wire:key="recap-{{ $employeeId }}">
                            <td class="px-4 py-2 sticky left-0 bg-white dark:bg-zinc-900 z-10">
                                <div>
                                    <span class="font-medium">{{ $data['employee']->name }}</span>
                                    <br>
                                    <span class="text-xs text-zinc-500">{{ $data['employee']->position?->name ?? '-' }}</span>
                                </div>
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
                                                'cuti' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300',
                                                'dinas_luar' => 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/50 dark:text-cyan-300',
                                                'alpha' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
                                                default => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300',
                                            } }}"
                                            title="{{ $dayData['check_in'] ? 'Masuk: ' . $dayData['check_in'] : '' }} {{ $dayData['check_out'] ? '| Pulang: ' . $dayData['check_out'] : '' }}"
                                        >
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
                            <td class="px-2 py-2 text-center bg-purple-50 dark:bg-purple-900/30 font-medium">{{ $data['summary']['cuti'] }}</td>
                            <td class="px-2 py-2 text-center bg-cyan-50 dark:bg-cyan-900/30 font-medium">{{ $data['summary']['dinas_luar'] }}</td>
                            <td class="px-2 py-2 text-center bg-red-50 dark:bg-red-900/30 font-medium">{{ $data['summary']['alpha'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $this->daysInMonth + 7 }}" class="px-4 py-8 text-center text-zinc-500">
                                {{ __('Tidak ada data pegawai.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </flux:card.body>
    </flux:card>
</div>
