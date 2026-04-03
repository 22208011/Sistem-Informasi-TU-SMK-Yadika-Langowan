<?php

use App\Models\Student;
use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\LetterRequest;
use App\Models\StudentAttendance;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Portal Siswa')] class extends Component {
    public function mount()
    {
        if (!auth()->user()->isStudent()) {
            return redirect()->route('dashboard');
        }
    }

    #[Computed]
    public function student(): ?Student
    {
        return auth()->user()->student;
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

        return compact('hadir', 'sakit', 'izin', 'alpha', 'total', 'percentage');
    }

    #[Computed]
    public function letterRequests()
    {
        if (!$this->student) {
            return collect();
        }

        return LetterRequest::where('student_id', $this->student->id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
    }

    #[Computed]
    public function letterRequestStats(): array
    {
        if (!$this->student) {
            return ['total' => 0, 'pending' => 0, 'completed' => 0, 'rejected' => 0];
        }

        $requests = LetterRequest::where('student_id', $this->student->id)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'total' => array_sum($requests),
            'pending' => ($requests['pending'] ?? 0) + ($requests['processing'] ?? 0),
            'completed' => $requests['completed'] ?? 0,
            'rejected' => $requests['rejected'] ?? 0,
        ];
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
            <flux:heading size="xl">{{ __('Portal Siswa') }}</flux:heading>
            <flux:text class="mt-2">
                {{ __('Selamat datang, :name', ['name' => auth()->user()->name]) }}
            </flux:text>
        </div>
        <flux:text class="text-sm text-zinc-500">
            {{ now()->translatedFormat('l, d F Y') }}
        </flux:text>
    </div>

    @if (!$this->student)
        <!-- No Student Data Linked -->
        <flux:card class="bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800">
            <div class="flex items-start gap-4 p-4">
                <flux:icon name="exclamation-triangle" class="size-8 text-yellow-500" />
                <div>
                    <flux:heading size="lg">{{ __('Data Siswa Belum Terhubung') }}</flux:heading>
                    <flux:text class="mt-2 text-zinc-600 dark:text-zinc-400">
                        {{ __('Akun Anda belum terhubung dengan data siswa. Silakan hubungi pihak sekolah (Tata Usaha) untuk menghubungkan akun Anda.') }}
                    </flux:text>
                </div>
            </div>
        </flux:card>
    @else
        <!-- Student Info Card -->
        <flux:card class="bg-linear-to-br from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 border-emerald-200 dark:border-emerald-800">
            <div class="flex flex-col md:flex-row md:items-center gap-6 p-2">
                <div class="shrink-0">
                    <flux:avatar
                        :initials="Str::of($this->student->name)->explode(' ')->take(2)->map(fn($w) => Str::substr($w, 0, 1))->implode('')"
                        size="xl"
                        class="ring-4 ring-white dark:ring-zinc-800"
                    />
                </div>
                <div class="flex-1 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <flux:text class="text-sm text-emerald-600 dark:text-emerald-400">{{ __('Nama Siswa') }}</flux:text>
                        <flux:heading size="lg">{{ $this->student->name }}</flux:heading>
                    </div>
                    <div>
                        <flux:text class="text-sm text-emerald-600 dark:text-emerald-400">{{ __('NIS / NISN') }}</flux:text>
                        <flux:text class="font-medium">{{ $this->student->nis }} / {{ $this->student->nisn ?? '-' }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-sm text-emerald-600 dark:text-emerald-400">{{ __('Kelas') }}</flux:text>
                        <flux:badge color="green" size="lg">
                            {{ $this->student->classroom?->name ?? '-' }}
                        </flux:badge>
                    </div>
                    <div>
                        <flux:text class="text-sm text-emerald-600 dark:text-emerald-400">{{ __('Jurusan') }}</flux:text>
                        <flux:text class="font-medium">{{ $this->student->department?->name ?? '-' }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-sm text-emerald-600 dark:text-emerald-400">{{ __('Status') }}</flux:text>
                        <flux:badge color="{{ $this->student->status === 'aktif' ? 'green' : 'zinc' }}">
                            {{ \App\Models\Student::STATUSES[$this->student->status] ?? $this->student->status }}
                        </flux:badge>
                    </div>
                    <div>
                        <flux:text class="text-sm text-emerald-600 dark:text-emerald-400">{{ __('Tahun Ajaran') }}</flux:text>
                        <flux:text class="font-medium">{{ $this->activeAcademicYear?->name ?? '-' }}</flux:text>
                    </div>
                </div>
            </div>
        </flux:card>

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Attendance -->
            <flux:card>
                <div class="flex items-center justify-between p-1">
                    <div>
                        <flux:text class="text-sm text-zinc-500">{{ __('Kehadiran') }}</flux:text>
                        <flux:heading size="2xl" class="mt-1">{{ $this->attendanceSummary['percentage'] }}%</flux:heading>
                        <flux:text size="sm" class="text-zinc-500 mt-1">
                            {{ $this->attendanceSummary['hadir'] }}/{{ $this->attendanceSummary['total'] }} hari
                        </flux:text>
                    </div>
                    <div class="p-3 rounded-full bg-green-100 dark:bg-green-900/20">
                        <flux:icon name="check-badge" class="size-6 text-green-600 dark:text-green-400" />
                    </div>
                </div>
            </flux:card>

            <!-- Total Permohonan -->
            <flux:card>
                <div class="flex items-center justify-between p-1">
                    <div>
                        <flux:text class="text-sm text-zinc-500">{{ __('Total Permohonan') }}</flux:text>
                        <flux:heading size="2xl" class="mt-1">{{ $this->letterRequestStats['total'] }}</flux:heading>
                        <flux:text size="sm" class="text-zinc-500 mt-1">{{ __('surat diajukan') }}</flux:text>
                    </div>
                    <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900/20">
                        <flux:icon name="document-text" class="size-6 text-blue-600 dark:text-blue-400" />
                    </div>
                </div>
            </flux:card>

            <!-- Diproses -->
            <flux:card>
                <div class="flex items-center justify-between p-1">
                    <div>
                        <flux:text class="text-sm text-zinc-500">{{ __('Sedang Diproses') }}</flux:text>
                        <flux:heading size="2xl" class="mt-1">{{ $this->letterRequestStats['pending'] }}</flux:heading>
                        <flux:text size="sm" class="text-zinc-500 mt-1">{{ __('menunggu') }}</flux:text>
                    </div>
                    <div class="p-3 rounded-full bg-yellow-100 dark:bg-yellow-900/20">
                        <flux:icon name="clock" class="size-6 text-yellow-600 dark:text-yellow-400" />
                    </div>
                </div>
            </flux:card>

            <!-- Selesai -->
            <flux:card>
                <div class="flex items-center justify-between p-1">
                    <div>
                        <flux:text class="text-sm text-zinc-500">{{ __('Selesai') }}</flux:text>
                        <flux:heading size="2xl" class="mt-1">{{ $this->letterRequestStats['completed'] }}</flux:heading>
                        <flux:text size="sm" class="text-zinc-500 mt-1">{{ __('surat selesai') }}</flux:text>
                    </div>
                    <div class="p-3 rounded-full bg-emerald-100 dark:bg-emerald-900/20">
                        <flux:icon name="check-circle" class="size-6 text-emerald-600 dark:text-emerald-400" />
                    </div>
                </div>
            </flux:card>
        </div>

        <!-- Quick Actions -->
        <flux:card>
            <flux:card.header>
                <flux:heading size="lg">{{ __('Ajukan Permohonan Surat') }}</flux:heading>
            </flux:card.header>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 p-4">
                @foreach (\App\Models\LetterRequest::TYPES as $key => $label)
                    <a href="{{ route('student-portal.letter-requests.create', ['type' => $key]) }}" wire:navigate
                       class="flex flex-col items-center gap-3 p-4 rounded-lg border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors group">
                        <div class="p-3 rounded-full bg-{{ \App\Models\LetterRequest::TYPE_COLORS[$key] }}-100 dark:bg-{{ \App\Models\LetterRequest::TYPE_COLORS[$key] }}-900/20 group-hover:scale-110 transition-transform">
                            <flux:icon :name="\App\Models\LetterRequest::TYPE_ICONS[$key]" class="size-6 text-{{ \App\Models\LetterRequest::TYPE_COLORS[$key] }}-600 dark:text-{{ \App\Models\LetterRequest::TYPE_COLORS[$key] }}-400" />
                        </div>
                        <flux:text class="text-sm font-medium text-center">{{ $label }}</flux:text>
                    </a>
                @endforeach
            </div>
        </flux:card>

        <!-- Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Letter Requests -->
            <flux:card>
                <flux:card.header class="flex items-center justify-between">
                    <flux:heading size="lg">{{ __('Permohonan Surat Terbaru') }}</flux:heading>
                    <flux:button variant="ghost" size="sm" href="{{ route('student-portal.letter-requests') }}" wire:navigate>
                        {{ __('Lihat Semua') }}
                    </flux:button>
                </flux:card.header>
                @if ($this->letterRequests->isNotEmpty())
                    <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach ($this->letterRequests as $request)
                            <div class="p-4 hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-1">
                                            <flux:badge size="sm" color="{{ \App\Models\LetterRequest::TYPE_COLORS[$request->letter_type] ?? 'zinc' }}">
                                                {{ \App\Models\LetterRequest::TYPES[$request->letter_type] ?? $request->letter_type }}
                                            </flux:badge>
                                            <flux:badge size="sm" color="{{ \App\Models\LetterRequest::STATUS_COLORS[$request->status] ?? 'zinc' }}">
                                                {{ \App\Models\LetterRequest::STATUSES[$request->status] ?? $request->status }}
                                            </flux:badge>
                                        </div>
                                        <flux:text class="font-medium text-sm">{{ $request->request_number }}</flux:text>
                                        @if($request->purpose)
                                            <flux:text size="sm" class="text-zinc-500 mt-1">{{ Str::limit($request->purpose, 50) }}</flux:text>
                                        @endif
                                        <flux:text size="sm" class="text-zinc-400 mt-1">
                                            {{ $request->created_at->translatedFormat('d M Y H:i') }}
                                        </flux:text>
                                    </div>
                                    @if($request->canBeDownloaded())
                                        <flux:badge color="green" size="sm">
                                            <flux:icon name="arrow-down-tray" class="size-3 mr-1" /> Unduh
                                        </flux:badge>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-8 text-center">
                        <flux:icon name="document-text" class="size-12 text-zinc-300 mx-auto mb-3" />
                        <flux:text class="text-zinc-500">{{ __('Belum ada permohonan surat.') }}</flux:text>
                        <flux:button variant="primary" size="sm" class="mt-3" href="{{ route('student-portal.letter-requests.create') }}" wire:navigate>
                            {{ __('Ajukan Sekarang') }}
                        </flux:button>
                    </div>
                @endif
            </flux:card>

            <!-- Announcements -->
            <flux:card>
                <flux:card.header class="flex items-center justify-between">
                    <flux:heading size="lg">{{ __('Pengumuman') }}</flux:heading>
                    <flux:button variant="ghost" size="sm" href="{{ route('student-portal.announcements') }}" wire:navigate>
                        {{ __('Lihat Semua') }}
                    </flux:button>
                </flux:card.header>
                @if ($this->announcements->isNotEmpty())
                    <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach ($this->announcements as $announcement)
                            <div class="p-4 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 {{ $announcement->is_pinned ? 'bg-amber-50/50 dark:bg-amber-900/10' : '' }}">
                                <div class="flex items-start gap-3">
                                    @if ($announcement->is_pinned)
                                        <flux:icon name="pin" class="size-4 text-amber-500 mt-1 shrink-0" />
                                    @endif
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-1">
                                            <flux:badge size="sm" color="{{ \App\Models\Announcement::TYPE_COLORS[$announcement->type] ?? 'zinc' }}">
                                                {{ \App\Models\Announcement::TYPES[$announcement->type] ?? $announcement->type }}
                                            </flux:badge>
                                            @if($announcement->priority === 'high')
                                                <flux:badge size="sm" color="red">Penting</flux:badge>
                                            @endif
                                        </div>
                                        <flux:text class="font-medium">{{ $announcement->title }}</flux:text>
                                        <flux:text size="sm" class="text-zinc-500 mt-1">
                                            {{ Str::limit(strip_tags($announcement->content), 80) }}
                                        </flux:text>
                                        <flux:text size="sm" class="text-zinc-400 mt-1">
                                            {{ $announcement->published_at?->diffForHumans() ?? $announcement->created_at->diffForHumans() }}
                                        </flux:text>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-8 text-center">
                        <flux:icon name="megaphone" class="size-12 text-zinc-300 mx-auto mb-3" />
                        <flux:text class="text-zinc-500">{{ __('Tidak ada pengumuman.') }}</flux:text>
                    </div>
                @endif
            </flux:card>
        </div>
    @endif
</div>
