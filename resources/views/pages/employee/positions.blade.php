<?php

use App\Models\Position;
use App\Livewire\Concerns\WithNotification;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('Jabatan')] class extends Component {
    use WithPagination;
    use WithNotification;

    public bool $showModal = false;
    public ?Position $editing = null;

    public string $name = '';
    public string $code = '';
    public string $category = 'fungsional';
    public string $description = '';
    public bool $is_active = true;

    public string $search = '';

    public function rules(): array
    {
        $uniqueRule = $this->editing
            ? 'unique:positions,code,' . $this->editing->id
            : 'unique:positions,code';

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', $uniqueRule],
            'category' => ['required', 'in:struktural,fungsional'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }

    protected $messages = [
        'name.required' => 'Nama jabatan wajib diisi.',
        'code.required' => 'Kode jabatan wajib diisi.',
        'code.unique' => 'Kode jabatan sudah terdaftar.',
        'category.required' => 'Kategori jabatan wajib dipilih.',
    ];

    #[Computed]
    public function positions()
    {
        return Position::query()
            ->withCount('employees')
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('code', 'like', "%{$this->search}%"))
            ->orderBy('category')
            ->orderBy('name')
            ->paginate(15);
    }

    #[Computed]
    public function statistics()
    {
        return [
            'total' => Position::count(),
            'active' => Position::active()->count(),
            'struktural' => Position::where('category', 'struktural')->count(),
            'fungsional' => Position::where('category', 'fungsional')->count(),
        ];
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(Position $position): void
    {
        $this->editing = $position;
        $this->name = $position->name;
        $this->code = $position->code;
        $this->category = $position->category;
        $this->description = $position->description ?? '';
        $this->is_active = (bool) $position->is_active;
        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();
        $validated['is_active'] = (bool) $this->is_active;

        try {
            if ($this->editing) {
                $this->editing->update($validated);
                $this->success('Jabatan berhasil diperbarui.');
            } else {
                Position::create($validated);
                $this->success('Jabatan berhasil ditambahkan.');
            }

            $this->resetForm();
            $this->showModal = false;
        } catch (\Exception $e) {
            $this->error('Gagal menyimpan jabatan: ' . $e->getMessage());
        }
    }

    public function toggleActive(Position $position): void
    {
        $position->update(['is_active' => !$position->is_active]);
        $status = $position->is_active ? 'diaktifkan' : 'dinonaktifkan';
        $this->success("Jabatan berhasil {$status}.");
    }

    public function delete(Position $position): void
    {
        if ($position->employees()->exists()) {
            $this->error('Tidak dapat menghapus jabatan yang masih memiliki pegawai.');
            return;
        }

        try {
            $name = $position->name;
            $position->delete();
            $this->success("Jabatan {$name} berhasil dihapus.");
        } catch (\Exception $e) {
            $this->error('Gagal menghapus jabatan: ' . $e->getMessage());
        }
    }

    public function resetForm(): void
    {
        $this->editing = null;
        $this->name = '';
        $this->code = '';
        $this->category = 'fungsional';
        $this->description = '';
        $this->is_active = true;
        $this->resetErrorBag();
    }

    public function closeModal(): void
    {
        $this->resetForm();
        $this->showModal = false;
    }

    public function toggleActiveForm(): void
    {
        $this->is_active = !$this->is_active;
    }
}; ?>

<div>
    <!-- Page Header -->
    <x-page-header>
        <x-slot:icon>
            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
        </x-slot:icon>
        <x-slot:title>{{ __('Jabatan') }}</x-slot:title>
        <x-slot:subtitle>{{ __('Kelola data jabatan pegawai sekolah.') }}</x-slot:subtitle>
        <x-slot:actions>
            <flux:button :href="route('employees.index')" variant="ghost" icon="arrow-left" wire:navigate class="rounded-xl!">
                {{ __('Kembali') }}
            </flux:button>
            <flux:button wire:click="create" icon="plus" class="rounded-xl! bg-linear-to-r! from-purple-600! to-indigo-600! hover:from-purple-700! hover:to-indigo-700! shadow-lg! shadow-purple-500!/25">
                {{ __('Tambah Jabatan') }}
            </flux:button>
        </x-slot:actions>
    </x-page-header>

    {{-- Notification --}}
    <x-notification-alert :type="$notificationType" :message="$notificationMessage" />

    <!-- Statistics Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6 mb-8">
        <x-stat-card title="Total Jabatan" :value="$this->statistics['total']" color="purple" class="animate-fade-in-up">
            <x-slot:icon>
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card title="Jabatan Aktif" :value="$this->statistics['active']" color="green" class="animate-fade-in-up delay-100">
            <x-slot:icon>
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card title="Struktural" :value="$this->statistics['struktural']" color="blue" class="animate-fade-in-up delay-200">
            <x-slot:icon>
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card title="Fungsional" :value="$this->statistics['fungsional']" color="amber" class="animate-fade-in-up delay-300">
            <x-slot:icon>
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <!-- Data Table Card -->
    <x-elegant-card :noPadding="true" class="animate-fade-in-up delay-400">
        <x-slot:header>
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="{{ __('Cari jabatan...') }}"
                icon="magnifying-glass"
                class="max-w-xs rounded-xl! bg-zinc-50! dark:bg-zinc-800/50!"
            />
        </x-slot:header>

        <flux:table class="table-elegant">
            <flux:table.columns>
                <flux:table.column class="font-semibold!">{{ __('Kode') }}</flux:table.column>
                <flux:table.column class="font-semibold!">{{ __('Nama Jabatan') }}</flux:table.column>
                <flux:table.column class="font-semibold!">{{ __('Kategori') }}</flux:table.column>
                <flux:table.column class="font-semibold!">{{ __('Jumlah Pegawai') }}</flux:table.column>
                <flux:table.column class="font-semibold!">{{ __('Status') }}</flux:table.column>
                <flux:table.column class="text-right font-semibold!">{{ __('Aksi') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->positions as $position)
                    <flux:table.row wire:key="pos-{{ $position->id }}" class="hover:bg-purple-50/50! dark:hover:bg-purple-900/10! transition-colors">
                        <flux:table.cell>
                            <span class="inline-flex items-center justify-center min-w-12 px-3 py-1.5 rounded-lg bg-linear-to-r from-purple-500 to-indigo-600 text-white text-sm font-bold shadow-sm">
                                {{ $position->code }}
                            </span>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div>
                                <p class="font-semibold text-zinc-800 dark:text-white">{{ $position->name }}</p>
                                @if ($position->description)
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ Str::limit($position->description, 50) }}</p>
                                @endif
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge color="{{ $position->category === 'struktural' ? 'blue' : 'amber' }}" class="rounded-lg!">
                                {{ App\Models\Position::CATEGORIES[$position->category] ?? ucfirst($position->category) }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 text-sm font-medium">
                                {{ $position->employees_count }} orang
                            </span>
                        </flux:table.cell>
                        <flux:table.cell>
                            <button
                                type="button"
                                wire:click="toggleActive({{ $position->id }})"
                                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 {{ $position->is_active ? 'bg-green-500' : 'bg-zinc-300 dark:bg-zinc-600' }}"
                                role="switch"
                                aria-checked="{{ $position->is_active ? 'true' : 'false' }}"
                                title="{{ $position->is_active ? 'Nonaktifkan' : 'Aktifkan' }}"
                            >
                                <span class="sr-only">Toggle active</span>
                                <span class="{{ $position->is_active ? 'translate-x-5' : 'translate-x-0' }} pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                            </button>
                        </flux:table.cell>
                        <flux:table.cell class="text-right">
                            <flux:dropdown position="bottom" align="end">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" class="rounded-lg! hover:bg-zinc-100! dark:hover:bg-zinc-800!" />
                                <flux:menu class="rounded-xl! shadow-xl! border-zinc!-200/50 dark:border-zinc!-700/50">
                                    <flux:menu.item wire:click="edit({{ $position->id }})" icon="pencil" class="rounded-lg!">
                                        {{ __('Edit') }}
                                    </flux:menu.item>
                                    <flux:menu.item wire:click="toggleActive({{ $position->id }})" icon="{{ $position->is_active ? 'x-circle' : 'check-circle' }}" class="rounded-lg!">
                                        {{ $position->is_active ? __('Nonaktifkan') : __('Aktifkan') }}
                                    </flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item
                                        wire:click="delete({{ $position->id }})"
                                        wire:confirm="{{ __('Apakah Anda yakin ingin menghapus jabatan ini?') }}"
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
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <p class="text-zinc-500 dark:text-zinc-400">{{ __('Belum ada data jabatan.') }}</p>
                                <flux:button wire:click="create" variant="ghost" icon="plus" size="sm" class="rounded-lg!">
                                    {{ __('Tambah Jabatan Pertama') }}
                                </flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        @if ($this->positions->hasPages())
            <x-slot:footer>
                {{ $this->positions->links() }}
            </x-slot:footer>
        @endif
    </x-elegant-card>

    <!-- Modal Form -->
    <flux:modal wire:model="showModal" class="max-w-lg">
        <form wire:submit="save">
            <flux:modal.header class="border-b border-zinc-100 dark:border-zinc-800">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-linear-to-br from-purple-500 to-indigo-600 text-white">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <flux:heading size="lg">
                        {{ $editing ? __('Edit Jabatan') : __('Tambah Jabatan') }}
                    </flux:heading>
                </div>
            </flux:modal.header>

            <flux:modal.body class="space-y-4 py-6">
                <div class="grid grid-cols-3 gap-4">
                    <flux:input
                        wire:model="code"
                        label="{{ __('Kode') }}"
                        placeholder="KS"
                        required
                        class="rounded-xl!"
                    />

                    <div class="col-span-2">
                        <flux:input
                            wire:model="name"
                            label="{{ __('Nama Jabatan') }}"
                            placeholder="Kepala Sekolah"
                            required
                            class="rounded-xl!"
                        />
                    </div>
                </div>

                <flux:select wire:model="category" label="{{ __('Kategori') }}" required class="rounded-xl!">
                    @foreach (App\Models\Position::CATEGORIES as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </flux:select>

                <flux:textarea
                    wire:model="description"
                    label="{{ __('Deskripsi') }}"
                    rows="3"
                    class="rounded-xl!"
                />

                <div class="p-4 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium text-zinc-700 dark:text-zinc-300">{{ __('Jabatan Aktif') }}</p>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Jabatan aktif dapat digunakan untuk pegawai.') }}</p>
                        </div>
                        <button
                            type="button"
                            wire:click="toggleActiveForm"
                            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 {{ $is_active ? 'bg-green-500' : 'bg-zinc-300 dark:bg-zinc-600' }}"
                            role="switch"
                            aria-checked="{{ $is_active ? 'true' : 'false' }}"
                        >
                            <span class="sr-only">Toggle active status</span>
                            <span class="{{ $is_active ? 'translate-x-5' : 'translate-x-0' }} pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                        </button>
                    </div>
                </div>
            </flux:modal.body>

            <flux:modal.footer class="border-t border-zinc-100 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-800/50">
                <flux:button type="button" wire:click="closeModal" variant="ghost" class="rounded-xl!">
                    {{ __('Batal') }}
                </flux:button>
                <flux:button type="submit" class="rounded-xl! bg-linear-to-r! from-purple-600! to-indigo-600!">
                    <svg class="size-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
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
