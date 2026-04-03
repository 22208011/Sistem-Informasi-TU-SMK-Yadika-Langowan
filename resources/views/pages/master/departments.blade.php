<?php

use App\Models\Department;
use App\Models\Employee;
use App\Livewire\Concerns\WithNotification;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('Jurusan')] class extends Component {
    use WithPagination;
    use WithNotification;

    public bool $showModal = false;
    public ?Department $editing = null;

    public string $code = '';
    public string $name = '';
    public string $skill_competency = '';
    public string $description = '';
    public ?int $head_id = null;
    public bool $is_active = true;

    public string $search = '';

    public function rules(): array
    {
        $uniqueRule = $this->editing
            ? 'unique:departments,code,' . $this->editing->id
            : 'unique:departments,code';

        return [
            'code' => ['required', 'string', 'max:20', $uniqueRule],
            'name' => ['required', 'string', 'max:255'],
            'skill_competency' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'head_id' => ['nullable', 'exists:employees,id'],
            'is_active' => ['boolean'],
        ];
    }

    #[Computed]
    public function departments()
    {
        return Department::query()
            ->with('head')
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('code', 'like', "%{$this->search}%"))
            ->orderBy('code')
            ->paginate(10);
    }

    #[Computed]
    public function employees()
    {
        return Employee::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(Department $department): void
    {
        $this->editing = $department;
        $this->code = $department->code;
        $this->name = $department->name;
        $this->skill_competency = $department->skill_competency ?? '';
        $this->description = $department->description ?? '';
        $this->head_id = $department->head_id;
        $this->is_active = $department->is_active;
        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->editing) {
            $this->editing->update($validated);
            $this->success('Jurusan berhasil diperbarui.');
        } else {
            Department::create($validated);
            $this->success('Jurusan berhasil ditambahkan.');
        }

        $this->resetForm();
        $this->showModal = false;
    }

    public function delete(Department $department): void
    {
        if ($department->classrooms()->exists()) {
            $this->error('Tidak dapat menghapus jurusan yang masih memiliki kelas.');
            return;
        }

        $department->delete();
        $this->success('Jurusan berhasil dihapus.');
    }

    public function resetForm(): void
    {
        $this->editing = null;
        $this->code = '';
        $this->name = '';
        $this->skill_competency = '';
        $this->description = '';
        $this->head_id = null;
        $this->is_active = true;
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
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
        </x-slot:icon>
        <x-slot:title>{{ __('Jurusan / Kompetensi Keahlian') }}</x-slot:title>
        <x-slot:subtitle>{{ __('Kelola data jurusan dan program keahlian di SMK.') }}</x-slot:subtitle>
        <x-slot:actions>
            <flux:button wire:click="create" icon="plus" class="rounded-xl! bg-linear-to-r! from-purple-600! to-indigo-600! hover:from-purple-700! hover:to-indigo-700! shadow-lg! shadow-purple-500!/25 hover:shadow-xl! hover:shadow-purple-500!/30 hover:scale-105! transition-all! duration-300!">
                {{ __('Tambah Jurusan') }}
            </flux:button>
        </x-slot:actions>
    </x-page-header>

    {{-- Notification --}}
    <x-notification-alert :type="$notificationType" :message="$notificationMessage" />

    <!-- Statistics Summary -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6 animate-fade-in-up">
        <x-stat-card title="Total Jurusan" :value="$this->departments->total()" color="purple">
            <x-slot:icon>
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </x-slot:icon>
        </x-stat-card>
        <x-stat-card title="Jurusan Aktif" :value="$this->departments->where('is_active', true)->count()" color="green">
            <x-slot:icon>
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <!-- Data Table Card -->
    <x-elegant-card :noPadding="true" class="animate-fade-in-up delay-100">
        <x-slot:header>
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="{{ __('Cari jurusan...') }}"
                icon="magnifying-glass"
                class="max-w-xs rounded-xl! bg-zinc-50! dark:bg-zinc-800/50!"
            />
        </x-slot:header>

        <flux:table class="table-elegant">
            <flux:table.columns>
                <flux:table.column class="font-semibold!">{{ __('Kode') }}</flux:table.column>
                <flux:table.column class="font-semibold!">{{ __('Nama Jurusan') }}</flux:table.column>
                <flux:table.column class="font-semibold!">{{ __('Kompetensi Keahlian') }}</flux:table.column>
                <flux:table.column class="font-semibold!">{{ __('Ketua Jurusan') }}</flux:table.column>
                <flux:table.column class="font-semibold!">{{ __('Status') }}</flux:table.column>
                <flux:table.column class="text-right font-semibold!">{{ __('Aksi') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->departments as $department)
                    <flux:table.row wire:key="dept-{{ $department->id }}" class="hover:bg-purple-50/50! dark:hover:bg-purple-900/10! transition-colors">
                        <flux:table.cell>
                            <span class="inline-flex items-center justify-center min-w-12 px-3 py-1.5 rounded-lg bg-linear-to-r from-purple-500 to-indigo-600 text-white text-sm font-bold shadow-sm">
                                {{ $department->code }}
                            </span>
                        </flux:table.cell>
                        <flux:table.cell>
                            <p class="font-semibold text-zinc-800 dark:text-white">{{ $department->name }}</p>
                        </flux:table.cell>
                        <flux:table.cell>
                            <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ $department->skill_competency ?? '-' }}</span>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($department->head)
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full bg-linear-to-br from-purple-500 to-indigo-600 flex items-center justify-center text-white text-xs font-bold">
                                        {{ substr($department->head->name, 0, 1) }}
                                    </div>
                                    <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $department->head->name }}</span>
                                </div>
                            @else
                                <span class="text-sm text-zinc-400">-</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            @if ($department->is_active)
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
                                    <flux:menu.item wire:click="edit({{ $department->id }})" icon="pencil" class="rounded-lg!">
                                        {{ __('Edit') }}
                                    </flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item
                                        wire:click="delete({{ $department->id }})"
                                        wire:confirm="{{ __('Apakah Anda yakin ingin menghapus jurusan ini?') }}"
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
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                                <p class="text-zinc-500 dark:text-zinc-400">{{ __('Belum ada data jurusan.') }}</p>
                                <flux:button wire:click="create" variant="ghost" icon="plus" size="sm" class="rounded-lg!">
                                    {{ __('Tambah Jurusan Pertama') }}
                                </flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        @if ($this->departments->hasPages())
            <x-slot:footer>
                {{ $this->departments->links() }}
            </x-slot:footer>
        @endif
    </x-elegant-card>

    <!-- Modal Form with Elegant Style -->
    <flux:modal wire:model="showModal" class="max-w-lg">
        <form wire:submit="save">
            <flux:modal.header class="border-b border-zinc-100 dark:border-zinc-800">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-linear-to-br from-purple-500 to-indigo-600 text-white">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <flux:heading size="lg">
                        {{ $editing ? __('Edit Jurusan') : __('Tambah Jurusan') }}
                    </flux:heading>
                </div>
            </flux:modal.header>

            <flux:modal.body class="space-y-4 py-6">
                <div class="grid grid-cols-3 gap-4">
                    <flux:input
                        wire:model="code"
                        label="{{ __('Kode') }}"
                        placeholder="RPL"
                        required
                        class="rounded-xl!"
                    />

                    <div class="col-span-2">
                        <flux:input
                            wire:model="name"
                            label="{{ __('Nama Jurusan') }}"
                            placeholder="Rekayasa Perangkat Lunak"
                            required
                            class="rounded-xl!"
                        />
                    </div>
                </div>

                <flux:input
                    wire:model="skill_competency"
                    label="{{ __('Kompetensi Keahlian') }}"
                    placeholder="Pengembangan Perangkat Lunak dan Gim"
                    class="rounded-xl!"
                />

                <flux:textarea
                    wire:model="description"
                    label="{{ __('Deskripsi') }}"
                    rows="3"
                    class="rounded-xl!"
                />

                <flux:select wire:model="head_id" label="{{ __('Ketua Jurusan') }}" class="rounded-xl!">
                    <option value="">-- Pilih Ketua Jurusan --</option>
                    @foreach ($this->employees as $employee)
                        <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                    @endforeach
                </flux:select>

                <div class="p-4 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl">
                    <flux:checkbox
                        wire:model="is_active"
                        label="{{ __('Jurusan aktif') }}"
                    />
                </div>
            </flux:modal.body>

            <flux:modal.footer class="border-t border-zinc-100 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-800/50">
                <flux:button type="button" wire:click="closeModal" variant="ghost" class="rounded-xl!">
                    {{ __('Batal') }}
                </flux:button>
                <flux:button type="submit" class="rounded-xl! bg-linear-to-r! from-purple-600! to-indigo-600!">
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