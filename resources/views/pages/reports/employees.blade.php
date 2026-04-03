<?php

use App\Models\Employee;
use App\Models\Position;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('Laporan Pegawai')] class extends Component {
    use WithPagination;

    public $employee_type = '';
    public $is_active = '1';
    public $position_id = '';

    public function with(): array
    {
        $query = Employee::query()->with(['position']);

        if ($this->employee_type) {
            $query->where('employee_type', $this->employee_type);
        }

        if ($this->is_active !== '') {
            $query->where('is_active', $this->is_active);
        }

        if ($this->position_id) {
            $query->where('position_id', $this->position_id);
        }

        // Statistics
        $allEmployees = Employee::where('is_active', true);

        $totalActive = (clone $allEmployees)->count();
        $totalTeachers = (clone $allEmployees)->where(fn($q) => $q->where('employee_type', 'guru')->orWhere('employee_type', 'keduanya'))->count();
        $totalStaff = (clone $allEmployees)->where(fn($q) => $q->where('employee_type', 'staf')->orWhere('employee_type', 'keduanya'))->count();
        $totalMale = (clone $allEmployees)->where('gender', 'L')->count();
        $totalFemale = (clone $allEmployees)->where('gender', 'P')->count();

        // Employment status
        $pns = Employee::where('is_active', true)->where('employee_status', 'pns')->count();
        $honorer = Employee::where('is_active', true)->where('employee_status', 'honorer')->count();
        $kontrak = Employee::where('is_active', true)->where('employee_status', 'kontrak')->count();

        // Per Position
        $perPosition = Position::withCount(['employees' => fn($q) => $q->where('is_active', true)])->get();

        return [
            'employees' => $query->orderBy('name')->paginate(20),
            'positions' => Position::orderBy('name')->get(),
            'totalActive' => $totalActive,
            'totalTeachers' => $totalTeachers,
            'totalStaff' => $totalStaff,
            'totalMale' => $totalMale,
            'totalFemale' => $totalFemale,
            'pns' => $pns,
            'honorer' => $honorer,
            'kontrak' => $kontrak,
            'perPosition' => $perPosition,
        ];
    }
}; ?>

