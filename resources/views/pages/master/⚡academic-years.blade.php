<?php

use App\Models\AcademicYear;
use App\Livewire\Concerns\WithNotification;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('Tahun Ajaran')] class extends Component {
    use WithPagination;
    use WithNotification;

    public bool $showModal = false;
    public ?AcademicYear $editing = null;

    public string $name = '';
    public string $start_date = '';
    public string $end_date = '';
    public string $semester = 'ganjil';
    public bool $is_active = false;

    public string $search = '';

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:20'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'semester' => ['required', 'in:ganjil,genap'],
            'is_active' => ['boolean'],
        ];
    }

    #[Computed]
    public function academicYears()
    {
        return AcademicYear::query()
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderByDesc('name')
            ->orderBy('semester')
            ->paginate(10);
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(AcademicYear $academicYear): void
    {
        $this->editing = $academicYear;
        $this->name = $academicYear->name;
        $this->start_date = $academicYear->start_date->format('Y-m-d');
        $this->end_date = $academicYear->end_date->format('Y-m-d');
        $this->semester = $academicYear->semester;
        $this->is_active = $academicYear->is_active;
        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->editing) {
            $this->editing->update($validated);

            if ($validated['is_active']) {
                $this->editing->setAsActive();
            }

            $this->success('Tahun ajaran berhasil diperbarui.');
        } else {
            $academicYear = AcademicYear::create($validated);

            if ($validated['is_active']) {
                $academicYear->setAsActive();
            }

            $this->success('Tahun ajaran berhasil ditambahkan.');
        }

        $this->resetForm();
        $this->showModal = false;
    }

    public function delete(AcademicYear $academicYear): void
    {
        $academicYear->delete();
        $this->success('Tahun ajaran berhasil dihapus.');
    }

    public function setActive(AcademicYear $academicYear): void
    {
        $academicYear->setAsActive();
        $this->success('Tahun ajaran aktif berhasil diubah.');
    }

    public function resetForm(): void
    {
        $this->editing = null;
        $this->name = '';
        $this->start_date = '';
        $this->end_date = '';
        $this->semester = 'ganjil';
        $this->is_active = false;
        $this->resetErrorBag();
    }

    public function closeModal(): void
    {
        $this->resetForm();
        $this->showModal = false;
    }
}; ?>

