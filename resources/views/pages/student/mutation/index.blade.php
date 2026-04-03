<?php

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\StudentMutation;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('Mutasi Siswa')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $filterType = '';
    public string $filterStatus = '';
    public ?int $filterAcademicYear = null;

    #[Computed]
    public function mutations()
    {
        return StudentMutation::query()
            ->with(['student', 'previousClassroom', 'newClassroom', 'academicYear', 'approver'])
            ->when($this->search, fn($q) => $q->whereHas('student', function ($query) {
                $query->where('name', 'like', "%{$this->search}%")
                    ->orWhere('nis', 'like', "%{$this->search}%");
            }))
            ->when($this->filterType, fn($q) => $q->where('type', $this->filterType))
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterAcademicYear, fn($q) => $q->where('academic_year_id', $this->filterAcademicYear))
            ->orderByDesc('mutation_date')
            ->orderByDesc('created_at')
            ->paginate(15);
    }

    #[Computed]
    public function academicYears()
    {
        return AcademicYear::orderByDesc('start_date')->get();
    }

    #[Computed]
    public function statistics()
    {
        $currentYear = AcademicYear::where('is_active', true)->first();
        $yearId = $currentYear?->id;

        return [
            'total' => StudentMutation::when($yearId, fn($q) => $q->where('academic_year_id', $yearId))->count(),
            'masuk' => StudentMutation::when($yearId, fn($q) => $q->where('academic_year_id', $yearId))->where('type', 'masuk')->count(),
            'keluar' => StudentMutation::when($yearId, fn($q) => $q->where('academic_year_id', $yearId))->whereIn('type', ['keluar', 'do'])->count(),
            'pending' => StudentMutation::where('status', 'pending')->count(),
        ];
    }

    public function approve(StudentMutation $mutation): void
    {
        $mutation->update([
            'status' => StudentMutation::STATUS_APPROVED,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        // Update student status based on mutation type
        if ($mutation->type === StudentMutation::TYPE_MASUK) {
            $mutation->student->update([
                'status' => 'aktif',
                'classroom_id' => $mutation->new_classroom_id,
            ]);
        } elseif ($mutation->type === StudentMutation::TYPE_KELUAR) {
            $mutation->student->update(['status' => 'pindah']);
        } elseif ($mutation->type === StudentMutation::TYPE_DO) {
            $mutation->student->update(['status' => 'do']);
        } elseif ($mutation->type === StudentMutation::TYPE_LULUS) {
            $mutation->student->update(['status' => 'lulus']);
        } elseif (in_array($mutation->type, [StudentMutation::TYPE_PINDAH_KELAS, StudentMutation::TYPE_NAIK_KELAS])) {
            $mutation->student->update(['classroom_id' => $mutation->new_classroom_id]);
        }

        session()->flash('success', 'Mutasi berhasil disetujui.');
    }

    public function reject(StudentMutation $mutation): void
    {
        $mutation->update([
            'status' => StudentMutation::STATUS_REJECTED,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        session()->flash('success', 'Mutasi ditolak.');
    }

    public function delete(StudentMutation $mutation): void
    {
        $mutation->delete();
        session()->flash('success', 'Data mutasi berhasil dihapus.');
    }
}; ?>

<div>
    <!-- Page Header -->
    <x-page-header>
        <x-slot:icon>
            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
            </svg>
        </x-slot:icon>
        <x-slot:title>{{ __('Mutasi Siswa') }}</x-slot:title>
        <x-slot:subtitle>{{ __('Kelola data mutasi siswa: pendaftaran, pindah masuk/keluar, dan kenaikan kelas.') }}</x-slot:subtitle>
        <x-slot:actions>
            <div class="flex items-center gap-2" x-data>
                <flux:dropdown position="bottom" align="end">
                    <flux:button icon="plus" variant="primary" class="rounded-xl! bg-linear-to-r! from-blue-600! to-indigo-600!">
                        {{ __('Tambah Mutasi') }}
                    </flux:button>
                    <flux:menu class="min-w-55">
                        <a href="{{ route('students.mutations.register') }}" class="flex items-center gap-2 px-3 py-2 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-lg transition-colors">
                            <flux:icon.user-plus class="size-5" />
                            {{ __('Pendaftaran Siswa Baru') }}
                        </a>
                        <a href="{{ route('students.mutations.in') }}" class="flex items-center gap-2 px-3 py-2 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-lg transition-colors">
                            <flux:icon.arrow-right-circle class="size-5" />
                            {{ __('Mutasi Masuk (Pindahan)') }}
                        </a>
                        <a href="{{ route('students.mutations.out') }}" class="flex items-center gap-2 px-3 py-2 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-lg transition-colors">
                            <flux:icon.arrow-left-circle class="size-5" />
                            {{ __('Mutasi Keluar') }}
                        </a>
                        <div class="border-t border-zinc-200 dark:border-zinc-700 my-1"></div>
                        <a href="{{ route('students.mutations.promotion') }}" class="flex items-center gap-2 px-3 py-2 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-lg transition-colors">
                            <flux:icon.academic-cap class="size-5" />
                            {{ __('Kenaikan Kelas') }}
                        </a>
                    </flux:menu>
                </flux:dropdown>
            </div>
        </x-slot:actions>
    </x-page-header>

    <!-- Quick Actions -->
    <div class="flex flex-wrap gap-2 mb-6">
        <a href="{{ route('students.mutations.register') }}" wire:navigate class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors shadow-sm">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
            Pendaftaran Siswa Baru
        </a>
        <a href="{{ route('students.mutations.in') }}" wire:navigate class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors shadow-sm">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" /></svg>
            Mutasi Masuk
        </a>
        <a href="{{ route('students.mutations.out') }}" wire:navigate class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-yellow-600 hover:bg-yellow-700 rounded-lg transition-colors shadow-sm">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
            Mutasi Keluar
        </a>
        <a href="{{ route('students.mutations.promotion') }}" wire:navigate class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-purple-600 hover:bg-purple-700 rounded-lg transition-colors shadow-sm">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 11l3-3m0 0l3 3m-3-3v8m0-13a9 9 0 110 18 9 9 0 010-18z" /></svg>
            Kenaikan Kelas
        </a>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6 mb-8">
        <x-stat-card title="Total Mutasi" :value="$this->statistics['total']" color="blue">
            <x-slot:icon>
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                </svg>
            </x-slot:icon>
        </x-stat-card>
        <x-stat-card title="Siswa Masuk" :value="$this->statistics['masuk']" color="green">
            <x-slot:icon>
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
            </x-slot:icon>
        </x-stat-card>
        <x-stat-card title="Siswa Keluar" :value="$this->statistics['keluar']" color="yellow">
            <x-slot:icon>
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7a4 4 0 11-8 0 4 4 0 018 0zM9 14a6 6 0 00-6 6v1h12v-1a6 6 0 00-6-6zM21 12h-6" />
                </svg>
            </x-slot:icon>
        </x-stat-card>
        <x-stat-card title="Menunggu Persetujuan" :value="$this->statistics['pending']" color="orange">
            <x-slot:icon>
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <!-- Filters -->
    <x-card class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari nama/NIS siswa..." icon="magnifying-glass" />
            <flux:select wire:model.live="filterType">
                <option value="">Semua Tipe</option>
                @foreach(\App\Models\StudentMutation::TYPES as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="filterStatus">
                <option value="">Semua Status</option>
                @foreach(\App\Models\StudentMutation::STATUSES as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="filterAcademicYear">
                <option value="">Semua Tahun Ajaran</option>
                @foreach($this->academicYears as $year)
                    <option value="{{ $year->id }}">{{ $year->name }}</option>
                @endforeach
            </flux:select>
        </div>
    </x-card>

    <!-- Data Table -->
    <x-card>
        <x-slot:header>
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Riwayat Mutasi</h3>
        </x-slot:header>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-700">
                        <th class="text-left py-3 px-4 font-medium text-zinc-600 dark:text-zinc-400">Tanggal</th>
                        <th class="text-left py-3 px-4 font-medium text-zinc-600 dark:text-zinc-400">Siswa</th>
                        <th class="text-left py-3 px-4 font-medium text-zinc-600 dark:text-zinc-400">Tipe</th>
                        <th class="text-left py-3 px-4 font-medium text-zinc-600 dark:text-zinc-400">Keterangan</th>
                        <th class="text-left py-3 px-4 font-medium text-zinc-600 dark:text-zinc-400">Status</th>
                        <th class="text-center py-3 px-4 font-medium text-zinc-600 dark:text-zinc-400">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse($this->mutations as $mutation)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                            <td class="py-3 px-4">
                                <div class="text-zinc-900 dark:text-white">{{ $mutation->mutation_date->format('d M Y') }}</div>
                                <div class="text-xs text-zinc-500">{{ $mutation->academicYear?->name }}</div>
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-medium text-zinc-900 dark:text-white">{{ $mutation->student?->name }}</div>
                                <div class="text-xs text-zinc-500">NIS: {{ $mutation->student?->nis }}</div>
                            </td>
                            <td class="py-3 px-4">
                                <flux:badge size="sm" :color="\App\Models\StudentMutation::TYPE_COLORS[$mutation->type] ?? 'zinc'">
                                    {{ \App\Models\StudentMutation::TYPES[$mutation->type] ?? $mutation->type }}
                                </flux:badge>
                            </td>
                            <td class="py-3 px-4">
                                @if($mutation->type === 'masuk' && $mutation->previous_school)
                                    <div class="text-sm text-zinc-600 dark:text-zinc-400">Dari: {{ $mutation->previous_school }}</div>
                                @elseif($mutation->type === 'keluar' && $mutation->destination_school)
                                    <div class="text-sm text-zinc-600 dark:text-zinc-400">Ke: {{ $mutation->destination_school }}</div>
                                @elseif(in_array($mutation->type, ['pindah_kelas', 'naik_kelas']))
                                    <div class="text-sm text-zinc-600 dark:text-zinc-400">
                                        {{ $mutation->previousClassroom?->name }} → {{ $mutation->newClassroom?->name }}
                                    </div>
                                @else
                                    <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ $mutation->reason ?? '-' }}</div>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                <flux:badge size="sm" :color="\App\Models\StudentMutation::STATUS_COLORS[$mutation->status] ?? 'zinc'">
                                    {{ \App\Models\StudentMutation::STATUSES[$mutation->status] ?? $mutation->status }}
                                </flux:badge>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    @if($mutation->isPending())
                                        <flux:button size="xs" variant="ghost" icon="check" wire:click="approve({{ $mutation->id }})" wire:confirm="Yakin menyetujui mutasi ini?" class="text-green-600 hover:text-green-700" title="Setujui" />
                                        <flux:button size="xs" variant="ghost" icon="x-mark" wire:click="reject({{ $mutation->id }})" wire:confirm="Yakin menolak mutasi ini?" class="text-red-600 hover:text-red-700" title="Tolak" />
                                    @endif
                                    <flux:button size="xs" variant="ghost" icon="trash" wire:click="delete({{ $mutation->id }})" wire:confirm="Yakin ingin menghapus data mutasi ini?" class="text-red-600 hover:text-red-700" title="Hapus" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-zinc-500">
                                <div class="flex flex-col items-center gap-2">
                                    <svg class="size-12 text-zinc-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                    </svg>
                                    <span>Belum ada data mutasi</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($this->mutations->hasPages())
            <div class="border-t border-zinc-200 dark:border-zinc-700 px-4 py-3">
                {{ $this->mutations->links() }}
            </div>
        @endif
    </x-card>
</div>
