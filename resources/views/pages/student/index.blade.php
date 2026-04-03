<?php

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Department;
use App\Models\Student;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('Data Siswa')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $filterStatus = '';
    public ?int $filterClassroom = null;
    public ?int $filterDepartment = null;
    public ?int $filterEntryYear = null;

    #[Computed]
    public function students()
    {
        return Student::query()
            ->with(['classroom', 'department'])
            ->when($this->search, fn($q) => $q->where(function ($query) {
                $query->where('name', 'like', "%{$this->search}%")
                    ->orWhere('nis', 'like', "%{$this->search}%")
                    ->orWhere('nisn', 'like', "%{$this->search}%");
            }))
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterClassroom, fn($q) => $q->where('classroom_id', $this->filterClassroom))
            ->when($this->filterDepartment, fn($q) => $q->where('department_id', $this->filterDepartment))
            ->when($this->filterEntryYear, fn($q) => $q->where('entry_year', $this->filterEntryYear))
            ->orderBy('name')
            ->paginate(15);
    }

    #[Computed]
    public function classrooms()
    {
        return Classroom::active()->orderBy('name')->get();
    }

    #[Computed]
    public function departments()
    {
        return Department::active()->orderBy('name')->get();
    }

    #[Computed]
    public function entryYears()
    {
        return Student::distinct()->orderByDesc('entry_year')->pluck('entry_year');
    }

    #[Computed]
    public function statistics()
    {
        return [
            'total' => Student::count(),
            'active' => Student::active()->count(),
            'male' => Student::active()->where('gender', 'L')->count(),
            'female' => Student::active()->where('gender', 'P')->count(),
        ];
    }

    public function delete(Student $student): void
    {
        $student->delete();
        session()->flash('success', 'Data siswa berhasil dihapus.');
    }
}; ?>

