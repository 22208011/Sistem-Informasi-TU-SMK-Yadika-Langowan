<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-50 dark:bg-zinc-900 antialiased">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200/60 bg-white dark:border-zinc-800 dark:bg-zinc-950">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Platform')">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                {{-- Portal Orang Tua - Only for Parents --}}
                @if(auth()->user()->isParent())
                <flux:sidebar.group icon="home" :heading="__('Portal Orang Tua')" expandable :expanded="request()->routeIs('parent.*')">
                    <flux:sidebar.item icon="clipboard-document-check" :href="route('parent.attendance')" :current="request()->routeIs('parent.attendance')" wire:navigate>
                        {{ __('Kehadiran Anak') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="megaphone" :href="route('parent.announcements')" :current="request()->routeIs('parent.announcements')" wire:navigate>
                        {{ __('Pengumuman') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="envelope" :href="route('parent.letters')" :current="request()->routeIs('parent.letters')" wire:navigate>
                        {{ __('Surat dari Sekolah') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
                @endif

                {{-- Portal Siswa - Only for Students --}}
                @if(auth()->user()->isStudent())
                <flux:sidebar.group icon="academic-cap" :heading="__('Portal Siswa')" expandable :expanded="request()->routeIs('student-portal.*')">
                    <flux:sidebar.item icon="home" :href="route('student-portal.dashboard')" :current="request()->routeIs('student-portal.dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="document-text" :href="route('student-portal.letter-requests')" :current="request()->routeIs('student-portal.letter-requests') || request()->routeIs('student-portal.letter-requests.create')" wire:navigate>
                        {{ __('Permohonan Surat') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="megaphone" :href="route('student-portal.announcements')" :current="request()->routeIs('student-portal.announcements')" wire:navigate>
                        {{ __('Pengumuman') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
                @endif

                @if(auth()->user()->isAdmin() || auth()->user()->hasAnyPermission(['master.view', 'master.create', 'master.edit']))
                <flux:sidebar.group icon="circle-stack" :heading="__('Master Data')" expandable :expanded="request()->routeIs('master.*')">
                    <flux:sidebar.item icon="building-office" :href="route('master.school-profile')" :current="request()->routeIs('master.school-profile')" wire:navigate>
                        {{ __('Profil Sekolah') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="calendar" :href="route('master.academic-years')" :current="request()->routeIs('master.academic-years')" wire:navigate>
                        {{ __('Tahun Ajaran') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="briefcase" :href="route('master.departments')" :current="request()->routeIs('master.departments')" wire:navigate>
                        {{ __('Jurusan') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="academic-cap" :href="route('master.classrooms')" :current="request()->routeIs('master.classrooms')" wire:navigate>
                        {{ __('Kelas') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
                @endif

                {{-- Akademik Menu for Teachers and Staff --}}
                @if(auth()->user()->isAdmin() || auth()->user()->hasAnyPermission(['subjects.view', 'schedule.view', 'schedule.view_own', 'extracurriculars.view']))
                <flux:sidebar.group icon="academic-cap" :heading="__('Akademik')" expandable :expanded="request()->routeIs('academic.*')">
                    @if(auth()->user()->isAdmin() || auth()->user()->hasAnyPermission(['subjects.view']))
                    <flux:sidebar.item icon="book-open" :href="route('academic.subjects')" :current="request()->routeIs('academic.subjects')" wire:navigate>
                        {{ __('Mata Pelajaran') }}
                    </flux:sidebar.item>
                    @endif
                    @if(auth()->user()->isAdmin() || auth()->user()->hasAnyPermission(['schedule.view', 'schedule.view_own']))
                    <flux:sidebar.item icon="calendar-days" :href="route('academic.schedules')" :current="request()->routeIs('academic.schedules')" wire:navigate>
                        {{ __('Jadwal Pelajaran') }}
                    </flux:sidebar.item>
                    @endif
                    @if(auth()->user()->isAdmin() || auth()->user()->hasAnyPermission(['extracurriculars.view']))
                    <flux:sidebar.item icon="sparkles" :href="route('academic.extracurriculars')" :current="request()->routeIs('academic.extracurriculars')" wire:navigate>
                        {{ __('Ekstrakurikuler') }}
                    </flux:sidebar.item>
                    @endif
                </flux:sidebar.group>
                @endif

                @if(auth()->user()->isAdmin() || auth()->user()->hasAnyPermission(['students.view', 'students.create', 'students.edit', 'graduates.view']))
                <flux:sidebar.group icon="user-group" :heading="__('Kesiswaan')" expandable :expanded="request()->routeIs('students.*') || request()->routeIs('guardians.*')">
                    <flux:sidebar.item icon="users" :href="route('students.index')" :current="request()->routeIs('students.index') || request()->routeIs('students.show') || request()->routeIs('students.create') || request()->routeIs('students.edit')" wire:navigate>
                        {{ __('Data Siswa') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="user-group" :href="route('guardians.index')" :current="request()->routeIs('guardians.*')" wire:navigate>
                        {{ __('Data Wali Murid') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="arrows-right-left" :href="route('students.mutations.index')" :current="request()->routeIs('students.mutations.*')" wire:navigate>
                        {{ __('Mutasi Siswa') }}
                    </flux:sidebar.item>
                    @if(auth()->user()->isAdmin() || auth()->user()->hasAnyPermission(['graduates.view']))
                    <flux:sidebar.item icon="academic-cap" :href="route('students.graduates')" :current="request()->routeIs('students.graduates')" wire:navigate>
                        {{ __('Data Lulusan') }}
                    </flux:sidebar.item>
                    @endif
                    @if(auth()->user()->isAdmin() || auth()->user()->hasAnyPermission(['attendance.view', 'attendance.create', 'attendance.edit']))
                    <flux:sidebar.item icon="clipboard-document-check" :href="route('students.attendance.index')" :current="request()->routeIs('students.attendance.index')" wire:navigate>
                        {{ __('Kehadiran Siswa') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="chart-bar" :href="route('students.attendance.recap')" :current="request()->routeIs('students.attendance.recap')" wire:navigate>
                        {{ __('Rekap Kehadiran') }}
                    </flux:sidebar.item>
                    @endif
                </flux:sidebar.group>
                @endif

                @if(auth()->user()->isAdmin() || auth()->user()->hasAnyPermission(['employees.view', 'employees.create', 'employees.edit']))
                <flux:sidebar.group icon="briefcase" :heading="__('Kepegawaian')" expandable :expanded="request()->routeIs('employees.*')">
                    <flux:sidebar.item icon="users" :href="route('employees.index')" :current="request()->routeIs('employees.index') || request()->routeIs('employees.show') || request()->routeIs('employees.create') || request()->routeIs('employees.edit')" wire:navigate>
                        {{ __('Data Pegawai') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="briefcase" :href="route('employees.positions')" :current="request()->routeIs('employees.positions')" wire:navigate>
                        {{ __('Jabatan') }}
                    </flux:sidebar.item>
                    @if(auth()->user()->isAdmin() || auth()->user()->hasAnyPermission(['attendance.view', 'attendance.create', 'attendance.edit']))
                    <flux:sidebar.item icon="clipboard-document-check" :href="route('employees.attendance.index')" :current="request()->routeIs('employees.attendance.index')" wire:navigate>
                        {{ __('Kehadiran Pegawai') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="chart-bar" :href="route('employees.attendance.recap')" :current="request()->routeIs('employees.attendance.recap')" wire:navigate>
                        {{ __('Rekap Kehadiran') }}
                    </flux:sidebar.item>
                    @endif
                </flux:sidebar.group>
                @endif

                {{-- Inventaris --}}
                @if(auth()->user()->isAdmin() || auth()->user()->hasAnyPermission(['inventory.view', 'inventory.create', 'inventory.edit']))
                <flux:sidebar.group icon="cube" :heading="__('Inventaris')" expandable :expanded="request()->routeIs('inventory.*')">
                    <flux:sidebar.item icon="archive-box" :href="route('inventory.items')" :current="request()->routeIs('inventory.items')" wire:navigate>
                        {{ __('Data Barang') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="arrow-path-rounded-square" :href="route('inventory.borrowings')" :current="request()->routeIs('inventory.borrowings')" wire:navigate>
                        {{ __('Peminjaman') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
                @endif

                {{-- Keuangan --}}
                @if(auth()->user()->isAdmin() || auth()->user()->hasAnyPermission(['finance.view', 'finance.create', 'finance.edit']))
                <flux:sidebar.group icon="banknotes" :heading="__('Keuangan')" expandable :expanded="request()->routeIs('finance.*')">
                    <flux:sidebar.item icon="credit-card" :href="route('finance.payments')" :current="request()->routeIs('finance.payments')" wire:navigate>
                        {{ __('Pembayaran') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="chart-pie" :href="route('finance.reports')" :current="request()->routeIs('finance.reports')" wire:navigate>
                        {{ __('Laporan Keuangan') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
                @endif

                {{-- Pengumuman --}}
                @if((auth()->user()->isAdmin() || auth()->user()->hasAnyPermission(['announcements.view', 'announcements.create'])) && !auth()->user()->isStudent() && !auth()->user()->isParent())
                <flux:sidebar.group :heading="__('Informasi')">
                    <flux:sidebar.item icon="megaphone" :href="route('academic.announcements')" :current="request()->routeIs('academic.announcements')" wire:navigate>
                        {{ __('Pengumuman') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
                @endif

                {{-- Surat Menyurat --}}
                @if(auth()->user()->isAdmin() || auth()->user()->hasAnyPermission(['letters.view', 'letters.create', 'calling-letters.view']))
                <flux:sidebar.group icon="document-text" :heading="__('Surat Menyurat')" expandable :expanded="request()->routeIs('letters.*')">
                    @if(auth()->user()->isAdmin() || auth()->user()->hasAnyPermission(['letters.view', 'letters.create']))
                    <flux:sidebar.item icon="inbox-arrow-down" :href="route('letters.incoming')" :current="request()->routeIs('letters.incoming')" wire:navigate>
                        {{ __('Surat Masuk') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="paper-airplane" :href="route('letters.outgoing')" :current="request()->routeIs('letters.outgoing')" wire:navigate>
                        {{ __('Surat Keluar') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="document-duplicate" :href="route('letters.templates')" :current="request()->routeIs('letters.templates')" wire:navigate>
                        {{ __('Template Surat') }}
                    </flux:sidebar.item>
                    @endif
                    @if(auth()->user()->isAdmin() || auth()->user()->hasAnyPermission(['calling-letters.view']))
                    <flux:sidebar.item icon="phone" :href="route('letters.calling-letters')" :current="request()->routeIs('letters.calling-letters')" wire:navigate>
                        {{ __('Surat Panggilan') }}
                    </flux:sidebar.item>
                    @endif
                    @if(auth()->user()->isAdmin() || auth()->user()->hasAnyPermission(['letters.view']))
                    <flux:sidebar.item icon="inbox-stack" :href="route('letters.requests')" :current="request()->routeIs('letters.requests')" wire:navigate>
                        {{ __('Permohonan Surat') }}
                    </flux:sidebar.item>
                    @endif
                </flux:sidebar.group>
                @endif

                {{-- Laporan for Kepala Sekolah & Admin --}}
                @if(auth()->user()->isAdmin() || auth()->user()->hasAnyPermission(['reports.view', 'reports.students', 'reports.employees']))
                <flux:sidebar.group icon="chart-bar" :heading="__('Laporan')" expandable :expanded="request()->routeIs('reports.*')">
                    <flux:sidebar.item icon="document-chart-bar" :href="route('reports.index')" :current="request()->routeIs('reports.index')" wire:navigate>
                        {{ __('Ringkasan') }}
                    </flux:sidebar.item>
                    @if(auth()->user()->isAdmin() || auth()->user()->hasAnyPermission(['reports.students']))
                    <flux:sidebar.item icon="users" :href="route('reports.students')" :current="request()->routeIs('reports.students')" wire:navigate>
                        {{ __('Laporan Siswa') }}
                    </flux:sidebar.item>
                    @endif
                    @if(auth()->user()->isAdmin() || auth()->user()->hasAnyPermission(['reports.employees']))
                    <flux:sidebar.item icon="user-group" :href="route('reports.employees')" :current="request()->routeIs('reports.employees')" wire:navigate>
                        {{ __('Laporan Pegawai') }}
                    </flux:sidebar.item>
                    @endif
                    @if(auth()->user()->isAdmin() || auth()->user()->hasAnyPermission(['attendance.view']))
                    <flux:sidebar.item icon="clipboard-document-check" :href="route('reports.attendance')" :current="request()->routeIs('reports.attendance')" wire:navigate>
                        {{ __('Laporan Kehadiran') }}
                    </flux:sidebar.item>
                    @endif
                </flux:sidebar.group>
                @endif

                @if(auth()->user()->isAdmin() || auth()->user()->hasAnyPermission(['users.view', 'roles.view', 'audit-logs.view']))
                <flux:sidebar.group icon="cog-6-tooth" :heading="__('Admin Panel')" expandable :expanded="request()->routeIs('admin.*')">
                    @if(auth()->user()->hasAnyPermission(['users.view']) || auth()->user()->isAdmin())
                    <flux:sidebar.item icon="user-group" :href="route('admin.users')" :current="request()->routeIs('admin.users')" wire:navigate>
                        {{ __('Manajemen User') }}
                    </flux:sidebar.item>
                    @endif
                    @if(auth()->user()->hasAnyPermission(['roles.view']) || auth()->user()->isAdmin())
                    <flux:sidebar.item icon="shield-check" :href="route('admin.roles')" :current="request()->routeIs('admin.roles')" wire:navigate>
                        {{ __('Manajemen Role') }}
                    </flux:sidebar.item>
                    @endif
                    @if(auth()->user()->hasAnyPermission(['audit-logs.view']) || auth()->user()->isAdmin())
                    <flux:sidebar.item icon="document-magnifying-glass" :href="route('admin.audit-logs')" :current="request()->routeIs('admin.audit-logs')" wire:navigate>
                        {{ __('Audit Log') }}
                    </flux:sidebar.item>
                    @endif
                </flux:sidebar.group>
                @endif
            </flux:sidebar.nav>

            <flux:spacer />

            <flux:sidebar.nav>
                {{-- Theme Toggle --}}
                <flux:sidebar.item id="theme-toggle-sidebar" as="button" icon="moon" class="cursor-pointer dark:hidden">
                    {{ __('Mode Gelap') }}
                </flux:sidebar.item>
                <flux:sidebar.item id="theme-toggle-sidebar-dark" as="button" icon="sun" class="cursor-pointer hidden dark:flex">
                    {{ __('Mode Terang') }}
                </flux:sidebar.item>

                <flux:sidebar.item icon="cog-6-tooth" :href="route('profile.edit')" :current="request()->routeIs('profile.edit')" wire:navigate>
                    {{ __('Pengaturan') }}
                </flux:sidebar.item>

                {{-- Logout Button --}}
                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:sidebar.item
                        as="button"
                        type="submit"
                        icon="arrow-right-start-on-rectangle"
                        class="w-full text-red-600 hover:text-red-700 hover:bg-red-50 dark:text-red-400 dark:hover:text-red-300 dark:hover:bg-red-900/20"
                    >
                        {{ __('Keluar') }}
                    </flux:sidebar.item>
                </form>
            </flux:sidebar.nav>

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>


        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            {{-- Mobile Theme Toggle --}}
            <button id="theme-toggle-mobile" type="button" aria-label="Toggle dark mode" class="rounded-lg p-2 text-zinc-500 hover:text-zinc-700 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:text-zinc-200 dark:hover:bg-zinc-800 transition-colors mr-2">
                <svg class="w-5 h-5 hidden dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="5"/><path d="M12 1v2m0 18v2M4.22 4.22l1.42 1.42m12.72 12.72l1.42 1.42M1 12h2m18 0h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/>
                </svg>
                <svg class="w-5 h-5 block dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/>
                </svg>
            </button>

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{-- Page Loading Indicator --}}
        <div id="page-loading" class="loading-overlay" wire:loading.class="active">
            <div class="flex flex-col items-center gap-3">
                <svg class="size-10 animate-spin text-primary-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm font-medium text-zinc-600 dark:text-zinc-300">Memuat...</span>
            </div>
        </div>

        {{ $slot }}

        {{-- Flux Scripts - using direct file for reliability --}}
        <?php app('livewire')->forceAssetInjection(); ?>
        <script src="{{ asset('vendor/flux/flux.js') }}" data-navigate-once></script>
        @livewireScripts

        {{-- Performance optimization scripts --}}
        <script>
            // Page navigation loading indicator
            document.addEventListener('livewire:navigate-start', () => {
                document.getElementById('page-loading')?.classList.add('active');
            });
            document.addEventListener('livewire:navigate-end', () => {
                document.getElementById('page-loading')?.classList.remove('active');
            });

            // Lazy load images
            document.addEventListener('DOMContentLoaded', () => {
                const images = document.querySelectorAll('img[loading="lazy"]');
                images.forEach(img => {
                    img.addEventListener('load', () => img.classList.add('loaded'));
                    if (img.complete) img.classList.add('loaded');
                });
            });

            // Prefetch links on hover
            document.addEventListener('DOMContentLoaded', () => {
                const links = document.querySelectorAll('a[wire\\:navigate]');
                links.forEach(link => {
                    link.addEventListener('mouseenter', () => {
                        const href = link.getAttribute('href');
                        if (href && !document.querySelector(`link[rel="prefetch"][href="${href}"]`)) {
                            const prefetch = document.createElement('link');
                            prefetch.rel = 'prefetch';
                            prefetch.href = href;
                            document.head.appendChild(prefetch);
                        }
                    }, { once: true });
                });
            });
        </script>
    </body>
</html>
