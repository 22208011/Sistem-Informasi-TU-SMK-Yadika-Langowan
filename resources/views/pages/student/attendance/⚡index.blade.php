<?php

use App\Models\Classroom;
use App\Models\Student;
use App\Models\StudentAttendance;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Kehadiran Siswa')] class extends Component {
    public string $selectedDate = '';
    public ?int $selectedClassroom = null;

    // Attendance data for bulk update
    public array $attendances = [];

    public function mount(): void
    {
        $this->selectedDate = now()->format('Y-m-d');

        // Auto-select first classroom if available
        $firstClassroom = Classroom::active()->first();
        if ($firstClassroom) {
            $this->selectedClassroom = $firstClassroom->id;
            $this->loadAttendances();
        }
    }

    public function updatedSelectedDate(): void
    {
        $this->loadAttendances();
    }

    public function updatedSelectedClassroom(): void
    {
        $this->loadAttendances();
    }

    public function loadAttendances(): void
    {
        if (!$this->selectedClassroom) {
            $this->attendances = [];
            return;
        }

        $students = Student::query()
            ->active()
            ->where('classroom_id', $this->selectedClassroom)
            ->with(['attendances' => fn($q) => $q->whereDate('date', $this->selectedDate)])
            ->orderBy('name')
            ->get();

        $this->attendances = [];
        foreach ($students as $student) {
            $attendance = $student->attendances->first();
            $this->attendances[$student->id] = [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'student_nis' => $student->nis,
                'gender' => $student->gender,
                'status' => $attendance?->status ?? 'hadir',
                'notes' => $attendance?->notes ?? '',
                'has_record' => $attendance !== null,
            ];
        }
    }

    #[Computed]
    public function classrooms()
    {
        return Classroom::active()->orderBy('name')->get();
    }

    #[Computed]
    public function selectedClassroomData()
    {
        return $this->selectedClassroom
            ? Classroom::with('department')->find($this->selectedClassroom)
            : null;
    }

    #[Computed]
    public function statistics()
    {
        if (!$this->selectedClassroom) {
            return ['total' => 0, 'hadir' => 0, 'izin' => 0, 'sakit' => 0, 'alpha' => 0];
        }

        $date = Carbon::parse($this->selectedDate);
        $attendances = StudentAttendance::whereDate('date', $date)
            ->where('classroom_id', $this->selectedClassroom)
            ->get();

        $totalStudents = Student::active()->where('classroom_id', $this->selectedClassroom)->count();

        return [
            'total' => $totalStudents,
            'recorded' => $attendances->count(),
            'hadir' => $attendances->where('status', StudentAttendance::STATUS_HADIR)->count(),
            'izin' => $attendances->where('status', StudentAttendance::STATUS_IZIN)->count(),
            'sakit' => $attendances->where('status', StudentAttendance::STATUS_SAKIT)->count(),
            'alpha' => $attendances->where('status', StudentAttendance::STATUS_ALPHA)->count(),
        ];
    }

    public function saveAttendances(): void
    {
        if (!$this->selectedClassroom) {
            session()->flash('error', 'Pilih kelas terlebih dahulu.');
            return;
        }

        $saved = 0;
        $date = Carbon::parse($this->selectedDate);

        foreach ($this->attendances as $studentId => $data) {
            StudentAttendance::updateOrCreate(
                [
                    'student_id' => $studentId,
                    'date' => $date,
                ],
                [
                    'classroom_id' => $this->selectedClassroom,
                    'status' => $data['status'],
                    'notes' => $data['notes'] ?? null,
                    'recorded_by' => auth()->id(),
                ]
            );
            $saved++;
        }

        $this->loadAttendances();
        session()->flash('success', "Kehadiran {$saved} siswa berhasil disimpan.");
    }

    public function setAllPresent(): void
    {
        foreach ($this->attendances as $studentId => $data) {
            $this->attendances[$studentId]['status'] = 'hadir';
        }
    }

    public function previousDay(): void
    {
        $this->selectedDate = Carbon::parse($this->selectedDate)->subDay()->format('Y-m-d');
        $this->loadAttendances();
    }

    public function nextDay(): void
    {
        $this->selectedDate = Carbon::parse($this->selectedDate)->addDay()->format('Y-m-d');
        $this->loadAttendances();
    }

    public function goToToday(): void
    {
        $this->selectedDate = now()->format('Y-m-d');
        $this->loadAttendances();
    }
}; ?>