<div>
    <!-- Page Header with Animation -->
    <x-page-header>
        <x-slot:icon>
            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
        </x-slot:icon>
        <x-slot:title>{{ __('Data Siswa') }}</x-slot:title>
        <x-slot:subtitle>{{ __('Kelola data siswa, informasi akademik, dan status keaktifan.') }}</x-slot:subtitle>
        <x-slot:actions>
            <flux:button :href="route('students.mutations.index')" variant="ghost" icon="arrows-right-left" wire:navigate class="rounded-xl! hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors">
                {{ __('Mutasi') }}
            </flux:button>
            <flux:button :href="route('students.attendance.index')" variant="ghost" icon="clipboard-document-check" wire:navigate class="rounded-xl! hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors">
                {{ __('Kehadiran') }}
            </flux:button>
            <flux:button :href="route('students.create')" icon="plus" wire:navigate class="rounded-xl! bg-linear-to-r! from-blue-600! to-indigo-600! hover:from-blue-700! hover:to-indigo-700! shadow-lg! shadow-blue-500!/25 hover:shadow-xl! hover:shadow-blue-500!/30 hover:scale-105! transition-all! duration-300!">
                {{ __('Tambah Siswa') }}
            </flux:button>
        </x-slot:actions>
    </x-page-header>

    <!-- Statistics Cards with Elegant Design -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6 mb-8">
        <x-stat-card title="Total Siswa" :value="$this->statistics['total']" color="blue" class="animate-fade-in-up">
            <x-slot:icon>
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </x-slot:icon>
        </x-stat-card>
        
        <x-stat-card title="Siswa Aktif" :value="$this->statistics['active']" color="green" class="animate-fade-in-up delay-100">
            <x-slot:icon>
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </x-slot:icon>
        </x-stat-card>
        
        <x-stat-card title="Laki-laki" :value="$this->statistics['male']" color="indigo" class="animate-fade-in-up delay-200">
            <x-slot:icon>
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
            </x-slot:icon>
        </x-stat-card>
        
        <x-stat-card title="Perempuan" :value="$this->statistics['female']" color="purple" class="animate-fade-in-up delay-300">
            <x-slot:icon>
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <!-- Data Table Card -->
    <x-elegant-card :noPadding="true" class="animate-fade-in-up delay-300">
        <x-slot:header>
            <div class="flex flex-col lg:flex-row gap-4 w-full">
                <div class="relative flex-1 max-w-sm">
                    <flux:input
                        wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('Cari nama, NIS, NISN...') }}"
                        icon="magnifying-glass"
                        class="rounded-xl! bg-zinc-50! dark:bg-zinc-800/50! focus:bg-white! dark:focus:bg-zinc-800!"
                    />
                </div>

                <div class="flex flex-wrap gap-3">
                    <flux:select wire:model.live="filterStatus" class="rounded-xl! bg-zinc-50! dark:bg-zinc-800/50! min-w-35">
                        <option value="">Semua Status</option>
                        @foreach (App\Models\Student::STATUSES as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model.live="filterClassroom" class="rounded-xl! bg-zinc-50! dark:bg-zinc-800/50! min-w-35">
                        <option value="">Semua Kelas</option>
                        @foreach ($this->classrooms as $classroom)
                            <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model.live="filterDepartment" class="rounded-xl! bg-zinc-50! dark:bg-zinc-800/50! min-w-35">
                        <option value="">Semua Jurusan</option>
                        @foreach ($this->departments as $department)
                            <option value="{{ $department->id }}">{{ $department->code }}</option>
                        @endforeach
                    </flux:select>

                    @if ($this->entryYears->isNotEmpty())
                        <flux:select wire:model.live="filterEntryYear" class="rounded-xl! bg-zinc-50! dark:bg-zinc-800/50! min-w-35">
                            <option value="">Semua Angkatan</option>
                            @foreach ($this->entryYears as $year)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @endforeach
                        </flux:select>
                    @endif
                </div>
            </div>
        </x-slot:header>

        <flux:table class="table-elegant">
                <flux:table.columns>
                    <flux:table.column class="font-semibold!">{{ __('Siswa') }}</flux:table.column>
                    <flux:table.column class="font-semibold!">{{ __('NIS/NISN') }}</flux:table.column>
                    <flux:table.column class="font-semibold!">{{ __('Kelas') }}</flux:table.column>
                    <flux:table.column class="font-semibold!">{{ __('Jurusan') }}</flux:table.column>
                    <flux:table.column class="font-semibold!">{{ __('Angkatan') }}</flux:table.column>
                    <flux:table.column class="font-semibold!">{{ __('Status') }}</flux:table.column>
                    <flux:table.column class="text-right font-semibold!">{{ __('Aksi') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($this->students as $student)
                        <flux:table.row wire:key="stu-{{ $student->id }}" class="hover:bg-blue-50/50! dark:hover:bg-blue-900/10! transition-colors">
                            <flux:table.cell>
                                <div class="flex items-center gap-3">
                                    @if ($student->photo)
                                        <img src="{{ $student->photo_url }}" alt="{{ $student->name }}" class="w-11 h-11 rounded-xl object-cover ring-2 ring-white dark:ring-zinc-800 shadow-md" />
                                    @else
                                        <div class="w-11 h-11 rounded-xl bg-linear-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-md">
                                            <span class="text-sm font-bold text-white">{{ substr($student->name, 0, 1) }}</span>
                                        </div>
                                    @endif
                                    <div>
                                        <p class="font-semibold text-zinc-800 dark:text-white">{{ $student->name }}</p>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                            {{ App\Models\Student::GENDERS[$student->gender] ?? $student->gender }}
                                        </p>
                                    </div>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="space-y-0.5">
                                    <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $student->nis }}</p>
                                    @if ($student->nisn)
                                        <p class="text-xs text-zinc-400 dark:text-zinc-500">NISN: {{ $student->nisn }}</p>
                                    @endif
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 text-sm font-medium">
                                    {{ $student->classroom?->name ?? '-' }}
                                </span>
                            </flux:table.cell>
                            <flux:table.cell>
                                <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ $student->department?->code ?? '-' }}</span>
                            </flux:table.cell>
                            <flux:table.cell>
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $student->entry_year }}</span>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge color="{{ App\Models\Student::STATUS_COLORS[$student->status] ?? 'zinc' }}" class="rounded-lg!">
                                    {{ App\Models\Student::STATUSES[$student->status] ?? $student->status }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell class="text-right">
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" class="rounded-lg! hover:bg-zinc-100! dark:hover:bg-zinc-800!" />

                                    <flux:menu class="rounded-xl! shadow-xl! border-zinc!-200/50 dark:border-zinc!-700/50">
                                        <flux:menu.item :href="route('students.show', $student)" icon="eye" wire:navigate class="rounded-lg!">
                                            {{ __('Lihat Detail') }}
                                        </flux:menu.item>
                                        <flux:menu.item :href="route('students.edit', $student)" icon="pencil" wire:navigate class="rounded-lg!">
                                            {{ __('Edit') }}
                                        </flux:menu.item>
                                        <flux:menu.separator />
                                        <flux:menu.item
                                            wire:click="delete({{ $student->id }})"
                                            wire:confirm="{{ __('Apakah Anda yakin ingin menghapus data siswa ini?') }}"
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
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                        </svg>
                                    </div>
                                    <p class="text-zinc-500 dark:text-zinc-400">{{ __('Belum ada data siswa.') }}</p>
                                    <flux:button :href="route('students.create')" variant="ghost" icon="plus" wire:navigate size="sm" class="rounded-lg!">
                                        {{ __('Tambah Siswa Pertama') }}
                                    </flux:button>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>

        @if ($this->students->hasPages())
            <x-slot:footer>
                {{ $this->students->links() }}
            </x-slot:footer>
        @endif
    </x-elegant-card>
</div>
