<?php

use App\Models\StudentAttendance;
use App\Models\Student;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('layouts.app')] #[Title('Kehadiran Anak')] class extends Component {
    use WithPagination;

    public $selectedStudent = '';
    public $month = '';
    public $year = '';

    public function mount()
    {
        $this->month = now()->month;
        $this->year = now()->year;

        // Auto-select first student if only one
        $children = $this->getChildren();
        if ($children->count() === 1) {
            $this->selectedStudent = $children->first()->id;
        }
    }

    public function getChildren()
    {
        $user = auth()->user();
        return $user->children ?? collect();
    }

    public function with(): array
    {
        $children = $this->getChildren();

        $attendanceRecords = collect();
        $summary = [
            'present' => 0,
            'sick' => 0,
            'excused' => 0,
            'absent' => 0,
            'late' => 0,
            'total' => 0,
        ];

        if ($this->selectedStudent && $this->month && $this->year) {
            $startDate = \Carbon\Carbon::create($this->year, $this->month, 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();

            $attendanceRecords = StudentAttendance::where('student_id', $this->selectedStudent)
                ->whereBetween('date', [$startDate, $endDate])
                ->orderBy('date', 'desc')
                ->get();

            // Calculate summary
            $summary['present'] = $attendanceRecords->where('status', 'present')->count();
            $summary['sick'] = $attendanceRecords->where('status', 'sick')->count();
            $summary['excused'] = $attendanceRecords->where('status', 'excused')->count();
            $summary['absent'] = $attendanceRecords->where('status', 'absent')->count();
            $summary['late'] = $attendanceRecords->where('status', 'late')->count();
            $summary['total'] = $attendanceRecords->count();
        }

        return [
            'children' => $children,
            'attendanceRecords' => $attendanceRecords,
            'summary' => $summary,
            'months' => [
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
            ],
        ];
    }
}; ?>