<div>
    <!-- Page Header with Animation -->
    <x-page-header>
        <x-slot:icon>
            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
        </x-slot:icon>
        <x-slot:title>{{ __('Tahun Ajaran') }}</x-slot:title>
        <x-slot:subtitle>{{ __('Kelola periode akademik dan semester untuk tahun ajaran sekolah.') }}</x-slot:subtitle>
        <x-slot:actions>
            <flux:button wire:click="create" icon="plus" class="rounded-xl! bg-linear-to-r! from-blue-600! to-indigo-600! hover:from-blue-700! hover:to-indigo-700! shadow-lg! shadow-blue-500!/25 hover:shadow-xl! hover:shadow-blue-500!/30 hover:scale-105! transition-all! duration-300!">
                {{ __('Tambah Tahun Ajaran') }}
            </flux:button>
        </x-slot:actions>
    </x-page-header>

    {{-- Notification --}}
    <x-notification-alert :type="$notificationType" :message="$notificationMessage" />

    <!-- Active Year Highlight -->
    @php $activeYear = $this->academicYears->firstWhere('is_active', true); @endphp
    @if($activeYear)
        <div class="mb-6 animate-fade-in-up">
            <div class="relative overflow-hidden rounded-xl bg-linear-to-r from-green-500 via-emerald-500 to-teal-500 p-6 text-white shadow-lg">
                <div class="absolute -right-10 -top-10 h-32 w-32 rounded-full bg-white/10 blur-2xl"></div>
                <div class="relative flex items-center gap-4">
                    <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-white/20 backdrop-blur-sm">
                        <svg class="size-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-white/80">Tahun Ajaran Aktif</p>
                        <h3 class="text-2xl font-bold">{{ $activeYear->name }} - Semester {{ ucfirst($activeYear->semester) }}</h3>
                        <p class="text-sm text-white/70">{{ $activeYear->start_date->format('d M Y') }} - {{ $activeYear->end_date->format('d M Y') }}</p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Data Table Card -->
    <x-elegant-card :noPadding="true" class="animate-fade-in-up delay-100">
        <x-slot:header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 w-full">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('Cari tahun ajaran...') }}"
                    icon="magnifying-glass"
                    class="max-w-xs rounded-xl! bg-zinc-50! dark:bg-zinc-800/50!"
                />
            </div>
        </x-slot:header>

        <flux:table class="table-elegant">
            <flux:table.columns>
                <flux:table.column class="font-semibold!">{{ __('Tahun Ajaran') }}</flux:table.column>
                <flux:table.column class="font-semibold!">{{ __('Semester') }}</flux:table.column>
                <flux:table.column class="font-semibold!">{{ __('Tanggal Mulai') }}</flux:table.column>
                <flux:table.column class="font-semibold!">{{ __('Tanggal Selesai') }}</flux:table.column>
                <flux:table.column class="font-semibold!">{{ __('Status') }}</flux:table.column>
                <flux:table.column class="text-right font-semibold!">{{ __('Aksi') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->academicYears as $year)
                    <flux:table.row wire:key="year-{{ $year->id }}" class="hover:bg-blue-50/50! dark:hover:bg-blue-900/10! transition-colors">
                        <flux:table.cell>
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg {{ $year->is_active ? 'bg-linear-to-br from-green-500 to-emerald-600' : 'bg-linear-to-br from-blue-500 to-indigo-600' }} text-white shadow-md">
                                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <span class="font-semibold text-zinc-800 dark:text-white">{{ $year->name }}</span>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg {{ $year->semester === 'ganjil' ? 'bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-300' : 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300' }} text-sm font-medium">
                                {{ ucfirst($year->semester) }}
                            </span>
                        </flux:table.cell>
                        <flux:table.cell>
                            <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ $year->start_date->format('d M Y') }}</span>
                        </flux:table.cell>
                        <flux:table.cell>
                            <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ $year->end_date->format('d M Y') }}</span>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if ($year->is_active)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 text-sm font-medium">
                                    <span class="h-2 w-2 rounded-full bg-green-500 animate-pulse"></span>
                                    Aktif
                                </span>
                            @else
                                <flux:badge color="zinc" class="rounded-lg!">Tidak Aktif</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="text-right">
                            <flux:dropdown position="bottom" align="end">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" class="rounded-lg! hover:bg-zinc-100! dark:hover:bg-zinc-800!" />

                                <flux:menu class="rounded-xl! shadow-xl! border-zinc!-200/50 dark:border-zinc!-700/50">
                                    <flux:menu.item wire:click="edit({{ $year->id }})" icon="pencil" class="rounded-lg!">
                                        {{ __('Edit') }}
                                    </flux:menu.item>
                                    @if (!$year->is_active)
                                        <flux:menu.item wire:click="setActive({{ $year->id }})" icon="check-circle" class="rounded-lg!">
                                            {{ __('Set Aktif') }}
                                        </flux:menu.item>
                                    @endif
                                    <flux:menu.separator />
                                    <flux:menu.item
                                        wire:click="delete({{ $year->id }})"
                                        wire:confirm="{{ __('Apakah Anda yakin ingin menghapus tahun ajaran ini?') }}"
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
                        <flux:table.cell colspan="6" class="text-center py-12">
                            <div class="flex flex-col items-center gap-3">
                                <div class="w-16 h-16 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center">
                                    <svg class="size-8 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <p class="text-zinc-500 dark:text-zinc-400">{{ __('Belum ada data tahun ajaran.') }}</p>
                                <flux:button wire:click="create" variant="ghost" icon="plus" size="sm" class="rounded-lg!">
                                    {{ __('Tambah Tahun Ajaran Pertama') }}
                                </flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        @if ($this->academicYears->hasPages())
            <x-slot:footer>
                {{ $this->academicYears->links() }}
            </x-slot:footer>
        @endif
    </x-elegant-card>

    <!-- Modal Form with Elegant Style -->
    <flux:modal wire:model="showModal" class="max-w-lg">
        <form wire:submit="save">
            <flux:modal.header class="border-b border-zinc-100 dark:border-zinc-800">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-linear-to-br from-blue-500 to-indigo-600 text-white">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <flux:heading size="lg">
                        {{ $editing ? __('Edit Tahun Ajaran') : __('Tambah Tahun Ajaran') }}
                    </flux:heading>
                </div>
            </flux:modal.header>

            <flux:modal.body class="space-y-4 py-6">
                <flux:input
                    wire:model="name"
                    label="{{ __('Tahun Ajaran') }}"
                    placeholder="2025/2026"
                    required
                    class="rounded-xl!"
                />

                <flux:select wire:model="semester" label="{{ __('Semester') }}" required class="rounded-xl!">
                    <option value="ganjil">Ganjil (Semester 1)</option>
                    <option value="genap">Genap (Semester 2)</option>
                </flux:select>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input
                        wire:model="start_date"
                        label="{{ __('Tanggal Mulai') }}"
                        type="date"
                        required
                        class="rounded-xl!"
                    />

                    <flux:input
                        wire:model="end_date"
                        label="{{ __('Tanggal Selesai') }}"
                        type="date"
                        required
                        class="rounded-xl!"
                    />
                </div>

                <div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-800">
                    <flux:checkbox
                        wire:model="is_active"
                        label="{{ __('Set sebagai tahun ajaran aktif') }}"
                    />
                    <p class="text-xs text-amber-600 dark:text-amber-400 mt-2">
                        Mengaktifkan ini akan menonaktifkan tahun ajaran lainnya.
                    </p>
                </div>
            </flux:modal.body>

            <flux:modal.footer class="border-t border-zinc-100 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-800/50">
                <flux:button type="button" wire:click="closeModal" variant="ghost" class="rounded-xl!">
                    {{ __('Batal') }}
                </flux:button>
                <flux:button type="submit" class="rounded-xl! bg-linear-to-r! from-blue-600! to-indigo-600!">
                    {{ $editing ? __('Perbarui') : __('Simpan') }}
                </flux:button>
            </flux:modal.footer>
        </form>
    </flux:modal>
</div>

@script
<script>
    $wire.on('scroll-to-top', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
</script>
@endscript