<div>
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Kehadiran Siswa') }}</flux:heading>
            <flux:text class="mt-2">{{ __('Input kehadiran siswa per kelas.') }}</flux:text>
        </div>
        <div class="flex gap-2">
            <flux:button :href="route('students.attendance.recap')" variant="ghost" icon="chart-bar" wire:navigate>
                {{ __('Rekap Bulanan') }}
            </flux:button>
        </div>
    </div>

    <!-- Date & Class Selection -->
    <flux:card class="mb-6">
        <flux:card.body>
            <div class="flex flex-col md:flex-row md:items-center gap-4">
                <div class="flex items-center gap-2">
                    <flux:button wire:click="previousDay" variant="ghost" icon="chevron-left" size="sm" />
                    <flux:input
                        wire:model.live="selectedDate"
                        type="date"
                        class="w-auto"
                    />
                    <flux:button wire:click="nextDay" variant="ghost" icon="chevron-right" size="sm" />
                    <flux:button wire:click="goToToday" variant="ghost" size="sm">
                        {{ __('Hari Ini') }}
                    </flux:button>
                </div>

                <div class="flex-1">
                    <flux:select wire:model.live="selectedClassroom" class="w-full md:max-w-xs">
                        <option value="">-- Pilih Kelas --</option>
                        @foreach ($this->classrooms as $classroom)
                            <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="text-right">
                    <flux:text class="font-medium">
                        {{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('l, d F Y') }}
                    </flux:text>
                    @if ($this->selectedClassroomData)
                        <flux:text size="sm" class="text-zinc-500">
                            {{ $this->selectedClassroomData->name }}
                            @if ($this->selectedClassroomData->department)
                                - {{ $this->selectedClassroomData->department->code }}
                            @endif
                        </flux:text>
                    @endif
                </div>
            </div>
        </flux:card.body>
    </flux:card>

    @if ($selectedClassroom)
        <!-- Statistics -->
        <div class="grid grid-cols-2 md:grid-cols-6 gap-4 mb-6">
            <flux:card class="p-4">
                <flux:text class="text-xs text-zinc-500">Total Siswa</flux:text>
                <flux:heading size="lg">{{ $this->statistics['total'] }}</flux:heading>
            </flux:card>
            <flux:card class="p-4">
                <flux:text class="text-xs text-zinc-500">Tercatat</flux:text>
                <flux:heading size="lg">{{ $this->statistics['recorded'] }}</flux:heading>
            </flux:card>
            <flux:card class="p-4 border-green-200 dark:border-green-800">
                <flux:text class="text-xs text-green-600">Hadir</flux:text>
                <flux:heading size="lg" class="text-green-600">{{ $this->statistics['hadir'] }}</flux:heading>
            </flux:card>
            <flux:card class="p-4 border-blue-200 dark:border-blue-800">
                <flux:text class="text-xs text-blue-600">Izin</flux:text>
                <flux:heading size="lg" class="text-blue-600">{{ $this->statistics['izin'] }}</flux:heading>
            </flux:card>
            <flux:card class="p-4 border-yellow-200 dark:border-yellow-800">
                <flux:text class="text-xs text-yellow-600">Sakit</flux:text>
                <flux:heading size="lg" class="text-yellow-600">{{ $this->statistics['sakit'] }}</flux:heading>
            </flux:card>
            <flux:card class="p-4 border-red-200 dark:border-red-800">
                <flux:text class="text-xs text-red-600">Alpha</flux:text>
                <flux:heading size="lg" class="text-red-600">{{ $this->statistics['alpha'] }}</flux:heading>
            </flux:card>
        </div>

        <!-- Attendance Form -->
        <flux:card>
            <flux:card.header>
                <div class="flex flex-col sm:flex-row gap-4 w-full justify-between">
                    <flux:heading size="lg">{{ __('Daftar Siswa') }}</flux:heading>
                    <div class="flex gap-2">
                        <flux:button wire:click="setAllPresent" variant="ghost" size="sm">
                            {{ __('Set Semua Hadir') }}
                        </flux:button>
                        <flux:button wire:click="saveAttendances" variant="primary" icon="check">
                            {{ __('Simpan Kehadiran') }}
                        </flux:button>
                    </div>
                </div>
            </flux:card.header>

            <flux:card.body class="p-0">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column class="w-12">{{ __('No') }}</flux:table.column>
                        <flux:table.column>{{ __('Siswa') }}</flux:table.column>
                        <flux:table.column>{{ __('NIS') }}</flux:table.column>
                        <flux:table.column>{{ __('L/P') }}</flux:table.column>
                        <flux:table.column>{{ __('Status') }}</flux:table.column>
                        <flux:table.column>{{ __('Keterangan') }}</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($attendances as $index => $data)
                            <flux:table.row wire:key="att-{{ $data['student_id'] }}">
                                <flux:table.cell>{{ $loop->iteration }}</flux:table.cell>
                                <flux:table.cell class="font-medium">{{ $data['student_name'] }}</flux:table.cell>
                                <flux:table.cell>{{ $data['student_nis'] }}</flux:table.cell>
                                <flux:table.cell>{{ $data['gender'] }}</flux:table.cell>
                                <flux:table.cell>
                                    <div class="flex gap-2">
                                        @foreach (App\Models\StudentAttendance::STATUSES as $key => $label)
                                            <label class="inline-flex items-center">
                                                <input
                                                    type="radio"
                                                    wire:model="attendances.{{ $data['student_id'] }}.status"
                                                    value="{{ $key }}"
                                                    class="form-radio h-4 w-4 text-{{ App\Models\StudentAttendance::STATUS_COLORS[$key] }}-600"
                                                />
                                                <span class="ml-1 text-sm">{{ substr($label, 0, 1) }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:input
                                        wire:model="attendances.{{ $data['student_id'] }}.notes"
                                        size="sm"
                                        placeholder="Keterangan..."
                                        class="w-40"
                                    />
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="6" class="text-center py-8">
                                    <flux:text class="text-zinc-500">{{ __('Tidak ada siswa di kelas ini.') }}</flux:text>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </flux:card.body>
        </flux:card>
    @else
        <flux:card>
            <flux:card.body class="text-center py-12">
                <flux:icon name="users" class="w-12 h-12 mx-auto text-zinc-400 mb-4" />
                <flux:heading size="lg">{{ __('Pilih Kelas') }}</flux:heading>
                <flux:text class="text-zinc-500 mt-2">{{ __('Pilih kelas untuk menampilkan daftar siswa dan input kehadiran.') }}</flux:text>
            </flux:card.body>
        </flux:card>
    @endif
</div>