<div class="space-y-6">
        <!-- Header -->
        <div>
            <flux:heading size="xl">Kehadiran Anak</flux:heading>
            <flux:subheading>Pantau rekam kehadiran anak Anda di sekolah</flux:subheading>
        </div>

        <!-- Filters -->
        <flux:card>
            <div class="grid gap-4 sm:grid-cols-3">
                @if ($children->count() > 1)
                    <flux:select wire:model.live="selectedStudent" label="Pilih Anak">
                        <option value="">-- Pilih Anak --</option>
                        @foreach ($children as $child)
                            <option value="{{ $child->id }}">{{ $child->name }}</option>
                        @endforeach
                    </flux:select>
                @else
                    <div>
                        <flux:label>Nama Anak</flux:label>
                        <p class="mt-1 font-medium text-gray-900 dark:text-white">
                            {{ $children->first()?->name ?? '-' }}
                        </p>
                    </div>
                @endif

                <flux:select wire:model.live="month" label="Bulan">
                    @foreach ($months as $num => $name)
                        <option value="{{ $num }}">{{ $name }}</option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="year" label="Tahun">
                    @for ($y = now()->year; $y >= now()->year - 2; $y--)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endfor
                </flux:select>
            </div>
        </flux:card>

        @if ($selectedStudent)
            <!-- Summary Cards -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <flux:card class="border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20">
                    <div class="text-center">
                        <div class="text-3xl font-bold text-green-600 dark:text-green-400">{{ $summary['present'] }}</div>
                        <div class="text-sm text-green-700 dark:text-green-300">Hadir</div>
                    </div>
                </flux:card>

                <flux:card class="border-yellow-200 bg-yellow-50 dark:border-yellow-800 dark:bg-yellow-900/20">
                    <div class="text-center">
                        <div class="text-3xl font-bold text-yellow-600 dark:text-yellow-400">{{ $summary['late'] }}</div>
                        <div class="text-sm text-yellow-700 dark:text-yellow-300">Terlambat</div>
                    </div>
                </flux:card>

                <flux:card class="border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-900/20">
                    <div class="text-center">
                        <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $summary['sick'] }}</div>
                        <div class="text-sm text-blue-700 dark:text-blue-300">Sakit</div>
                    </div>
                </flux:card>

                <flux:card class="border-purple-200 bg-purple-50 dark:border-purple-800 dark:bg-purple-900/20">
                    <div class="text-center">
                        <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ $summary['excused'] }}</div>
                        <div class="text-sm text-purple-700 dark:text-purple-300">Izin</div>
                    </div>
                </flux:card>

                <flux:card class="border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20">
                    <div class="text-center">
                        <div class="text-3xl font-bold text-red-600 dark:text-red-400">{{ $summary['absent'] }}</div>
                        <div class="text-sm text-red-700 dark:text-red-300">Tanpa Keterangan</div>
                    </div>
                </flux:card>
            </div>

            <!-- Attendance Percentage -->
            @if ($summary['total'] > 0)
                <flux:card>
                    <flux:heading size="sm" class="mb-4">Persentase Kehadiran</flux:heading>
                    @php
                        $attendanceRate = round((($summary['present'] + $summary['late']) / $summary['total']) * 100, 1);
                    @endphp
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">Tingkat Kehadiran</span>
                            <span class="font-semibold {{ $attendanceRate >= 90 ? 'text-green-600' : ($attendanceRate >= 75 ? 'text-yellow-600' : 'text-red-600') }}">
                                {{ $attendanceRate }}%
                            </span>
                        </div>
                        <div class="h-4 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                            <div class="h-full rounded-full transition-all duration-500 {{ $attendanceRate >= 90 ? 'bg-green-500' : ($attendanceRate >= 75 ? 'bg-yellow-500' : 'bg-red-500') }}"
                                 style="width: {{ $attendanceRate }}%">
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            @if ($attendanceRate >= 90)
                                Kehadiran sangat baik! Pertahankan prestasinya.
                            @elseif ($attendanceRate >= 75)
                                Kehadiran cukup baik. Tingkatkan lagi ya!
                            @else
                                Kehadiran perlu ditingkatkan. Silakan hubungi wali kelas.
                            @endif
                        </p>
                    </div>
                </flux:card>
            @endif

            <!-- Attendance Records -->
            <flux:card>
                <flux:heading size="sm" class="mb-4">Rekam Kehadiran - {{ $months[$month] }} {{ $year }}</flux:heading>

                @if ($attendanceRecords->count() > 0)
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Tanggal</flux:table.column>
                            <flux:table.column>Hari</flux:table.column>
                            <flux:table.column>Status</flux:table.column>
                            <flux:table.column>Jam Masuk</flux:table.column>
                            <flux:table.column>Jam Keluar</flux:table.column>
                            <flux:table.column>Keterangan</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach ($attendanceRecords as $record)
                                <flux:table.row>
                                    <flux:table.cell>
                                        {{ \Carbon\Carbon::parse($record->date)->format('d M Y') }}
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        {{ \Carbon\Carbon::parse($record->date)->locale('id')->dayName }}
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        @php
                                            $statusColors = [
                                                'present' => 'green',
                                                'late' => 'yellow',
                                                'sick' => 'blue',
                                                'excused' => 'purple',
                                                'absent' => 'red',
                                            ];
                                            $statusLabels = [
                                                'present' => 'Hadir',
                                                'late' => 'Terlambat',
                                                'sick' => 'Sakit',
                                                'excused' => 'Izin',
                                                'absent' => 'Tanpa Keterangan',
                                            ];
                                        @endphp
                                        <flux:badge :color="$statusColors[$record->status] ?? 'zinc'" size="sm">
                                            {{ $statusLabels[$record->status] ?? $record->status }}
                                        </flux:badge>
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        {{ $record->check_in ? \Carbon\Carbon::parse($record->check_in)->format('H:i') : '-' }}
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        {{ $record->check_out ? \Carbon\Carbon::parse($record->check_out)->format('H:i') : '-' }}
                                    </flux:table.cell>
                                    <flux:table.cell class="max-w-xs truncate">
                                        {{ $record->notes ?? '-' }}
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                @else
                    <div class="py-12 text-center">
                        <flux:icon.calendar-days class="mx-auto mb-4 size-12 text-gray-400" />
                        <flux:heading size="sm">Belum Ada Data Kehadiran</flux:heading>
                        <flux:subheading>Data kehadiran untuk bulan ini belum tersedia.</flux:subheading>
                    </div>
                @endif
            </flux:card>
        @else
            <flux:card>
                <div class="py-12 text-center">
                    <flux:icon.user-group class="mx-auto mb-4 size-12 text-gray-400" />
                    <flux:heading size="lg">Pilih Anak</flux:heading>
                    <flux:subheading>Silakan pilih anak terlebih dahulu untuk melihat data kehadiran.</flux:subheading>
                </div>
            </flux:card>
        @endif
    </div>
</div>