<div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-4">
                <flux:button icon="arrow-left" variant="ghost" :href="route('reports.index')" wire:navigate />
                <div>
                    <flux:heading size="xl">Laporan Pegawai</flux:heading>
                    <flux:subheading>Statistik dan data pegawai</flux:subheading>
                </div>
            </div>
            @can('reports.export')
            <div class="flex gap-2">
                <flux:dropdown>
                    <flux:button icon="arrow-down-tray" variant="primary">Ekspor Laporan</flux:button>
                    <flux:menu>
                        <flux:menu.item icon="document-text" :href="route('reports.export.employees', ['format' => 'pdf', 'type' => $employee_type, 'status' => $is_active, 'position_id' => $position_id])" target="_blank">Laporan PDF</flux:menu.item>
                        <flux:menu.item icon="document" :href="route('reports.export.employees', ['format' => 'word', 'type' => $employee_type, 'status' => $is_active, 'position_id' => $position_id])" target="_blank">Microsoft Word (.doc)</flux:menu.item>
                        <flux:menu.item icon="table-cells" :href="route('reports.export.employees', ['format' => 'excel', 'type' => $employee_type, 'status' => $is_active, 'position_id' => $position_id])" target="_blank">Microsoft Excel (.xls)</flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
            </div>
            @endcan
        </div>

        <!-- Summary Stats -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <flux:card class="border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-900/20">
                <div class="text-center">
                    <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($totalActive) }}</p>
                    <p class="text-sm text-blue-700 dark:text-blue-300">Total Pegawai Aktif</p>
                </div>
            </flux:card>

            <flux:card class="border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20">
                <div class="text-center">
                    <p class="text-3xl font-bold text-green-600 dark:text-green-400">{{ number_format($totalTeachers) }}</p>
                    <p class="text-sm text-green-700 dark:text-green-300">Guru</p>
                </div>
            </flux:card>

            <flux:card class="border-purple-200 bg-purple-50 dark:border-purple-800 dark:bg-purple-900/20">
                <div class="text-center">
                    <p class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($totalStaff) }}</p>
                    <p class="text-sm text-purple-700 dark:text-purple-300">Staff</p>
                </div>
            </flux:card>

            <flux:card class="border-cyan-200 bg-cyan-50 dark:border-cyan-800 dark:bg-cyan-900/20">
                <div class="text-center">
                    <p class="text-3xl font-bold text-cyan-600 dark:text-cyan-400">{{ number_format($totalMale) }}</p>
                    <p class="text-sm text-cyan-700 dark:text-cyan-300">Laki-laki</p>
                </div>
            </flux:card>

            <flux:card class="border-pink-200 bg-pink-50 dark:border-pink-800 dark:bg-pink-900/20">
                <div class="text-center">
                    <p class="text-3xl font-bold text-pink-600 dark:text-pink-400">{{ number_format($totalFemale) }}</p>
                    <p class="text-sm text-pink-700 dark:text-pink-300">Perempuan</p>
                </div>
            </flux:card>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <!-- Employment Status -->
            <flux:card>
                <flux:heading size="sm" class="mb-4">Status Kepegawaian</flux:heading>
                <div class="space-y-4">
                    @php
                        $total = $pns + $kontrak + $honorer;
                        $statuses = [
                            ['label' => 'PNS', 'count' => $pns, 'color' => 'green'],
                            ['label' => 'Kontrak', 'count' => $kontrak, 'color' => 'blue'],
                            ['label' => 'Honorer', 'count' => $honorer, 'color' => 'yellow'],
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
            </flux:card>

            <!-- Per Position -->
            <flux:card>
                <flux:heading size="sm" class="mb-4">Pegawai per Jabatan</flux:heading>
                @if ($perPosition->count() > 0)
                    <div class="max-h-64 space-y-2 overflow-y-auto">
                        @foreach ($perPosition->sortByDesc('employees_count') as $position)
                            <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2 dark:bg-gray-800">
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $position->name }}</span>
                                <flux:badge color="blue" size="sm">{{ $position->employees_count }}</flux:badge>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500">Belum ada data jabatan.</p>
                @endif
            </flux:card>
        </div>

        <!-- Data Table -->
        <flux:card>
            <flux:heading size="sm" class="mb-4">Daftar Pegawai</flux:heading>

            <div class="mb-4 grid gap-4 sm:grid-cols-3">
                <flux:select wire:model.live="employee_type">
                    <option value="">Semua Tipe</option>
                    <option value="guru">Guru</option>
                    <option value="staf">Staff</option>
                    <option value="keduanya">Guru & Staff</option>
                </flux:select>
                <flux:select wire:model.live="is_active">
                    <option value="">Semua Status</option>
                    <option value="1">Aktif</option>
                    <option value="0">Tidak Aktif</option>
                </flux:select>
                <flux:select wire:model.live="position_id">
                    <option value="">Semua Jabatan</option>
                    @foreach ($positions as $position)
                        <option value="{{ $position->id }}">{{ $position->name }}</option>
                    @endforeach
                </flux:select>
            </div>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>NIP</flux:table.column>
                    <flux:table.column>Nama</flux:table.column>
                    <flux:table.column>L/P</flux:table.column>
                    <flux:table.column>Jabatan</flux:table.column>
                    <flux:table.column>Tipe</flux:table.column>
                    <flux:table.column>Status Kepegawaian</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($employees as $employee)
                        <flux:table.row>
                            <flux:table.cell class="font-mono text-sm">{{ $employee->nip ?? '-' }}</flux:table.cell>
                            <flux:table.cell class="font-medium">{{ $employee->name }}</flux:table.cell>
                            <flux:table.cell>{{ $employee->gender === 'male' ? 'L' : 'P' }}</flux:table.cell>
                            <flux:table.cell>{{ $employee->position?->name ?? '-' }}</flux:table.cell>
                            <flux:table.cell>
                                @php
                                    $typeLabels = ['teacher' => 'Guru', 'staff' => 'Staff', 'both' => 'Guru & Staff'];
                                @endphp
                                {{ $typeLabels[$employee->type] ?? $employee->type }}
                            </flux:table.cell>
                            <flux:table.cell>
                                @php
                                    $empStatusLabels = ['permanent' => 'Tetap', 'contract' => 'Kontrak', 'honorary' => 'Honorer'];
                                @endphp
                                {{ $empStatusLabels[$employee->employment_status] ?? $employee->employment_status }}
                            </flux:table.cell>
                            <flux:table.cell>
                                @php
                                    $statusColors = ['active' => 'green', 'inactive' => 'red', 'retired' => 'blue'];
                                    $statusLabels = ['active' => 'Aktif', 'inactive' => 'Tidak Aktif', 'retired' => 'Pensiun'];
                                @endphp
                                <flux:badge :color="$statusColors[$employee->status] ?? 'zinc'" size="sm">
                                    {{ $statusLabels[$employee->status] ?? $employee->status }}
                                </flux:badge>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="7" class="text-center py-8">
                                <p class="text-gray-500">Tidak ada data pegawai.</p>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>

            @if ($employees->hasPages())
                <div class="mt-4">
                    {{ $employees->links() }}
                </div>
            @endif
    </flux:card>
</div>
