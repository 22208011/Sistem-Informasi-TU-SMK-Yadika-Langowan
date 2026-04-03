<?php

use App\Models\Employee;
use App\Models\EmployeeAttendance;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Kehadiran Pegawai')] class extends Component {
    public string $selectedDate = '';
    public string $filterType = '';
    public string $search = '';

    // Attendance data for bulk update
    public array $attendances = [];
    
    // For editing single record
    public bool $showEditModal = false;
    public ?int $editingEmployeeId = null;
    public string $editStatus = 'hadir';
    public string $editCheckIn = '';
    public string $editCheckOut = '';
    public string $editNotes = '';

    public function mount(): void
    {
        $this->selectedDate = now()->format('Y-m-d');
        $this->loadAttendances();
    }

    public function updatedSelectedDate(): void
    {
        $this->loadAttendances();
    }

    public function updatedFilterType(): void
    {
        $this->loadAttendances();
    }

    public function updatedSearch(): void
    {
        $this->loadAttendances();
    }

    public function loadAttendances(): void
    {
        $employees = Employee::query()
            ->active()
            ->with(['position', 'attendances' => fn($q) => $q->whereDate('date', $this->selectedDate)])
            ->when($this->filterType, fn($q) => $q->where('employee_type', $this->filterType))
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->get();

        $this->attendances = [];
        foreach ($employees as $employee) {
            $attendance = $employee->attendances->first();
            $this->attendances[$employee->id] = [
                'employee_id' => $employee->id,
                'employee_name' => $employee->name,
                'employee_type' => $employee->employee_type,
                'position' => $employee->position?->name ?? '-',
                'status' => $attendance?->status ?? 'hadir',
                'check_in' => $attendance?->check_in?->format('H:i') ?? '',
                'check_out' => $attendance?->check_out?->format('H:i') ?? '',
                'notes' => $attendance?->notes ?? '',
                'has_record' => $attendance !== null,
                'attendance_id' => $attendance?->id,
            ];
        }
    }

    #[Computed]
    public function statistics()
    {
        $date = Carbon::parse($this->selectedDate);
        $attendances = EmployeeAttendance::whereDate('date', $date)->get();
        $totalEmployees = Employee::active()->count();

        return [
            'total' => $totalEmployees,
            'recorded' => $attendances->count(),
            'hadir' => $attendances->where('status', EmployeeAttendance::STATUS_HADIR)->count(),
            'izin' => $attendances->where('status', EmployeeAttendance::STATUS_IZIN)->count(),
            'sakit' => $attendances->where('status', EmployeeAttendance::STATUS_SAKIT)->count(),
            'cuti' => $attendances->where('status', EmployeeAttendance::STATUS_CUTI)->count(),
            'dinas_luar' => $attendances->where('status', EmployeeAttendance::STATUS_DINAS_LUAR)->count(),
            'alpha' => $attendances->where('status', EmployeeAttendance::STATUS_ALPHA)->count(),
            'not_recorded' => $totalEmployees - $attendances->count(),
        ];
    }

    public function saveAttendances(): void
    {
        $saved = 0;
        $date = Carbon::parse($this->selectedDate);

        foreach ($this->attendances as $employeeId => $data) {
            // Skip jika tidak ada perubahan status (dan belum punya record)
            if (!$data['has_record'] && $data['status'] === 'hadir' && empty($data['check_in']) && empty($data['check_out']) && empty($data['notes'])) {
                continue;
            }

            EmployeeAttendance::updateOrCreate(
                [
                    'employee_id' => $employeeId,
                    'date' => $date,
                ],
                [
                    'status' => $data['status'],
                    'check_in' => !empty($data['check_in']) ? $data['check_in'] : null,
                    'check_out' => !empty($data['check_out']) ? $data['check_out'] : null,
                    'notes' => $data['notes'] ?? null,
                    'recorded_by' => auth()->id(),
                ]
            );
            $saved++;
        }

        $this->loadAttendances();
        session()->flash('success', "Kehadiran {$saved} pegawai berhasil disimpan.");
    }

    public function setAllPresent(): void
    {
        foreach ($this->attendances as $employeeId => $data) {
            $this->attendances[$employeeId]['status'] = 'hadir';
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
    
    public function editAttendance(int $employeeId): void
    {
        $data = $this->attendances[$employeeId] ?? null;
        if (!$data) return;
        
        $this->editingEmployeeId = $employeeId;
        $this->editStatus = $data['status'];
        $this->editCheckIn = $data['check_in'];
        $this->editCheckOut = $data['check_out'];
        $this->editNotes = $data['notes'];
        
        $this->showEditModal = true;
    }
    
    public function updateAttendance(): void
    {
        $date = Carbon::parse($this->selectedDate);
        
        EmployeeAttendance::updateOrCreate(
            [
                'employee_id' => $this->editingEmployeeId,
                'date' => $date,
            ],
            [
                'status' => $this->editStatus,
                'check_in' => !empty($this->editCheckIn) ? $this->editCheckIn : null,
                'check_out' => !empty($this->editCheckOut) ? $this->editCheckOut : null,
                'notes' => $this->editNotes ?: null,
                'recorded_by' => auth()->id(),
            ]
        );
        
        $this->showEditModal = false;
        $this->resetEditForm();
        $this->loadAttendances();
        session()->flash('success', 'Kehadiran berhasil diperbarui.');
    }
    
    public function deleteAttendance(int $employeeId): void
    {
        $date = Carbon::parse($this->selectedDate);
        
        EmployeeAttendance::where('employee_id', $employeeId)
            ->whereDate('date', $date)
            ->delete();
        
        $this->loadAttendances();
        session()->flash('success', 'Data kehadiran berhasil dihapus.');
    }
    
    public function resetEditForm(): void
    {
        $this->editingEmployeeId = null;
        $this->editStatus = 'hadir';
        $this->editCheckIn = '';
        $this->editCheckOut = '';
        $this->editNotes = '';
    }
}; ?>

<div>
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Kehadiran Pegawai') }}</flux:heading>
            <flux:text class="mt-2">{{ __('Input dan kelola kehadiran harian pegawai.') }}</flux:text>
        </div>
        <div class="flex gap-2">
            <flux:button :href="route('employees.attendance.recap')" variant="ghost" icon="chart-bar" wire:navigate>
                {{ __('Rekap Bulanan') }}
            </flux:button>
        </div>
    </div>

    <!-- Date Navigation -->
    <flux:card class="mb-6">
        <flux:card.body>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
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

                <div class="flex items-center gap-4">
                    <flux:text class="font-medium">
                        {{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('l, d F Y') }}
                    </flux:text>
                </div>
            </div>
        </flux:card.body>
    </flux:card>

    <!-- Statistics -->
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-4 mb-6">
        <flux:card class="p-4">
            <flux:text class="text-xs text-zinc-500">Total</flux:text>
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
        <flux:card class="p-4 border-purple-200 dark:border-purple-800">
            <flux:text class="text-xs text-purple-600">Cuti</flux:text>
            <flux:heading size="lg" class="text-purple-600">{{ $this->statistics['cuti'] }}</flux:heading>
        </flux:card>
        <flux:card class="p-4 border-cyan-200 dark:border-cyan-800">
            <flux:text class="text-xs text-cyan-600">Dinas</flux:text>
            <flux:heading size="lg" class="text-cyan-600">{{ $this->statistics['dinas_luar'] }}</flux:heading>
        </flux:card>
        <flux:card class="p-4 border-red-200 dark:border-red-800">
            <flux:text class="text-xs text-red-600">Alpha</flux:text>
            <flux:heading size="lg" class="text-red-600">{{ $this->statistics['alpha'] }}</flux:heading>
        </flux:card>
    </div>

    <!-- Attendance Form -->
    <flux:card>
        <flux:card.header>
            <div class="flex flex-col lg:flex-row gap-4 w-full justify-between">
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
                    <flux:table.column>{{ __('Pegawai') }}</flux:table.column>
                    <flux:table.column>{{ __('Jabatan') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                    <flux:table.column>{{ __('Jam Masuk') }}</flux:table.column>
                    <flux:table.column>{{ __('Jam Pulang') }}</flux:table.column>
                    <flux:table.column>{{ __('Keterangan') }}</flux:table.column>
                    <flux:table.column class="text-right">{{ __('Aksi') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($attendances as $employeeId => $data)
                        <flux:table.row wire:key="att-{{ $employeeId }}">
                            <flux:table.cell>
                                <div>
                                    <flux:text class="font-medium">{{ $data['employee_name'] }}</flux:text>
                                    <flux:badge size="sm" color="{{ $data['employee_type'] === 'guru' ? 'blue' : 'purple' }}">
                                        {{ App\Models\Employee::TYPES[$data['employee_type']] }}
                                    </flux:badge>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>{{ $data['position'] }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:select wire:model="attendances.{{ $employeeId }}.status" size="sm">
                                    @foreach (App\Models\EmployeeAttendance::STATUSES as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </flux:select>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:input
                                    wire:model="attendances.{{ $employeeId }}.check_in"
                                    type="time"
                                    size="sm"
                                    class="w-28"
                                />
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:input
                                    wire:model="attendances.{{ $employeeId }}.check_out"
                                    type="time"
                                    size="sm"
                                    class="w-28"
                                />
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:input
                                    wire:model="attendances.{{ $employeeId }}.notes"
                                    size="sm"
                                    placeholder="Keterangan..."
                                    class="w-40"
                                />
                            </flux:table.cell>
                            <flux:table.cell class="text-right">
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />
                                    <flux:menu>
                                        <flux:menu.item wire:click="editAttendance({{ $employeeId }})" icon="pencil">
                                            Edit
                                        </flux:menu.item>
                                        @if ($data['has_record'])
                                            <flux:menu.item 
                                                wire:click="deleteAttendance({{ $employeeId }})" 
                                                wire:confirm="Apakah Anda yakin ingin menghapus data kehadiran ini?"
                                                icon="trash" 
                                                variant="danger"
                                            >
                                                Hapus
                                            </flux:menu.item>
                                        @endif
                                    </flux:menu>
                                </flux:dropdown>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="7" class="text-center py-8">
                                <flux:text class="text-zinc-500">{{ __('Tidak ada pegawai aktif.') }}</flux:text>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card.body>
    </flux:card>
    
    <!-- Edit Attendance Modal -->
    <flux:modal wire:model="showEditModal" class="max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">Edit Kehadiran</flux:heading>
            
            @if ($editingEmployeeId && isset($attendances[$editingEmployeeId]))
                <div class="p-3 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                    <flux:text class="font-medium">{{ $attendances[$editingEmployeeId]['employee_name'] }}</flux:text>
                    <flux:text class="text-sm text-zinc-500">{{ $attendances[$editingEmployeeId]['position'] }}</flux:text>
                </div>
            @endif
            
            <form wire:submit="updateAttendance" class="space-y-4">
                <flux:select wire:model="editStatus" label="Status Kehadiran">
                    @foreach (App\Models\EmployeeAttendance::STATUSES as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </flux:select>
                
                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="editCheckIn" type="time" label="Jam Masuk" />
                    <flux:input wire:model="editCheckOut" type="time" label="Jam Pulang" />
                </div>
                
                <flux:textarea wire:model="editNotes" label="Keterangan" rows="2" />
                
                <div class="flex justify-end gap-3 pt-4">
                    <flux:button type="button" wire:click="$set('showEditModal', false)" variant="ghost">
                        Batal
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        Simpan
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>
