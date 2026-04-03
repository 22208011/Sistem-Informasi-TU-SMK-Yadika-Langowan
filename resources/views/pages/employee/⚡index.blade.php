<?php

use App\Models\Employee;
use App\Models\Position;
use App\Livewire\Concerns\WithNotification;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('Data Pegawai')] class extends Component {
    use WithPagination;
    use WithNotification;

    public string $search = '';
    public string $filterType = '';
    public string $filterStatus = '';
    public string $filterActiveStatus = '';
    public ?int $filterPosition = null;

    public function mount(): void
    {
        // Check for session flash from redirect
        if (session()->has('success')) {
            $this->success(session('success'));
        }
        if (session()->has('error')) {
            $this->error(session('error'));
        }
    }

    #[Computed]
    public function employees()
    {
        return Employee::query()
            ->with(['position', 'department'])
            ->when($this->search, fn($q) => $q->where(function ($query) {
                $query->where('name', 'like', "%{$this->search}%")
                    ->orWhere('nip', 'like', "%{$this->search}%")
                    ->orWhere('nuptk', 'like', "%{$this->search}%");
            }))
            ->when($this->filterType, fn($q) => $q->where('employee_type', $this->filterType))
            ->when($this->filterStatus, fn($q) => $q->where('employee_status', $this->filterStatus))
            ->when($this->filterActiveStatus !== '', fn($q) => $q->where('is_active', $this->filterActiveStatus === '1'))
            ->when($this->filterPosition, fn($q) => $q->where('position_id', $this->filterPosition))
            ->orderBy('name')
            ->paginate(15);
    }

    #[Computed]
    public function positions()
    {
        return Position::active()->orderBy('name')->get();
    }

    #[Computed]
    public function statistics()
    {
        return [
            'total' => Employee::count(),
            'teachers' => Employee::teachers()->count(),
            'staff' => Employee::staff()->count(),
            'active' => Employee::active()->count(),
        ];
    }

    public function toggleActive(Employee $employee): void
    {
        \Log::info('toggleActive called', ['employee_id' => $employee->id, 'current_status' => $employee->is_active]);
        
        $employee->update(['is_active' => !$employee->is_active]);
        $employee->refresh();
        
        \Log::info('toggleActive completed', ['employee_id' => $employee->id, 'new_status' => $employee->is_active]);
        
        $status = $employee->is_active ? 'diaktifkan' : 'dinonaktifkan';
        $this->success("Pegawai berhasil {$status}.");
    }

    public function delete(Employee $employee): void
    {
        try {
            $name = $employee->name;
            $employee->delete();
            $this->success("Pegawai {$name} berhasil dihapus.");
        } catch (\Exception $e) {
            $this->error('Gagal menghapus pegawai: ' . $e->getMessage());
        }
    }
}; ?>

