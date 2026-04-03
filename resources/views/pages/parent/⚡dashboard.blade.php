<?php

use App\Models\Student;
use App\Models\Guardian;
use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\Letter;
use App\Models\StudentAttendance;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Portal Orang Tua')] class extends Component {
    public function mount()
    {
        // Ensure user is a parent
        if (!auth()->user()->isParent()) {
            return redirect()->route('dashboard');
        }
    }

    #[Computed]
    public function guardian(): ?Guardian
    {
        return auth()->user()->guardian;
    }

    #[Computed]
    public function student(): ?Student
    {
        return $this->guardian?->student;
    }

    #[Computed]
    public function activeAcademicYear(): ?AcademicYear
    {
        return AcademicYear::where('is_active', true)->first();
    }

    #[Computed]
    public function attendanceSummary(): array
    {
        if (!$this->student) {
            return ['hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alpha' => 0, 'total' => 0, 'percentage' => 0];
        }

        $attendance = StudentAttendance::where('student_id', $this->student->id)
            ->when($this->activeAcademicYear, function($q) {
                $academicYear = $this->activeAcademicYear;
                return $q->whereBetween('date', [$academicYear->start_date, $academicYear->end_date]);
            })
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $hadir = $attendance['hadir'] ?? 0;
        $sakit = $attendance['sakit'] ?? 0;
        $izin = $attendance['izin'] ?? 0;
        $alpha = $attendance['alpha'] ?? 0;
        $total = $hadir + $sakit + $izin + $alpha;
        $percentage = $total > 0 ? round(($hadir / $total) * 100, 1) : 0;

        return [
            'hadir' => $hadir,
            'sakit' => $sakit,
            'izin' => $izin,
            'alpha' => $alpha,
            'total' => $total,
            'percentage' => $percentage,
        ];
    }

    #[Computed]
    public function letters()
    {
        if (!$this->student) {
            return collect();
        }

        return Letter::where('student_id', $this->student->id)
            ->whereIn('status', ['sent', 'approved'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
    }

    #[Computed]
    public function announcements()
    {
        return Announcement::query()
            ->active()
            ->published()
            ->visibleTo(auth()->user())
            ->orderBy('is_pinned', 'desc')
            ->orderBy('published_at', 'desc')
            ->take(5)
            ->get();
    }
}; ?>

<div class="space-y-6">
    <!-- Welcome Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Portal Orang Tua/Wali') }}</flux:heading>
            <flux:text class="mt-2">
                {{ __('Selamat datang, :name', ['name' => auth()->user()->name]) }}
            </flux:text>
        </div>
        <flux:text class="text-sm text-zinc-500">
            {{ now()->translatedFormat('l, d F Y') }}
        </flux:text>
    </div>

    @if (!$this->student)
        <!-- No Student Linked -->
        <flux:card class="bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800">
            <flux:card.body>
                <div class="flex items-start gap-4">
                    <flux:icon name="exclamation-triangle" class="size-8 text-yellow-500" />
                    <div>
                        <flux:heading size="lg">{{ __('Data Anak Belum Terhubung') }}</flux:heading>
                        <flux:text class="mt-2 text-zinc-600 dark:text-zinc-400">
                            {{ __('Akun Anda belum terhubung dengan data siswa. Silakan hubungi pihak sekolah untuk menghubungkan akun Anda dengan data anak.') }}
                        </flux:text>
                    </div>
                </div>
            </flux:card.body>
        </flux:card>
    @else
        <!-- Student Info Card -->
        <flux:card class="bg-linear-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border-blue-200 dark:border-blue-800">
            <flux:card.body>
                <div class="flex flex-col md:flex-row md:items-center gap-6">
                    <div class="shrink-0">
                        <flux:avatar
                            :initials="Str::of($this->student->name)->explode(' ')->take(2)->map(fn($w) => Str::substr($w, 0, 1))->implode('')"
                            size="xl"
                            class="ring-4 ring-white dark:ring-zinc-800"
                        />
                    </div>
                    <div class="flex-1 grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <flux:text class="text-sm text-blue-600 dark:text-blue-400">{{ __('Nama Siswa') }}</flux:text>
                            <flux:heading size="lg">{{ $this->student->name }}</flux:heading>
                        </div>
                        <div>
                            <flux:text class="text-sm text-blue-600 dark:text-blue-400">{{ __('NIS / NISN') }}</flux:text>
                            <flux:text class="font-medium">{{ $this->student->nis }} / {{ $this->student->nisn ?? '-' }}</flux:text>
                        </div>
                        <div>
                            <flux:text class="text-sm text-blue-600 dark:text-blue-400">{{ __('Kelas') }}</flux:text>
                            <flux:badge color="blue" size="lg">
                                {{ $this->student->classroom?->name ?? '-' }}
                            </flux:badge>
                        </div>
                        <div>
                            <flux:text class="text-sm text-blue-600 dark:text-blue-400">{{ __('Jurusan') }}</flux:text>
                            <flux:text class="font-medium">{{ $this->student->department?->name ?? '-' }}</flux:text>
                        </div>
                        <div>
                            <flux:text class="text-sm text-blue-600 dark:text-blue-400">{{ __('Status') }}</flux:text>
                            <flux:badge color="{{ $this->student->status === 'aktif' ? 'green' : 'zinc' }}">
                                {{ \App\Models\Student::STATUSES[$this->student->status] ?? $this->student->status }}
                            </flux:badge>
                        </div>
                        <div>
                            <flux:text class="text-sm text-blue-600 dark:text-blue-400">{{ __('Tahun Ajaran') }}</flux:text>
                            <flux:text class="font-medium">{{ $this->activeAcademicYear?->name ?? '-' }}</flux:text>
                        </div>
                    </div>
                </div>
            </flux:card.body>
        </flux:card>

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Attendance Summary -->
            <flux:card>
                <flux:card.body>
                    <div class="flex items-center justify-between">
                        <div>
                            <flux:text class="text-sm text-zinc-500">{{ __('Kehadiran') }}</flux:text>
                            <flux:heading size="2xl" class="mt-1">{{ $this->attendanceSummary['percentage'] }}%</flux:heading>
                            <flux:text size="sm" class="text-zinc-500 mt-1">
                                {{ $this->attendanceSummary['hadir'] }} dari {{ $this->attendanceSummary['total'] }} hari
                            </flux:text>
                        </div>
                        <div class="p-3 rounded-full bg-green-100 dark:bg-green-900/20">
                            <flux:icon name="check-badge" class="size-6 text-green-600 dark:text-green-400" />
                        </div>
                    </div>
                    <div class="mt-4 grid grid-cols-4 gap-2 text-center text-xs">
                        <div class="p-2 rounded bg-green-100 dark:bg-green-900/20">
                            <div class="font-bold text-green-700 dark:text-green-400">{{ $this->attendanceSummary['hadir'] }}</div>
                            <div class="text-green-600 dark:text-green-500">Hadir</div>
                        </div>
                        <div class="p-2 rounded bg-yellow-100 dark:bg-yellow-900/20">
                            <div class="font-bold text-yellow-700 dark:text-yellow-400">{{ $this->attendanceSummary['sakit'] }}</div>
                            <div class="text-yellow-600 dark:text-yellow-500">Sakit</div>
                        </div>
                        <div class="p-2 rounded bg-blue-100 dark:bg-blue-900/20">
                            <div class="font-bold text-blue-700 dark:text-blue-400">{{ $this->attendanceSummary['izin'] }}</div>
                            <div class="text-blue-600 dark:text-blue-500">Izin</div>
                        </div>
                        <div class="p-2 rounded bg-red-100 dark:bg-red-900/20">
                            <div class="font-bold text-red-700 dark:text-red-400">{{ $this->attendanceSummary['alpha'] }}</div>
                            <div class="text-red-600 dark:text-red-500">Alpha</div>
                        </div>
                    </div>
                </flux:card.body>
            </flux:card>

            <!-- Letters Count -->
            <flux:card>
                <flux:card.body>
                    <div class="flex items-center justify-between">
                        <div>
                            <flux:text class="text-sm text-zinc-500">{{ __('Surat dari Sekolah') }}</flux:text>
                            <flux:heading size="2xl" class="mt-1">{{ $this->letters->count() }}</flux:heading>
                            <flux:text size="sm" class="text-zinc-500 mt-1">
                                {{ __('surat diterima') }}
                            </flux:text>
                        </div>
                        <div class="p-3 rounded-full bg-purple-100 dark:bg-purple-900/20">
                            <flux:icon name="envelope" class="size-6 text-purple-600 dark:text-purple-400" />
                        </div>
                    </div>
                </flux:card.body>
            </flux:card>
        </div>

        <!-- Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Letters from School -->
            <flux:card>
                <flux:card.header class="flex items-center justify-between">
                    <flux:heading size="lg">{{ __('Surat dari Sekolah') }}</flux:heading>
                    <flux:button variant="ghost" size="sm" href="{{ route('parent.letters') }}" wire:navigate>
                        {{ __('Lihat Semua') }}
                    </flux:button>
                </flux:card.header>
                <flux:card.body class="p-0">
                    @if ($this->letters->isNotEmpty())
                        <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach ($this->letters as $letter)
                                <div class="p-4 hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex-1">
                                            <flux:badge size="sm" color="{{ \App\Models\Letter::TYPE_COLORS[$letter->letter_type] ?? 'zinc' }}" class="mb-2">
                                                {{ \App\Models\Letter::TYPES[$letter->letter_type] ?? $letter->letter_type }}
                                            </flux:badge>
                                            <flux:text class="font-medium">{{ $letter->subject }}</flux:text>
                                            <flux:text size="sm" class="text-zinc-500 mt-1">
                                                {{ $letter->issued_at?->format('d M Y') ?? $letter->created_at->format('d M Y') }}
                                            </flux:text>
                                        </div>
                                        <flux:icon name="chevron-right" class="size-5 text-zinc-400" />
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="p-8 text-center">
                            <flux:icon name="envelope" class="size-12 text-zinc-300 mx-auto mb-3" />
                            <flux:text class="text-zinc-500">{{ __('Tidak ada surat.') }}</flux:text>
                        </div>
                    @endif
                </flux:card.body>
            </flux:card>
        </div>

        <!-- Announcements -->
        @if ($this->announcements->isNotEmpty())
            <flux:card>
                <flux:card.header>
                    <flux:heading size="lg">{{ __('Pengumuman Sekolah') }}</flux:heading>
                </flux:card.header>
                <flux:card.body class="space-y-4">
                    @foreach ($this->announcements as $announcement)
                        <div class="p-4 rounded-lg border border-zinc-200 dark:border-zinc-700 {{ $announcement->is_pinned ? 'bg-amber-50 dark:bg-amber-900/10 border-amber-200 dark:border-amber-800' : '' }}">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        @if ($announcement->is_pinned)
                                            <flux:icon name="pin" class="size-4 text-amber-500" />
                                        @endif
                                        <flux:badge size="sm" color="{{ \App\Models\Announcement::TYPE_COLORS[$announcement->type] ?? 'zinc' }}">
                                            {{ \App\Models\Announcement::TYPES[$announcement->type] ?? $announcement->type }}
                                        </flux:badge>
                                    </div>
                                    <flux:heading size="sm">{{ $announcement->title }}</flux:heading>
                                    <flux:text class="text-zinc-600 dark:text-zinc-400 mt-1">
                                        {{ $announcement->excerpt }}
                                    </flux:text>
                                </div>
                                <flux:text size="sm" class="text-zinc-500 whitespace-nowrap">
                                    {{ $announcement->published_at?->diffForHumans() ?? $announcement->created_at->diffForHumans() }}
                                </flux:text>
                            </div>
                        </div>
                    @endforeach
                </flux:card.body>
            </flux:card>
        @endif
    @endif

    <!-- User Info Card -->
    <flux:card>
        <flux:card.header>
            <flux:heading size="lg">{{ __('Informasi Akun') }}</flux:heading>
        </flux:card.header>
        <flux:card.body>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <flux:text class="text-sm text-zinc-500">{{ __('Nama') }}</flux:text>
                    <flux:text class="font-medium">{{ auth()->user()->name }}</flux:text>
                </div>
                <div>
                    <flux:text class="text-sm text-zinc-500">{{ __('Email') }}</flux:text>
                    <flux:text class="font-medium">{{ auth()->user()->email }}</flux:text>
                </div>
                <div>
                    <flux:text class="text-sm text-zinc-500">{{ __('Hubungan') }}</flux:text>
                    <flux:text class="font-medium">
                        {{ $this->guardian ? (\App\Models\Guardian::RELATIONSHIPS[$this->guardian->relationship] ?? $this->guardian->relationship) : '-' }}
                    </flux:text>
                </div>
                <div>
                    <flux:text class="text-sm text-zinc-500">{{ __('Role') }}</flux:text>
                    <flux:badge color="green">
                        {{ auth()->user()->role?->display_name ?? '-' }}
                    </flux:badge>
                </div>
            </div>
        </flux:card.body>
    </flux:card>
</div>
