<div class="space-y-8">
    <!-- Welcome Section -->
    <div class="animate-fade-in-up">
        <div class="relative overflow-hidden rounded-2xl bg-linear-to-r from-indigo-600 via-indigo-500 to-purple-600 p-8 text-white">
            <div class="absolute inset-0 opacity-10">
                <svg class="h-full w-full" viewBox="0 0 800 400" preserveAspectRatio="none">
                    <path d="M0 300 Q200 200 400 280 Q600 360 800 250 L800 400 L0 400 Z" fill="white" opacity="0.15"/>
                    <path d="M0 350 Q200 280 400 320 Q600 360 800 300 L800 400 L0 400 Z" fill="white" opacity="0.1"/>
                </svg>
            </div>
            
            <div class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
                <div class="space-y-3">
                    <div class="flex items-center gap-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-white/15 backdrop-blur-sm">
                            <flux:icon name="hand-raised" class="size-7 text-white" />
                        </div>
                        <div>
                            <flux:heading size="2xl" class="text-white font-bold">
                                {{ __('Selamat Datang, :name!', ['name' => auth()->user()->name]) }}
                            </flux:heading>
                            <flux:text class="text-white/70 text-base mt-1">
                                @if ($this->activeAcademicYear)
                                    {{ __('Tahun Ajaran :year - Semester :semester', [
                                        'year' => $this->activeAcademicYear->name,
                                        'semester' => ucfirst($this->activeAcademicYear->semester)
                                    ]) }}
                                @else
                                    {{ __('Sistem Informasi Tata Usaha - SMK YADIKA LANGOWAN') }}
                                @endif
                            </flux:text>
                        </div>
                    </div>
                </div>
                <div class="flex flex-col items-end gap-2">
                    <div class="inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-2 backdrop-blur-sm text-sm">
                        <flux:icon name="shield-check" class="size-4" />
                        <span class="font-medium">{{ auth()->user()->role?->display_name ?? '-' }}</span>
                    </div>
                    <div class="flex items-center gap-2 text-white/60 text-sm">
                        <flux:icon name="calendar" class="size-4" />
                        <span>{{ now()->translatedFormat('l, d F Y') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Today's Attendance Summary (Admin/Kepala Sekolah) -->
    @if ($this->isAdmin || $this->isKepalaSekolah)
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 animate-fade-in-up delay-100">
            <!-- Student Attendance Card -->
            <div class="stat-card blue p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div class="space-y-3">
                        <div class="flex items-center gap-2">
                            <div class="h-1.5 w-1.5 rounded-full bg-blue-500 animate-pulse-soft"></div>
                            <flux:text class="text-xs text-blue-600 dark:text-blue-400 font-semibold uppercase tracking-wider">
                                {{ __('Kehadiran Siswa Hari Ini') }}
                            </flux:text>
                        </div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-4xl font-bold text-zinc-900 dark:text-zinc-100 animate-count-up">
                                {{ $this->todayAttendanceSummary['students']['present'] }}
                            </span>
                            <span class="text-lg text-zinc-400 dark:text-zinc-500">
                                / {{ $this->todayAttendanceSummary['students']['total'] }}
                            </span>
                        </div>
                        <div class="flex items-center gap-4">
                            <span class="inline-flex items-center gap-1 text-sm text-emerald-600 dark:text-emerald-400">
                                <flux:icon name="check-circle" class="size-4" />
                                {{ $this->todayAttendanceSummary['students']['present'] }} hadir
                            </span>
                            <span class="inline-flex items-center gap-1 text-sm text-red-500">
                                <flux:icon name="x-circle" class="size-4" />
                                {{ $this->todayAttendanceSummary['students']['absent'] }} tidak hadir
                            </span>
                        </div>
                    </div>
                    <div class="icon-container blue size-14">
                        <flux:icon name="academic-cap" class="size-7" />
                    </div>
                </div>
                <div class="mt-4">
                    <div class="progress-elegant">
                        <div class="progress-bar" style="width: {{ $this->todayAttendanceSummary['students']['total'] > 0 ? ($this->todayAttendanceSummary['students']['present'] / $this->todayAttendanceSummary['students']['total'] * 100) : 0 }}%"></div>
                    </div>
                </div>
            </div>

            <!-- Employee Attendance Card -->
            <div class="stat-card green p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div class="space-y-3">
                        <div class="flex items-center gap-2">
                            <div class="h-1.5 w-1.5 rounded-full bg-green-500 animate-pulse-soft"></div>
                            <flux:text class="text-xs text-green-600 dark:text-green-400 font-semibold uppercase tracking-wider">
                                {{ __('Kehadiran Pegawai Hari Ini') }}
                            </flux:text>
                        </div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-4xl font-bold text-zinc-900 dark:text-zinc-100 animate-count-up">
                                {{ $this->todayAttendanceSummary['employees']['present'] }}
                            </span>
                            <span class="text-lg text-zinc-400 dark:text-zinc-500">
                                / {{ $this->todayAttendanceSummary['employees']['total'] }}
                            </span>
                        </div>
                        <div class="flex items-center gap-4">
                            <span class="inline-flex items-center gap-1 text-sm text-emerald-600 dark:text-emerald-400">
                                <flux:icon name="check-circle" class="size-4" />
                                {{ $this->todayAttendanceSummary['employees']['present'] }} hadir
                            </span>
                            <span class="inline-flex items-center gap-1 text-sm text-red-500">
                                <flux:icon name="x-circle" class="size-4" />
                                {{ $this->todayAttendanceSummary['employees']['absent'] }} tidak hadir
                            </span>
                        </div>
                    </div>
                    <div class="icon-container green size-14">
                        <flux:icon name="users" class="size-7" />
                    </div>
                </div>
                <div class="mt-4">
                    <div class="progress-elegant">
                        <div class="progress-bar bg-linear-to-r from-green-500 to-emerald-400" style="width: {{ $this->todayAttendanceSummary['employees']['total'] > 0 ? ($this->todayAttendanceSummary['employees']['present'] / $this->todayAttendanceSummary['employees']['total'] * 100) : 0 }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Stats Cards -->
    @if (count($this->stats) > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach ($this->stats as $key => $stat)
                @php
                    $index = $loop->index;
                    $colors = ['blue', 'green', 'purple', 'amber'];
                    $color = $stat['color'] ?? $colors[$index % 4];
                    $labels = [
                        'students' => 'Siswa',
                        'employees' => 'Pegawai', 
                        'classrooms' => 'Kelas',
                        'users' => 'User',
                        'my_students' => 'Siswa Saya',
                        'my_classrooms' => 'Kelas Saya'
                    ];
                @endphp
                @if ($stat['route'] || auth()->user()->isAdmin() || $this->isGuru)
                    <div class="stat-card {{ $color }} p-6 shadow-lg card-hover animate-fade-in-up delay-{{ ($index + 2) * 100 }}">
                        <div class="flex items-start justify-between">
                            <div class="space-y-2">
                                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400 font-medium uppercase tracking-wide">
                                    {{ __($labels[$key] ?? $key) }}
                                </flux:text>
                                <div class="flex items-baseline gap-2">
                                    <span class="text-4xl font-bold text-zinc-800 dark:text-white">
                                        {{ number_format($stat['total']) }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center gap-1 text-sm text-green-600 dark:text-green-400">
                                        <flux:icon name="arrow-trending-up" class="size-4" />
                                        {{ $stat['active'] }} {{ __('aktif') }}
                                    </span>
                                </div>
                            </div>
                            <div class="icon-container {{ $color }} size-14">
                                <flux:icon :name="$stat['icon']" class="size-7" />
                            </div>
                        </div>
                        @if ($stat['route'])
                            <a href="{{ $stat['route'] }}" wire:navigate class="mt-4 inline-flex items-center gap-2 text-sm font-medium text-{{ $color }}-600 hover:text-{{ $color }}-700 dark:text-{{ $color }}-400 dark:hover:text-{{ $color }}-300 transition-colors">
                                {{ __('Lihat semua') }}
                                <flux:icon name="arrow-right" class="size-4 transition-transform group-hover:translate-x-1" />
                            </a>
                        @endif
                    </div>
                @endif
            @endforeach
        </div>
    @endif

    <!-- Pending Letters for Kepala Sekolah -->
    @if ($this->isKepalaSekolah && $this->pendingLetters->isNotEmpty())
        <div class="rounded-2xl bg-white dark:bg-zinc-900/80 border border-amber-200/60 dark:border-amber-800/40 overflow-hidden animate-fade-in-up delay-200">
            <div class="flex items-center justify-between px-5 py-4 border-b border-amber-100 dark:border-amber-900/30">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center">
                        <flux:icon name="document-text" class="size-4 text-amber-600 dark:text-amber-400" />
                    </div>
                    <h3 class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">{{ __('Surat Menunggu Persetujuan') }}</h3>
                </div>
                <flux:badge color="yellow">{{ $this->pendingLetters->count() }} menunggu</flux:badge>
            </div>
            <div class="divide-y divide-zinc-100 dark:divide-zinc-800/40">
                @foreach ($this->pendingLetters as $letter)
                    <div class="grid grid-cols-4 gap-4 px-5 py-3 text-sm hover:bg-amber-50/30 dark:hover:bg-amber-900/5 transition-colors">
                        <span class="font-mono text-xs text-zinc-600 dark:text-zinc-400">{{ $letter->letter_number }}</span>
                        <span class="text-zinc-700 dark:text-zinc-300">{{ Str::limit($letter->subject, 40) }}</span>
                        <span class="text-zinc-500 dark:text-zinc-400">{{ $letter->author?->name ?? '-' }}</span>
                        <span class="text-zinc-400 dark:text-zinc-500 text-right">{{ $letter->created_at->format('d/m/Y') }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Quick Actions (Admin Only) -->
    @if ($this->isAdmin)
        <div class="animate-fade-in-up delay-300">
            <h3 class="text-sm font-semibold text-zinc-400 uppercase tracking-wider mb-4">{{ __('Aksi Cepat') }}</h3>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                <a href="{{ route('students.create') }}" wire:navigate class="flex flex-col items-center gap-2 p-4 rounded-xl bg-white dark:bg-zinc-900/80 border border-zinc-200/40 dark:border-zinc-800/60 hover:border-indigo-300 dark:hover:border-indigo-700 hover:shadow-md transition-all duration-200 group">
                    <div class="w-10 h-10 rounded-lg bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <flux:icon name="plus" class="size-5 text-blue-600 dark:text-blue-400" />
                    </div>
                    <span class="text-xs font-medium text-zinc-600 dark:text-zinc-400 text-center">{{ __('Tambah Siswa') }}</span>
                </a>
                <a href="{{ route('employees.create') }}" wire:navigate class="flex flex-col items-center gap-2 p-4 rounded-xl bg-white dark:bg-zinc-900/80 border border-zinc-200/40 dark:border-zinc-800/60 hover:border-green-300 dark:hover:border-green-700 hover:shadow-md transition-all duration-200 group">
                    <div class="w-10 h-10 rounded-lg bg-green-50 dark:bg-green-900/20 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <flux:icon name="plus" class="size-5 text-green-600 dark:text-green-400" />
                    </div>
                    <span class="text-xs font-medium text-zinc-600 dark:text-zinc-400 text-center">{{ __('Tambah Pegawai') }}</span>
                </a>
                <a href="{{ route('admin.users') }}" wire:navigate class="flex flex-col items-center gap-2 p-4 rounded-xl bg-white dark:bg-zinc-900/80 border border-zinc-200/40 dark:border-zinc-800/60 hover:border-purple-300 dark:hover:border-purple-700 hover:shadow-md transition-all duration-200 group">
                    <div class="w-10 h-10 rounded-lg bg-purple-50 dark:bg-purple-900/20 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <flux:icon name="users" class="size-5 text-purple-600 dark:text-purple-400" />
                    </div>
                    <span class="text-xs font-medium text-zinc-600 dark:text-zinc-400 text-center">{{ __('Kelola User') }}</span>
                </a>
                <a href="{{ route('admin.roles') }}" wire:navigate class="flex flex-col items-center gap-2 p-4 rounded-xl bg-white dark:bg-zinc-900/80 border border-zinc-200/40 dark:border-zinc-800/60 hover:border-amber-300 dark:hover:border-amber-700 hover:shadow-md transition-all duration-200 group">
                    <div class="w-10 h-10 rounded-lg bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <flux:icon name="shield-check" class="size-5 text-amber-600 dark:text-amber-400" />
                    </div>
                    <span class="text-xs font-medium text-zinc-600 dark:text-zinc-400 text-center">{{ __('Kelola Role') }}</span>
                </a>
                <a href="{{ route('master.school-profile') }}" wire:navigate class="flex flex-col items-center gap-2 p-4 rounded-xl bg-white dark:bg-zinc-900/80 border border-zinc-200/40 dark:border-zinc-800/60 hover:border-cyan-300 dark:hover:border-cyan-700 hover:shadow-md transition-all duration-200 group">
                    <div class="w-10 h-10 rounded-lg bg-cyan-50 dark:bg-cyan-900/20 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <flux:icon name="building-office" class="size-5 text-cyan-600 dark:text-cyan-400" />
                    </div>
                    <span class="text-xs font-medium text-zinc-600 dark:text-zinc-400 text-center">{{ __('Profil Sekolah') }}</span>
                </a>
            </div>
        </div>
    @endif

    <!-- Quick Actions for Teachers -->
    @if ($this->isGuru && !$this->isAdmin)
        <div class="animate-fade-in-up delay-300">
            <h3 class="text-sm font-semibold text-zinc-400 uppercase tracking-wider mb-4">{{ __('Aksi Cepat Guru') }}</h3>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                <a href="{{ route('academic.schedules') }}" wire:navigate class="flex flex-col items-center gap-2 p-4 rounded-xl bg-white dark:bg-zinc-900/80 border border-zinc-200/40 dark:border-zinc-800/60 hover:border-teal-300 dark:hover:border-teal-700 hover:shadow-md transition-all duration-200 group">
                    <div class="w-10 h-10 rounded-lg bg-teal-50 dark:bg-teal-900/20 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <flux:icon name="calendar-days" class="size-5 text-teal-600 dark:text-teal-400" />
                    </div>
                    <span class="text-xs font-medium text-zinc-600 dark:text-zinc-400 text-center">{{ __('Lihat Jadwal') }}</span>
                </a>
                <a href="{{ route('academic.subjects') }}" wire:navigate class="flex flex-col items-center gap-2 p-4 rounded-xl bg-white dark:bg-zinc-900/80 border border-zinc-200/40 dark:border-zinc-800/60 hover:border-indigo-300 dark:hover:border-indigo-700 hover:shadow-md transition-all duration-200 group">
                    <div class="w-10 h-10 rounded-lg bg-indigo-50 dark:bg-indigo-900/20 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <flux:icon name="book-open" class="size-5 text-indigo-600 dark:text-indigo-400" />
                    </div>
                    <span class="text-xs font-medium text-zinc-600 dark:text-zinc-400 text-center">{{ __('Mata Pelajaran') }}</span>
                </a>
                <a href="{{ route('academic.extracurriculars') }}" wire:navigate class="flex flex-col items-center gap-2 p-4 rounded-xl bg-white dark:bg-zinc-900/80 border border-zinc-200/40 dark:border-zinc-800/60 hover:border-purple-300 dark:hover:border-purple-700 hover:shadow-md transition-all duration-200 group">
                    <div class="w-10 h-10 rounded-lg bg-purple-50 dark:bg-purple-900/20 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <flux:icon name="sparkles" class="size-5 text-purple-600 dark:text-purple-400" />
                    </div>
                    <span class="text-xs font-medium text-zinc-600 dark:text-zinc-400 text-center">{{ __('Ekstrakurikuler') }}</span>
                </a>
            </div>
        </div>
    @endif

    <!-- Quick Actions for Kepala Sekolah -->
    @if ($this->isKepalaSekolah && !$this->isAdmin)
        <div class="animate-fade-in-up delay-300">
            <h3 class="text-sm font-semibold text-zinc-400 uppercase tracking-wider mb-4">{{ __('Aksi Cepat') }}</h3>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                <a href="{{ route('reports.index') }}" wire:navigate class="flex flex-col items-center gap-2 p-4 rounded-xl bg-white dark:bg-zinc-900/80 border border-zinc-200/40 dark:border-zinc-800/60 hover:border-indigo-300 dark:hover:border-indigo-700 hover:shadow-md transition-all duration-200 group">
                    <div class="w-10 h-10 rounded-lg bg-indigo-50 dark:bg-indigo-900/20 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <flux:icon name="document-chart-bar" class="size-5 text-indigo-600 dark:text-indigo-400" />
                    </div>
                    <span class="text-xs font-medium text-zinc-600 dark:text-zinc-400 text-center">{{ __('Lihat Laporan') }}</span>
                </a>
                <a href="{{ route('letters.index') }}" wire:navigate class="flex flex-col items-center gap-2 p-4 rounded-xl bg-white dark:bg-zinc-900/80 border border-zinc-200/40 dark:border-zinc-800/60 hover:border-amber-300 dark:hover:border-amber-700 hover:shadow-md transition-all duration-200 group">
                    <div class="w-10 h-10 rounded-lg bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <flux:icon name="envelope" class="size-5 text-amber-600 dark:text-amber-400" />
                    </div>
                    <span class="text-xs font-medium text-zinc-600 dark:text-zinc-400 text-center">{{ __('Surat Menyurat') }}</span>
                </a>
                <a href="{{ route('reports.attendance') }}" wire:navigate class="flex flex-col items-center gap-2 p-4 rounded-xl bg-white dark:bg-zinc-900/80 border border-zinc-200/40 dark:border-zinc-800/60 hover:border-cyan-300 dark:hover:border-cyan-700 hover:shadow-md transition-all duration-200 group">
                    <div class="w-10 h-10 rounded-lg bg-cyan-50 dark:bg-cyan-900/20 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <flux:icon name="clipboard-document-check" class="size-5 text-cyan-600 dark:text-cyan-400" />
                    </div>
                    <span class="text-xs font-medium text-zinc-600 dark:text-zinc-400 text-center">{{ __('Laporan Kehadiran') }}</span>
                </a>
            </div>
        </div>
    @endif

    <!-- Announcements -->
    @if ($this->announcements->isNotEmpty())
        <div class="animate-fade-in-up delay-400">
            <h3 class="text-sm font-semibold text-zinc-400 uppercase tracking-wider mb-4">{{ __('Pengumuman') }}</h3>
            <div class="space-y-3">
                @foreach ($this->announcements as $announcement)
                    <div class="p-4 rounded-xl bg-white dark:bg-zinc-900/80 border {{ $announcement->is_pinned ? 'border-amber-200/60 dark:border-amber-800/40' : 'border-zinc-200/40 dark:border-zinc-800/60' }} hover:shadow-md transition-all duration-200">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-2">
                                    @if ($announcement->is_pinned)
                                        <flux:icon name="map-pin" variant="solid" class="size-3.5 text-amber-500 shrink-0" />
                                    @endif
                                    <flux:badge size="sm" color="{{ \App\Models\Announcement::TYPE_COLORS[$announcement->type] ?? 'zinc' }}">
                                        {{ \App\Models\Announcement::TYPES[$announcement->type] ?? $announcement->type }}
                                    </flux:badge>
                                    <span class="text-xs text-zinc-400 dark:text-zinc-500">
                                        {{ $announcement->published_at?->diffForHumans() ?? $announcement->created_at->diffForHumans() }}
                                    </span>
                                </div>
                                <h4 class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">{{ $announcement->title }}</h4>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1 line-clamp-2">
                                    {{ $announcement->excerpt }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Data Tables -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 animate-fade-in-up delay-500">
        <!-- Recent Students -->
        @if ($this->recentStudents->isNotEmpty())
            <div class="rounded-2xl bg-white dark:bg-zinc-900/80 border border-zinc-200/40 dark:border-zinc-800/60 overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-zinc-100 dark:border-zinc-800/60">
                    <h3 class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">{{ __('Siswa Terbaru') }}</h3>
                    <a href="{{ route('students.index') }}" wire:navigate class="text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 transition-colors">
                        {{ __('Lihat Semua') }} &rarr;
                    </a>
                </div>
                <div class="divide-y divide-zinc-100 dark:divide-zinc-800/40">
                    @foreach ($this->recentStudents as $student)
                        <div class="flex items-center justify-between px-5 py-3 hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-linear-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white text-xs font-semibold">
                                    {{ Str::of($student->name)->explode(' ')->take(2)->map(fn($w) => Str::substr($w, 0, 1))->implode('') }}
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ $student->name }}</p>
                                    <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ $student->nis }}</p>
                                </div>
                            </div>
                            <flux:badge size="sm" color="{{ $student->status === 'aktif' ? 'green' : 'zinc' }}">
                                {{ $student->classroom?->name ?? '-' }}
                            </flux:badge>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Recent Employees -->
        @if ($this->recentEmployees->isNotEmpty())
            <div class="rounded-2xl bg-white dark:bg-zinc-900/80 border border-zinc-200/40 dark:border-zinc-800/60 overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-zinc-100 dark:border-zinc-800/60">
                    <h3 class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">{{ __('Pegawai Terbaru') }}</h3>
                    <a href="{{ route('employees.index') }}" wire:navigate class="text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 transition-colors">
                        {{ __('Lihat Semua') }} &rarr;
                    </a>
                </div>
                <div class="divide-y divide-zinc-100 dark:divide-zinc-800/40">
                    @foreach ($this->recentEmployees as $employee)
                        <div class="flex items-center justify-between px-5 py-3 hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-linear-to-br from-purple-500 to-pink-600 flex items-center justify-center text-white text-xs font-semibold">
                                    {{ Str::of($employee->name)->explode(' ')->take(2)->map(fn($w) => Str::substr($w, 0, 1))->implode('') }}
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ $employee->name }}</p>
                                    <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ $employee->nip ?? $employee->nuptk ?? '-' }}</p>
                                </div>
                            </div>
                            <flux:badge size="sm" color="{{ $employee->employee_type === 'guru' ? 'blue' : 'purple' }}">
                                {{ $employee->position?->name ?? '-' }}
                            </flux:badge>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <!-- User Info Card -->
    <div class="rounded-2xl bg-white dark:bg-zinc-900/80 border border-zinc-200/40 dark:border-zinc-800/60 p-5 animate-fade-in-up delay-600">
        <h3 class="text-sm font-semibold text-zinc-400 uppercase tracking-wider mb-4">{{ __('Informasi Akun') }}</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-1">{{ __('Nama') }}</p>
                <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ auth()->user()->name }}</p>
            </div>
            <div>
                <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-1">{{ __('Email') }}</p>
                <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ auth()->user()->email }}</p>
            </div>
            <div>
                <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-1">{{ __('Role') }}</p>
                <flux:badge color="{{ auth()->user()->role?->name === 'admin' ? 'red' : 'blue' }}">
                    {{ auth()->user()->role?->display_name ?? '-' }}
                </flux:badge>
            </div>
        </div>
    </div>
</div>