<div>
    <!-- Page Header with Animation -->
    <x-page-header>
        <x-slot:icon>
            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
        </x-slot:icon>
        <x-slot:title>{{ __('Data Pegawai') }}</x-slot:title>
        <x-slot:subtitle>{{ __('Kelola data guru dan tenaga kependidikan sekolah.') }}</x-slot:subtitle>
        <x-slot:actions>
            <flux:button :href="route('employees.attendance.index')" variant="ghost" icon="clipboard-document-check" wire:navigate class="rounded-xl! hover:bg-green-50 dark:hover:bg-green-900/20 transition-colors">
                {{ __('Kehadiran') }}
            </flux:button>
            <flux:button :href="route('employees.positions')" variant="ghost" icon="briefcase" wire:navigate class="rounded-xl! hover:bg-purple-50 dark:hover:bg-purple-900/20 transition-colors">
                {{ __('Jabatan') }}
            </flux:button>
            <flux:button :href="route('employees.create')" icon="plus" wire:navigate class="rounded-xl! bg-linear-to-r! from-green-600! to-emerald-600! hover:from-green-700! hover:to-emerald-700! shadow-lg! shadow-green-500!/25 hover:shadow-xl! hover:shadow-green-500!/30 hover:scale-105! transition-all! duration-300!">
                {{ __('Tambah Pegawai') }}
            </flux:button>
        </x-slot:actions>
    </x-page-header>

    {{-- Notification --}}
    <x-notification-alert :type="$notificationType" :message="$notificationMessage" />

    <!-- Statistics Cards with Elegant Design -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6 mb-8">
        <x-stat-card title="Total Pegawai" :value="$this->statistics['total']" color="green" class="animate-fade-in-up">
            <x-slot:icon>
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card title="Guru" :value="$this->statistics['teachers']" color="blue" class="animate-fade-in-up delay-100">
            <x-slot:icon>
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card title="Tenaga Kependidikan" :value="$this->statistics['staff']" color="purple" class="animate-fade-in-up delay-200">
            <x-slot:icon>
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card title="Aktif" :value="$this->statistics['active']" color="amber" class="animate-fade-in-up delay-300">
            <x-slot:icon>
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <!-- Template Surat Widget -->
    <div class="animate-fade-in-up delay-300">
        <livewire:letters.template-widget category="pegawai" title="Format Surat Pegawai" description="Akses cepat untuk mendownload atau mengunggah format dokumen khusus tenaga pendidik dan staff." />
    </div>

    <!-- Data Table Card -->
    <x-elegant-card :noPadding="true" class="animate-fade-in-up delay-400">
        <x-slot:header>
            <div class="flex flex-col lg:flex-row gap-4 w-full">
                <div class="relative flex-1 max-w-sm">
                    <flux:input
                        wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('Cari nama, NIP, NUPTK...') }}"
                        icon="magnifying-glass"
                        class="rounded-xl! bg-zinc-50! dark:bg-zinc-800/50! focus:bg-white! dark:focus:bg-zinc-800!"
                    />
                </div>

                <div class="flex flex-wrap gap-3">
                    <flux:select wire:model.live="filterType" class="rounded-xl! bg-zinc-50! dark:bg-zinc-800/50! min-w-35">
                        <option value="">Semua Tipe</option>
                        @foreach (App\Models\Employee::TYPES as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model.live="filterStatus" class="rounded-xl! bg-zinc-50! dark:bg-zinc-800/50! min-w-35">
                        <option value="">Semua Status</option>
                        @foreach (App\Models\Employee::STATUSES as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model.live="filterActiveStatus" class="rounded-xl! bg-zinc-50! dark:bg-zinc-800/50! min-w-35">
                        <option value="">Semua Keaktifan</option>
                        <option value="1">Aktif</option>
                        <option value="0">Tidak Aktif</option>
                    </flux:select>

                    <flux:select wire:model.live="filterPosition" class="rounded-xl! bg-zinc-50! dark:bg-zinc-800/50! min-w-35">
                        <option value="">Semua Jabatan</option>
                        @foreach ($this->positions as $position)
                            <option value="{{ $position->id }}">{{ $position->name }}</option>
                        @endforeach
                    </flux:select>
                </div>
            </div>
        </x-slot:header>

        <flux:table class="table-elegant">
            <flux:table.columns>
                <flux:table.column class="font-semibold!">{{ __('Pegawai') }}</flux:table.column>
                <flux:table.column class="font-semibold!">{{ __('NIP/NUPTK') }}</flux:table.column>
                <flux:table.column class="font-semibold!">{{ __('Tipe') }}</flux:table.column>
                <flux:table.column class="font-semibold!">{{ __('Jabatan') }}</flux:table.column>
                <flux:table.column class="font-semibold!">{{ __('Status') }}</flux:table.column>
                <flux:table.column class="font-semibold!">{{ __('Aktif') }}</flux:table.column>
                <flux:table.column class="text-right font-semibold!">{{ __('Aksi') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->employees as $employee)
                    <flux:table.row wire:key="emp-{{ $employee->id }}" class="hover:bg-green-50/50! dark:hover:bg-green-900/10! transition-colors">
                        <flux:table.cell>
                            <div class="flex items-center gap-3">
                                @if ($employee->photo)
                                    <img src="{{ $employee->photo_url }}" alt="{{ $employee->name }}" class="w-11 h-11 rounded-xl object-cover ring-2 ring-white dark:ring-zinc-800 shadow-md" />
                                @else
                                    <div class="w-11 h-11 rounded-xl bg-linear-to-br from-green-500 to-emerald-600 flex items-center justify-center shadow-md">
                                        <span class="text-sm font-bold text-white">{{ substr($employee->name, 0, 1) }}</span>
                                    </div>
                                @endif
                                <div>
                                    <p class="font-semibold text-zinc-800 dark:text-white">{{ $employee->name }}</p>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                        {{ App\Models\Employee::GENDERS[$employee->gender] ?? $employee->gender }}
                                    </p>
                                </div>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="space-y-0.5">
                                @if ($employee->nip)
                                    <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">NIP: {{ $employee->nip }}</p>
                                @endif
                                @if ($employee->nuptk)
                                    <p class="text-xs text-zinc-400 dark:text-zinc-500">NUPTK: {{ $employee->nuptk }}</p>
                                @endif
                                @if (!$employee->nip && !$employee->nuptk)
                                    <span class="text-zinc-400">-</span>
                                @endif
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg {{ $employee->employee_type === 'guru' ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300' : 'bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-300' }} text-sm font-medium">
                                {{ App\Models\Employee::TYPES[$employee->employee_type] ?? $employee->employee_type }}
                            </span>
                        </flux:table.cell>
                        <flux:table.cell>
                            <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ $employee->position?->name ?? '-' }}</span>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge color="{{ $employee->employee_status === 'pns' ? 'green' : 'zinc' }}" class="rounded-lg!">
                                {{ App\Models\Employee::STATUSES[$employee->employee_status] ?? $employee->employee_status }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <button
                                type="button"
                                wire:click="toggleActive({{ $employee->id }})"
                                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 {{ $employee->is_active ? 'bg-green-500' : 'bg-zinc-300 dark:bg-zinc-600' }}"
                                role="switch"
                                aria-checked="{{ $employee->is_active ? 'true' : 'false' }}"
                                title="{{ $employee->is_active ? 'Nonaktifkan' : 'Aktifkan' }}"
                            >
                                <span class="sr-only">Toggle active</span>
                                <span class="{{ $employee->is_active ? 'translate-x-5' : 'translate-x-0' }} pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                            </button>
                        </flux:table.cell>
                        <flux:table.cell class="text-right">
                            <flux:dropdown position="bottom" align="end">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" class="rounded-lg! hover:bg-zinc-100! dark:hover:bg-zinc-800!" />
                                <flux:menu class="rounded-xl! shadow-xl! border-zinc!-200/50 dark:border-zinc!-700/50">
                                    <flux:menu.item :href="route('employees.show', $employee)" icon="eye" wire:navigate class="rounded-lg!">
                                        {{ __('Lihat Detail') }}
                                    </flux:menu.item>
                                    <flux:menu.item :href="route('employees.edit', $employee)" icon="pencil" wire:navigate class="rounded-lg!">
                                        {{ __('Edit') }}
                                    </flux:menu.item>
                                    <flux:menu.item wire:click="toggleActive({{ $employee->id }})" icon="{{ $employee->is_active ? 'x-circle' : 'check-circle' }}" class="rounded-lg!">
                                        {{ $employee->is_active ? __('Nonaktifkan') : __('Aktifkan') }}
                                    </flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item
                                        wire:click="delete({{ $employee->id }})"
                                        wire:confirm="{{ __('Apakah Anda yakin ingin menghapus data pegawai ini?') }}"
                                        icon="trash"
                                        variant="danger"
                                        class="rounded-lg!"
                                    >
                                        {{ __('Hapus') }}
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7" class="text-center py-12">
                            <div class="flex flex-col items-center gap-3">
                                <div class="w-16 h-16 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center">
                                    <svg class="size-8 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                </div>
                                <p class="text-zinc-500 dark:text-zinc-400">{{ __('Belum ada data pegawai.') }}</p>
                                <flux:button :href="route('employees.create')" variant="ghost" icon="plus" wire:navigate size="sm" class="rounded-lg!">
                                    {{ __('Tambah Pegawai Pertama') }}
                                </flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        @if ($this->employees->hasPages())
            <x-slot:footer>
                {{ $this->employees->links() }}
            </x-slot:footer>
        @endif
    </x-elegant-card>
</div>

@script
<script>
    $wire.on('scroll-to-top', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
</script>
@endscript
