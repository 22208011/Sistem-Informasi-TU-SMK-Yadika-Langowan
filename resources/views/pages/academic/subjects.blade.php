<?php

use App\Models\Subject;
use App\Models\Department;
use App\Models\Employee;
use App\Livewire\Concerns\WithNotification;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('Mata Pelajaran')] class extends Component {
    use WithPagination;
    use WithNotification;

    public string $search = '';
    public string $filterDepartment = '';
    public string $filterGradeLevel = '';

    // Form fields
    public bool $showModal = false;
    public ?int $editingId = null;
    public string $name = '';
    public string $code = '';
    public ?int $department_id = null;
    public string $grade_level = '';
    public string $description = '';
    public array $selectedTeachers = [];
    public bool $is_active = true;

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', 'unique:subjects,code,' . $this->editingId],
            'department_id' => ['nullable', 'exists:departments,id'],
            'grade_level' => ['nullable', 'in:10,11,12'],
            'description' => ['nullable', 'string', 'max:1000'],
            'selectedTeachers' => ['array'],
            'is_active' => ['boolean'],
        ];
    }

    protected $messages = [
        'name.required' => 'Nama mata pelajaran wajib diisi.',
        'code.required' => 'Kode mata pelajaran wajib diisi.',
        'code.unique' => 'Kode mata pelajaran sudah terdaftar.',
    ];

    #[Computed]
    public function subjects()
    {
        return Subject::query()
            ->with(['department', 'teachers'])
            ->when($this->search, fn($q) => $q->where(function($query) {
                $query->where('name', 'like', "%{$this->search}%")
                      ->orWhere('code', 'like', "%{$this->search}%");
            }))
            ->when($this->filterDepartment, fn($q) => $q->where('department_id', $this->filterDepartment))
            ->when($this->filterGradeLevel, fn($q) => $q->where('grade_level', $this->filterGradeLevel))
            ->orderBy('name')
            ->paginate(15);
    }

    #[Computed]
    public function departments()
    {
        return Department::active()->orderBy('name')->get();
    }

    #[Computed]
    public function teachers()
    {
        return Employee::teachers()->active()->orderBy('name')->get();
    }

    #[Computed]
    public function statistics()
    {
        return [
            'total' => Subject::count(),
            'active' => Subject::where('is_active', true)->count(),
            'general' => Subject::whereNull('department_id')->count(),
            'vocational' => Subject::whereNotNull('department_id')->count(),
        ];
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(Subject $subject): void
    {
        $this->editingId = $subject->id;
        $this->name = $subject->name;
        $this->code = $subject->code;
        $this->department_id = $subject->department_id;
        $this->grade_level = $subject->grade_level ?? '';
        $this->description = $subject->description ?? '';
        $this->selectedTeachers = $subject->teachers->pluck('id')->toArray();
        $this->is_active = (bool) $subject->is_active;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        try {
            $data = [
                'name' => $this->name,
                'code' => $this->code,
                'department_id' => $this->department_id ?: null,
                'grade_level' => $this->grade_level ?: null,
                'description' => $this->description ?: null,
                'is_active' => (bool) $this->is_active,
            ];

            if ($this->editingId) {
                $subject = Subject::findOrFail($this->editingId);
                $subject->update($data);
                $subject->teachers()->sync($this->selectedTeachers);
                $this->success('Mata pelajaran berhasil diperbarui.');
            } else {
                $subject = Subject::create($data);
                $subject->teachers()->sync($this->selectedTeachers);
                $this->success('Mata pelajaran berhasil ditambahkan.');
            }

            $this->closeModal();
        } catch (\Exception $e) {
            $this->error('Gagal menyimpan mata pelajaran: ' . $e->getMessage());
        }
    }

    public function toggleActive(Subject $subject): void
    {
        $subject->update(['is_active' => !$subject->is_active]);
        $status = $subject->is_active ? 'diaktifkan' : 'dinonaktifkan';
        $this->success("Mata pelajaran berhasil {$status}.");
    }

    public function delete(Subject $subject): void
    {
        try {
            $name = $subject->name;
            $subject->teachers()->detach();
            $subject->delete();
            $this->success("Mata pelajaran {$name} berhasil dihapus.");
        } catch (\Exception $e) {
            $this->error('Gagal menghapus mata pelajaran: ' . $e->getMessage());
        }
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->code = '';
        $this->department_id = null;
        $this->grade_level = '';
        $this->description = '';
        $this->selectedTeachers = [];
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
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
        </x-slot:icon>
        <x-slot:title>{{ __('Mata Pelajaran') }}</x-slot:title>
        <x-slot:subtitle>{{ __('Kelola data mata pelajaran dan pengampu.') }}</x-slot:subtitle>
        <x-slot:actions>
            @can('subjects.create')
            <flux:button wire:click="create" icon="plus" class="rounded-xl! bg-linear-to-r! from-blue-600! to-indigo-600! hover:from-blue-700! hover:to-indigo-700! shadow-lg! shadow-blue-500!/25">
                {{ __('Tambah Mata Pelajaran') }}
            </flux:button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    {{-- Notification --}}
    <x-notification-alert :type="$notificationType" :message="$notificationMessage" />

    <!-- Statistics Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6 mb-8">
        <x-stat-card title="Total Mapel" :value="$this->statistics['total']" color="blue" class="animate-fade-in-up">
            <x-slot:icon>
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card title="Mapel Aktif" :value="$this->statistics['active']" color="green" class="animate-fade-in-up delay-100">
            <x-slot:icon>
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card title="Mapel Umum" :value="$this->statistics['general']" color="purple" class="animate-fade-in-up delay-200">
            <x-slot:icon>
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card title="Mapel Produktif" :value="$this->statistics['vocational']" color="amber" class="animate-fade-in-up delay-300">
            <x-slot:icon>
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <!-- Data Table Card -->
    <x-elegant-card :noPadding="true" class="animate-fade-in-up delay-400">
        <x-slot:header>
            <div class="flex flex-col lg:flex-row gap-4 w-full">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('Cari mata pelajaran...') }}"
                    icon="magnifying-glass"
                    class="flex-1 max-w-sm rounded-xl! bg-zinc-50! dark:bg-zinc-800/50!"
                />

                <div class="flex flex-wrap gap-3">
                    <flux:select wire:model.live="filterDepartment" class="rounded-xl! bg-zinc-50! dark:bg-zinc-800/50! min-w-40">
                        <option value="">Semua Jurusan</option>
                        <option value="general">Umum (Semua Jurusan)</option>
                        @foreach ($this->departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->code }}</option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model.live="filterGradeLevel" class="rounded-xl! bg-zinc-50! dark:bg-zinc-800/50! min-w-35">
                        <option value="">Semua Tingkat</option>
                        <option value="10">Kelas X</option>
                        <option value="11">Kelas XI</option>
                        <option value="12">Kelas XII</option>
                    </flux:select>
                </div>
            </div>
        </x-slot:header>

        <flux:table class="table-elegant">
            <flux:table.columns>
                <flux:table.column class="font-semibold!">{{ __('Kode') }}</flux:table.column>
                <flux:table.column class="font-semibold!">{{ __('Nama Mata Pelajaran') }}</flux:table.column>
                <flux:table.column class="font-semibold!">{{ __('Jurusan') }}</flux:table.column>
                <flux:table.column class="font-semibold!">{{ __('Tingkat') }}</flux:table.column>
                <flux:table.column class="font-semibold!">{{ __('Pengampu') }}</flux:table.column>
                <flux:table.column class="font-semibold!">{{ __('Status') }}</flux:table.column>
                <flux:table.column class="text-right font-semibold!">{{ __('Aksi') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->subjects as $subject)
                    <flux:table.row wire:key="subj-{{ $subject->id }}" class="hover:bg-blue-50/50! dark:hover:bg-blue-900/10! transition-colors">
                        <flux:table.cell>
                            <span class="inline-flex items-center justify-center min-w-12 px-3 py-1.5 rounded-lg bg-linear-to-r from-blue-500 to-indigo-600 text-white text-sm font-bold shadow-sm">
                                {{ $subject->code }}
                            </span>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div>
                                <p class="font-semibold text-zinc-800 dark:text-white">{{ $subject->name }}</p>
                                @if ($subject->description)
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ Str::limit($subject->description, 50) }}</p>
                                @endif
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if ($subject->department)
                                <flux:badge color="purple" class="rounded-lg!">{{ $subject->department->code }}</flux:badge>
                            @else
                                <flux:badge color="zinc" class="rounded-lg!">Umum</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            @if ($subject->grade_level)
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Kelas {{ $subject->grade_level }}</span>
                            @else
                                <span class="text-sm text-zinc-400">Semua</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex -space-x-2">
                                @forelse ($subject->teachers->take(3) as $teacher)
                                    <div class="w-8 h-8 rounded-full bg-linear-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white text-xs font-bold ring-2 ring-white dark:ring-zinc-800" title="{{ $teacher->name }}">
                                        {{ substr($teacher->name, 0, 1) }}
                                    </div>
                                @empty
                                    <span class="text-sm text-zinc-400">-</span>
                                @endforelse
                                @if ($subject->teachers->count() > 3)
                                    <div class="w-8 h-8 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center text-zinc-600 dark:text-zinc-300 text-xs font-bold ring-2 ring-white dark:ring-zinc-800">
                                        +{{ $subject->teachers->count() - 3 }}
                                    </div>
                                @endif
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <button
                                type="button"
                                wire:click="toggleActive({{ $subject->id }})"
                                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 {{ $subject->is_active ? 'bg-green-500' : 'bg-zinc-300 dark:bg-zinc-600' }}"
                                role="switch"
                                aria-checked="{{ $subject->is_active ? 'true' : 'false' }}"
                                title="{{ $subject->is_active ? 'Nonaktifkan' : 'Aktifkan' }}"
                            >
                                <span class="sr-only">Toggle active</span>
                                <span class="{{ $subject->is_active ? 'translate-x-5' : 'translate-x-0' }} pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                            </button>
                        </flux:table.cell>
                        <flux:table.cell class="text-right">
                            <flux:dropdown position="bottom" align="end">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" class="rounded-lg! hover:bg-zinc-100! dark:hover:bg-zinc-800!" />
                                <flux:menu class="rounded-xl! shadow-xl! border-zinc!-200/50 dark:border-zinc!-700/50">
                                    @can('subjects.edit')
                                    <flux:menu.item wire:click="edit({{ $subject->id }})" icon="pencil" class="rounded-lg!">
                                        {{ __('Edit') }}
                                    </flux:menu.item>
                                    @endcan
                                    <flux:menu.item wire:click="toggleActive({{ $subject->id }})" icon="{{ $subject->is_active ? 'x-circle' : 'check-circle' }}" class="rounded-lg!">
                                        {{ $subject->is_active ? __('Nonaktifkan') : __('Aktifkan') }}
                                    </flux:menu.item>
                                    @can('subjects.delete')
                                    <flux:menu.separator />
                                    <flux:menu.item
                                        wire:click="delete({{ $subject->id }})"
                                        wire:confirm="{{ __('Apakah Anda yakin ingin menghapus mata pelajaran ini?') }}"
                                        icon="trash"
                                        variant="danger"
                                        class="rounded-lg!"
                                    >
                                        {{ __('Hapus') }}
                                    </flux:menu.item>
                                    @endcan
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
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                    </svg>
                                </div>
                                <p class="text-zinc-500 dark:text-zinc-400">{{ __('Belum ada data mata pelajaran.') }}</p>
                                @can('subjects.create')
                                <flux:button wire:click="create" variant="ghost" icon="plus" size="sm" class="rounded-lg!">
                                    {{ __('Tambah Mata Pelajaran Pertama') }}
                                </flux:button>
                                @endcan
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        @if ($this->subjects->hasPages())
            <x-slot:footer>
                {{ $this->subjects->links() }}
            </x-slot:footer>
        @endif
    </x-elegant-card>

    <!-- Modal Form -->
    <flux:modal wire:model="showModal" class="max-w-xl">
        <form wire:submit="save">
            <flux:modal.header class="border-b border-zinc-100 dark:border-zinc-800">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-linear-to-br from-blue-500 to-indigo-600 text-white">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <flux:heading size="lg">
                        {{ $editingId ? __('Edit Mata Pelajaran') : __('Tambah Mata Pelajaran') }}
                    </flux:heading>
                </div>
            </flux:modal.header>

            <flux:modal.body class="space-y-4 py-6">
                <div class="grid grid-cols-3 gap-4">
                    <flux:input
                        wire:model="code"
                        label="{{ __('Kode') }}"
                        placeholder="MTK"
                        required
                        class="rounded-xl!"
                    />

                    <div class="col-span-2">
                        <flux:input
                            wire:model="name"
                            label="{{ __('Nama Mata Pelajaran') }}"
                            placeholder="Matematika"
                            required
                            class="rounded-xl!"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:select wire:model="department_id" label="{{ __('Jurusan (opsional)') }}" class="rounded-xl!">
                        <option value="">Umum (Semua Jurusan)</option>
                        @foreach ($this->departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->code }} - {{ $dept->name }}</option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="grade_level" label="{{ __('Tingkat Kelas') }}" class="rounded-xl!">
                        <option value="">Semua Tingkat</option>
                        <option value="10">Kelas X</option>
                        <option value="11">Kelas XI</option>
                        <option value="12">Kelas XII</option>
                    </flux:select>
                </div>

                <flux:textarea
                    wire:model="description"
                    label="{{ __('Deskripsi') }}"
                    rows="3"
                    class="rounded-xl!"
                />

                <div>
                    <flux:label>{{ __('Guru Pengampu') }}</flux:label>
                    <div class="mt-2 max-h-40 overflow-y-auto space-y-2 p-3 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl">
                        @foreach ($this->teachers as $teacher)
                            <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-white dark:hover:bg-zinc-700 cursor-pointer transition-colors">
                                <input
                                    type="checkbox"
                                    wire:model="selectedTeachers"
                                    value="{{ $teacher->id }}"
                                    class="w-4 h-4 rounded border-zinc-300 text-blue-600 focus:ring-blue-500"
                                />
                                <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $teacher->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="p-4 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium text-zinc-700 dark:text-zinc-300">{{ __('Mata Pelajaran Aktif') }}</p>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Mata pelajaran aktif dapat digunakan dalam jadwal.') }}</p>
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
                <flux:button type="submit" class="rounded-xl! bg-linear-to-r! from-blue-600! to-indigo-600!">
                    <svg class="size-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    {{ $editingId ? __('Perbarui') : __('Simpan') }}
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
