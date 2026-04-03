<?php

use App\Models\Extracurricular;
use App\Models\ExtracurricularMember;
use App\Models\Student;
use App\Models\Employee;
use App\Livewire\Concerns\WithNotification;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('Ekstrakurikuler')] class extends Component {
    use WithPagination;
    use WithNotification;

    public string $search = '';
    public string $filterCategory = '';
    public string $filterStatus = '';

    // Form fields
    public bool $showModal = false;
    public ?int $editingId = null;
    public string $name = '';
    public string $code = '';
    public string $category = 'olahraga';
    public string $description = '';
    public string $schedule = '';
    public ?int $coach_id = null;
    public bool $is_active = true;

    // Member management
    public bool $showMemberModal = false;
    public ?Extracurricular $selectedExtracurricular = null;
    public array $selectedStudents = [];

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', 'unique:extracurriculars,code,' . $this->editingId],
            'category' => ['required', 'in:olahraga,seni,akademik,keagamaan,keterampilan,lainnya'],
            'description' => ['nullable', 'string', 'max:1000'],
            'schedule' => ['nullable', 'string', 'max:255'],
            'coach_id' => ['nullable', 'exists:employees,id'],
            'is_active' => ['boolean'],
        ];
    }

    protected $messages = [
        'name.required' => 'Nama ekstrakurikuler wajib diisi.',
        'code.required' => 'Kode ekstrakurikuler wajib diisi.',
        'code.unique' => 'Kode ekstrakurikuler sudah terdaftar.',
        'category.required' => 'Kategori wajib dipilih.',
    ];

    public const CATEGORIES = [
        'olahraga' => 'Olahraga',
        'seni' => 'Seni & Budaya',
        'akademik' => 'Akademik',
        'keagamaan' => 'Keagamaan',
        'keterampilan' => 'Keterampilan',
        'lainnya' => 'Lainnya',
    ];

    #[Computed]
    public function extracurriculars()
    {
        return Extracurricular::query()
            ->with(['coach', 'members'])
            ->withCount('members')
            ->when($this->search, fn($q) => $q->where(function($query) {
                $query->where('name', 'like', "%{$this->search}%")
                      ->orWhere('code', 'like', "%{$this->search}%");
            }))
            ->when($this->filterCategory, fn($q) => $q->where('category', $this->filterCategory))
            ->when($this->filterStatus !== '', fn($q) => $q->where('is_active', $this->filterStatus === '1'))
            ->orderBy('name')
            ->paginate(15);
    }

    #[Computed]
    public function coaches()
    {
        return Employee::teachers()->active()->orderBy('name')->get();
    }

    #[Computed]
    public function activeStudents()
    {
        return Student::active()->orderBy('name')->get();
    }

    #[Computed]
    public function statistics()
    {
        return [
            'total' => Extracurricular::count(),
            'active' => Extracurricular::where('is_active', true)->count(),
            'members' => ExtracurricularMember::where('status', 'aktif')->count(),
        ];
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(Extracurricular $extracurricular): void
    {
        $this->editingId = $extracurricular->id;
        $this->name = $extracurricular->name;
        $this->code = $extracurricular->code;
        $this->category = $extracurricular->category;
        $this->description = $extracurricular->description ?? '';
        $this->schedule = $extracurricular->schedule ?? '';
        $this->coach_id = $extracurricular->coach_id;
        $this->is_active = (bool) $extracurricular->is_active;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        try {
            $data = [
                'name' => $this->name,
                'code' => $this->code,
                'category' => $this->category,
                'description' => $this->description ?: null,
                'schedule' => $this->schedule ?: null,
                'coach_id' => $this->coach_id,
                'is_active' => (bool) $this->is_active,
            ];

            if ($this->editingId) {
                $extracurricular = Extracurricular::findOrFail($this->editingId);
                $extracurricular->update($data);
                $this->success('Ekstrakurikuler berhasil diperbarui.');
            } else {
                Extracurricular::create($data);
                $this->success('Ekstrakurikuler berhasil ditambahkan.');
            }

            $this->closeModal();
        } catch (\Exception $e) {
            $this->error('Gagal menyimpan ekstrakurikuler: ' . $e->getMessage());
        }
    }

    public function toggleActive(Extracurricular $extracurricular): void
    {
        $extracurricular->update(['is_active' => !$extracurricular->is_active]);
        $status = $extracurricular->is_active ? 'diaktifkan' : 'dinonaktifkan';
        $this->success("Ekstrakurikuler berhasil {$status}.");
    }

    public function delete(Extracurricular $extracurricular): void
    {
        try {
            $name = $extracurricular->name;
            $extracurricular->members()->delete();
            $extracurricular->delete();
            $this->success("Ekstrakurikuler {$name} berhasil dihapus.");
        } catch (\Exception $e) {
            $this->error('Gagal menghapus ekstrakurikuler: ' . $e->getMessage());
        }
    }

    public function openMemberModal(Extracurricular $extracurricular): void
    {
        $this->selectedExtracurricular = $extracurricular;
        $this->selectedStudents = $extracurricular->members->pluck('student_id')->toArray();
        $this->showMemberModal = true;
    }

    public function saveMembers(): void
    {
        try {
            // Get current members
            $currentMembers = $this->selectedExtracurricular->members->pluck('student_id')->toArray();

            // Remove members not in selected
            ExtracurricularMember::where('extracurricular_id', $this->selectedExtracurricular->id)
                ->whereNotIn('student_id', $this->selectedStudents)
                ->delete();

            // Add new members
            $newMembers = array_diff($this->selectedStudents, $currentMembers);
            $activeYear = \App\Models\AcademicYear::where('is_active', true)->first();
            foreach ($newMembers as $studentId) {
                ExtracurricularMember::create([
                    'extracurricular_id' => $this->selectedExtracurricular->id,
                    'student_id' => $studentId,
                    'academic_year_id' => $activeYear?->id ?? 1,
                    'join_date' => now(),
                    'status' => 'aktif',
                ]);
            }

            $this->success('Anggota ekstrakurikuler berhasil diperbarui.');
            $this->showMemberModal = false;
        } catch (\Exception $e) {
            $this->error('Gagal menyimpan anggota: ' . $e->getMessage());
        }
    }

    public function removeMember(int $memberId): void
    {
        try {
            ExtracurricularMember::find($memberId)?->delete();
            $this->success('Anggota berhasil dihapus.');
        } catch (\Exception $e) {
            $this->error('Gagal menghapus anggota: ' . $e->getMessage());
        }
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->code = '';
        $this->category = 'olahraga';
        $this->description = '';
        $this->schedule = '';
        $this->coach_id = null;
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
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </x-slot:icon>
        <x-slot:title>{{ __('Ekstrakurikuler') }}</x-slot:title>
        <x-slot:subtitle>{{ __('Kelola kegiatan ekstrakurikuler dan anggotanya.') }}</x-slot:subtitle>
        <x-slot:actions>
            @can('extracurriculars.create')
            <flux:button wire:click="create" icon="plus" class="rounded-xl! bg-linear-to-r! from-orange-600! to-red-600! hover:from-orange-700! hover:to-red-700! shadow-lg! shadow-orange-500!/25">
                {{ __('Tambah Ekstrakurikuler') }}
            </flux:button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    {{-- Notification --}}
    <x-notification-alert :type="$notificationType" :message="$notificationMessage" />

    <!-- Statistics Cards -->
    <div class="grid grid-cols-3 gap-4 lg:gap-6 mb-8">
        <x-stat-card title="Total Ekskul" :value="$this->statistics['total']" color="orange" class="animate-fade-in-up">
            <x-slot:icon>
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card title="Ekskul Aktif" :value="$this->statistics['active']" color="green" class="animate-fade-in-up delay-100">
            <x-slot:icon>
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card title="Total Anggota" :value="$this->statistics['members']" color="blue" class="animate-fade-in-up delay-200">
            <x-slot:icon>
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <!-- Data Table Card -->
    <x-elegant-card :noPadding="true" class="animate-fade-in-up delay-300">
        <x-slot:header>
            <div class="flex flex-col lg:flex-row gap-4 w-full">
                <div class="relative w-full max-w-sm">
                    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-zinc-400 dark:text-zinc-500">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('Cari ekstrakurikuler...') }}"
                        class="h-11 w-full rounded-xl border border-zinc-300 bg-zinc-50 pl-10 pr-4 text-sm text-zinc-700 placeholder:text-zinc-400 focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-200 dark:border-zinc-700 dark:bg-zinc-800/60 dark:text-zinc-200 dark:placeholder:text-zinc-500 dark:focus:border-orange-400 dark:focus:ring-orange-900/40"
                    />
                </div>

                <div class="flex flex-wrap gap-3">
                    <flux:select wire:model.live="filterCategory" class="rounded-xl! bg-zinc-50! dark:bg-zinc-800/50! min-w-40">
                        <option value="">Semua Kategori</option>
                        @foreach (self::CATEGORIES as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model.live="filterStatus" class="rounded-xl! bg-zinc-50! dark:bg-zinc-800/50! min-w-35">
                        <option value="">Semua Status</option>
                        <option value="1">Aktif</option>
                        <option value="0">Tidak Aktif</option>
                    </flux:select>
                </div>
            </div>
        </x-slot:header>

        <flux:table class="table-elegant">
            <flux:table.columns>
                <flux:table.column class="font-semibold!">{{ __('Ekstrakurikuler') }}</flux:table.column>
                <flux:table.column class="font-semibold!">{{ __('Kategori') }}</flux:table.column>
                <flux:table.column class="font-semibold!">{{ __('Pembina') }}</flux:table.column>
                <flux:table.column class="font-semibold!">{{ __('Jadwal') }}</flux:table.column>
                <flux:table.column class="font-semibold!">{{ __('Anggota') }}</flux:table.column>
                <flux:table.column class="font-semibold!">{{ __('Status') }}</flux:table.column>
                <flux:table.column class="text-right font-semibold!">{{ __('Aksi') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->extracurriculars as $extracurricular)
                    <flux:table.row wire:key="ext-{{ $extracurricular->id }}" class="hover:bg-orange-50/50! dark:hover:bg-orange-900/10! transition-colors">
                        <flux:table.cell>
                            <div class="flex items-center gap-3">
                                <div class="w-11 h-11 rounded-xl bg-linear-to-br from-orange-500 to-red-600 flex items-center justify-center shadow-md">
                                    <span class="text-sm font-bold text-white">{{ substr($extracurricular->code, 0, 2) }}</span>
                                </div>
                                <div>
                                    <p class="font-semibold text-zinc-800 dark:text-white">{{ $extracurricular->name }}</p>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $extracurricular->code }}</p>
                                </div>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge color="orange" class="rounded-lg!">
                                {{ self::CATEGORIES[$extracurricular->category] ?? $extracurricular->category }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if ($extracurricular->coach)
                                <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $extracurricular->coach->name }}</span>
                            @else
                                <span class="text-sm text-zinc-400">-</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ $extracurricular->schedule ?? '-' }}</span>
                        </flux:table.cell>
                        <flux:table.cell>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 text-sm font-medium">
                                {{ $extracurricular->members_count }} siswa
                            </span>
                        </flux:table.cell>
                        <flux:table.cell>
                            <button
                                type="button"
                                wire:click="toggleActive({{ $extracurricular->id }})"
                                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 {{ $extracurricular->is_active ? 'bg-green-500' : 'bg-zinc-300 dark:bg-zinc-600' }}"
                                role="switch"
                                aria-checked="{{ $extracurricular->is_active ? 'true' : 'false' }}"
                            >
                                <span class="{{ $extracurricular->is_active ? 'translate-x-5' : 'translate-x-0' }} pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                            </button>
                        </flux:table.cell>
                        <flux:table.cell class="text-right">
                            <flux:dropdown position="bottom" align="end">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" class="rounded-lg! hover:bg-zinc-100! dark:hover:bg-zinc-800!" />
                                <flux:menu class="rounded-xl! shadow-xl! border-zinc!-200/50 dark:border-zinc!-700/50">
                                    <flux:menu.item wire:click="openMemberModal({{ $extracurricular->id }})" icon="users" class="rounded-lg!">
                                        {{ __('Kelola Anggota') }}
                                    </flux:menu.item>
                                    @canany(['extracurriculars.update', 'extracurriculars.edit'])
                                    <flux:menu.item wire:click="edit({{ $extracurricular->id }})" icon="pencil" class="rounded-lg!">
                                        {{ __('Edit') }}
                                    </flux:menu.item>
                                    @endcanany
                                    <flux:menu.item wire:click="toggleActive({{ $extracurricular->id }})" icon="{{ $extracurricular->is_active ? 'x-circle' : 'check-circle' }}" class="rounded-lg!">
                                        {{ $extracurricular->is_active ? __('Nonaktifkan') : __('Aktifkan') }}
                                    </flux:menu.item>
                                    @can('extracurriculars.delete')
                                    <flux:menu.separator />
                                    <flux:menu.item
                                        wire:click="delete({{ $extracurricular->id }})"
                                        wire:confirm="{{ __('Apakah Anda yakin ingin menghapus ekstrakurikuler ini?') }}"
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
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <p class="text-zinc-500 dark:text-zinc-400">{{ __('Belum ada data ekstrakurikuler.') }}</p>
                                @can('extracurriculars.create')
                                <flux:button wire:click="create" variant="ghost" icon="plus" size="sm" class="rounded-lg!">
                                    {{ __('Tambah Ekstrakurikuler Pertama') }}
                                </flux:button>
                                @endcan
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        @if ($this->extracurriculars->hasPages())
            <x-slot:footer>
                {{ $this->extracurriculars->links() }}
            </x-slot:footer>
        @endif
    </x-elegant-card>

    <!-- Modal Form -->
    <flux:modal wire:model="showModal" class="max-w-lg">
        <form wire:submit="save">
            <flux:modal.header class="border-b border-zinc-100 dark:border-zinc-800">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-linear-to-br from-orange-500 to-red-600 text-white">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <flux:heading size="lg">
                        {{ $editingId ? __('Edit Ekstrakurikuler') : __('Tambah Ekstrakurikuler') }}
                    </flux:heading>
                </div>
            </flux:modal.header>

            <flux:modal.body class="space-y-4 py-6">
                <div class="grid grid-cols-3 gap-4">
                    <flux:input wire:model="code" label="{{ __('Kode') }}" placeholder="PMR" required class="rounded-xl!" />
                    <div class="col-span-2">
                        <flux:input wire:model="name" label="{{ __('Nama') }}" placeholder="Palang Merah Remaja" required class="rounded-xl!" />
                    </div>
                </div>

                <flux:select wire:model="category" label="{{ __('Kategori') }}" required class="rounded-xl!">
                    @foreach (self::CATEGORIES as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="coach_id" label="{{ __('Pembina') }}" class="rounded-xl!">
                    <option value="">-- Pilih Pembina --</option>
                    @foreach ($this->coaches as $coach)
                        <option value="{{ $coach->id }}">{{ $coach->name }}</option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="schedule" label="{{ __('Jadwal') }}" placeholder="Senin & Kamis, 15:00-17:00" class="rounded-xl!" />

                <flux:textarea wire:model="description" label="{{ __('Deskripsi') }}" rows="3" class="rounded-xl!" />

                <div class="p-4 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium text-zinc-700 dark:text-zinc-300">{{ __('Ekstrakurikuler Aktif') }}</p>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Ekstrakurikuler aktif dapat menerima pendaftaran.') }}</p>
                        </div>
                        <button
                            type="button"
                            wire:click="toggleActiveForm"
                            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 {{ $is_active ? 'bg-green-500' : 'bg-zinc-300 dark:bg-zinc-600' }}"
                            role="switch"
                        >
                            <span class="{{ $is_active ? 'translate-x-5' : 'translate-x-0' }} pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                        </button>
                    </div>
                </div>
            </flux:modal.body>

            <flux:modal.footer class="border-t border-zinc-100 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-800/50">
                <flux:button type="button" wire:click="closeModal" variant="ghost" class="rounded-xl!">{{ __('Batal') }}</flux:button>
                <flux:button type="submit" class="rounded-xl! bg-linear-to-r! from-orange-600! to-red-600!">
                    <svg class="size-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    {{ $editingId ? __('Perbarui') : __('Simpan') }}
                </flux:button>
            </flux:modal.footer>
        </form>
    </flux:modal>

    <!-- Member Modal -->
    <flux:modal wire:model="showMemberModal" class="max-w-2xl">
        <flux:modal.header class="border-b border-zinc-100 dark:border-zinc-800">
            <flux:heading size="lg">{{ __('Kelola Anggota') }} - {{ $selectedExtracurricular?->name }}</flux:heading>
        </flux:modal.header>

        <flux:modal.body class="py-6">
            <div class="max-h-80 overflow-y-auto space-y-2 p-3 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl">
                @foreach ($this->activeStudents as $student)
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-white dark:hover:bg-zinc-700 cursor-pointer transition-colors">
                        <input
                            type="checkbox"
                            wire:model="selectedStudents"
                            value="{{ $student->id }}"
                            class="w-4 h-4 rounded border-zinc-300 text-orange-600 focus:ring-orange-500"
                        />
                        <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $student->name }}</span>
                        <span class="text-xs text-zinc-400">{{ $student->classroom?->name }}</span>
                    </label>
                @endforeach
            </div>
        </flux:modal.body>

        <flux:modal.footer class="border-t border-zinc-100 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-800/50">
            <flux:button type="button" wire:click="$set('showMemberModal', false)" variant="ghost" class="rounded-xl!">{{ __('Batal') }}</flux:button>
            <flux:button wire:click="saveMembers" class="rounded-xl! bg-linear-to-r! from-orange-600! to-red-600!">
                {{ __('Simpan Anggota') }}
            </flux:button>
        </flux:modal.footer>
    </flux:modal>
</div>

@script
<script>
    $wire.on('scroll-to-top', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
</script>
@endscript
