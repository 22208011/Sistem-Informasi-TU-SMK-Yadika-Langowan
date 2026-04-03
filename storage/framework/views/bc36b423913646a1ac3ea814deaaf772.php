<?php
    $schoolProfile = \App\Models\SchoolProfile::getProfile();
?>
<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo e($schoolProfile?->name ?? config('app.name')); ?> - Sistem Informasi Tata Usaha</title>
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
</head>
<body class="min-h-screen bg-linear-to-br from-slate-50 via-blue-50 to-indigo-100 dark:from-zinc-950 dark:via-zinc-900 dark:to-zinc-800 antialiased">
    <!-- Animated Background Elements -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-40 -right-40 w-80 h-80 bg-linear-to-br from-blue-400/30 to-indigo-400/30 rounded-full blur-3xl animate-float"></div>
        <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-linear-to-br from-purple-400/30 to-pink-400/30 rounded-full blur-3xl animate-float" style="animation-delay: 2s;"></div>
        <div class="absolute top-1/3 left-1/4 w-64 h-64 bg-linear-to-br from-cyan-400/20 to-blue-400/20 rounded-full blur-3xl animate-float" style="animation-delay: 4s;"></div>
        <div class="absolute bottom-1/4 right-1/4 w-72 h-72 bg-linear-to-br from-emerald-400/20 to-teal-400/20 rounded-full blur-3xl animate-float" style="animation-delay: 3s;"></div>
    </div>

    <!-- Navigation -->
    <nav class="relative z-10 px-6 py-4 lg:px-12">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-4">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($schoolProfile?->logo_url): ?>
                    <img src="<?php echo e($schoolProfile->logo_url); ?>" alt="<?php echo e($schoolProfile->name); ?>" class="h-14 w-auto" />
                <?php else: ?>
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-linear-to-br from-red-600 to-indigo-600 shadow-lg">
                        <svg class="size-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                        </svg>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <div>
                    <h1 class="text-lg font-bold text-zinc-800 dark:text-white"><?php echo e($schoolProfile?->name ?? config('app.name')); ?></h1>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Sistem Informasi Tata Usaha</p>
                </div>
            </div>
            
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(Route::has('login')): ?>
                <div class="flex items-center gap-3">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->guard()->check()): ?>
                        <a href="<?php echo e(url('/dashboard')); ?>" class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-semibold text-white bg-linear-to-r from-blue-600 to-indigo-600 rounded-full shadow-lg shadow-blue-500/25 hover:shadow-xl hover:shadow-blue-500/30 hover:scale-105 transition-all duration-300">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            Dashboard
                        </a>
                    <?php else: ?>
                        <a href="<?php echo e(route('login')); ?>" class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-semibold text-white bg-linear-to-r from-blue-600 to-indigo-600 rounded-full shadow-lg shadow-blue-500/25 hover:shadow-xl hover:shadow-blue-500/30 hover:scale-105 transition-all duration-300">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                            </svg>
                            Login
                        </a>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(Route::has('register')): ?>
                            <a href="<?php echo e(route('register')); ?>" class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-semibold text-zinc-700 dark:text-zinc-300 bg-white/80 dark:bg-zinc-800/80 backdrop-blur-sm rounded-full border border-zinc-200 dark:border-zinc-700 hover:border-blue-500 hover:text-blue-600 hover:scale-105 transition-all duration-300">
                                Register
                            </a>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </nav>

    <!-- Hero Section -->
    <main class="relative z-10">
        <div class="max-w-7xl mx-auto px-6 lg:px-12 pt-16 pb-24 lg:pt-24 lg:pb-32">
            <div class="grid lg:grid-cols-2 gap-12 lg:gap-20 items-center">
                <!-- Left Content -->
                <div class="space-y-8 animate-fade-in-up">
                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-sm font-medium">
                        <span class="flex h-2 w-2 rounded-full bg-blue-500 animate-pulse"></span>
                        Sistem Terintegrasi & Modern
                    </div>
                    
                    <h1 class="text-4xl lg:text-6xl font-extrabold text-zinc-900 dark:text-white leading-tight">
                        Kelola Administrasi Sekolah dengan 
                        <span class="bg-linear-to-r from-blue-600 via-indigo-600 to-purple-600 bg-clip-text text-transparent">Mudah & Efisien</span>
                    </h1>
                    
                    <p class="text-lg lg:text-xl text-zinc-600 dark:text-zinc-400 leading-relaxed">
                        Sistem informasi tata usaha yang lengkap untuk mengelola data siswa, pegawai, inventaris, keuangan, dan administrasi sekolah dalam satu platform terintegrasi.
                    </p>
                    
                    <div class="flex flex-wrap gap-4">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->guard()->check()): ?>
                            <a href="<?php echo e(url('/dashboard')); ?>" class="group inline-flex items-center gap-3 px-8 py-4 text-lg font-semibold text-white bg-linear-to-r from-blue-600 to-indigo-600 rounded-2xl shadow-xl shadow-blue-500/25 hover:shadow-2xl hover:shadow-blue-500/30 hover:scale-105 transition-all duration-300">
                                <span>Masuk Dashboard</span>
                                <svg class="size-6 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </a>
                        <?php else: ?>
                            <a href="<?php echo e(route('login')); ?>" class="group inline-flex items-center gap-3 px-8 py-4 text-lg font-semibold text-white bg-linear-to-r from-blue-600 to-indigo-600 rounded-2xl shadow-xl shadow-blue-500/25 hover:shadow-2xl hover:shadow-blue-500/30 hover:scale-105 transition-all duration-300">
                                <span>Mulai Sekarang</span>
                                <svg class="size-6 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </a>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>

                <!-- Right Content - Features Grid -->
                <div class="grid grid-cols-2 gap-4 lg:gap-6 animate-fade-in-right delay-200">
                    <!-- Feature Card 1 -->
                    <div class="stat-card blue p-6 shadow-xl hover:shadow-2xl transition-all duration-300 animate-fade-in-up delay-100">
                        <div class="icon-container blue size-14 mb-4">
                            <svg class="size-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-zinc-800 dark:text-white mb-2">Data Siswa</h3>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Kelola data siswa, wali murid, dan kehadiran dengan mudah</p>
                    </div>

                    <!-- Feature Card 2 -->
                    <div class="stat-card green p-6 shadow-xl hover:shadow-2xl transition-all duration-300 animate-fade-in-up delay-200">
                        <div class="icon-container green size-14 mb-4">
                            <svg class="size-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-zinc-800 dark:text-white mb-2">Kepegawaian</h3>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Manajemen data guru, staff, dan absensi pegawai</p>
                    </div>

                    <!-- Feature Card 3 -->
                    <div class="stat-card purple p-6 shadow-xl hover:shadow-2xl transition-all duration-300 animate-fade-in-up delay-300">
                        <div class="icon-container purple size-14 mb-4">
                            <svg class="size-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-zinc-800 dark:text-white mb-2">Inventaris</h3>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Kelola kategori barang, aset sekolah, dan peminjaman barang</p>
                    </div>

                    <!-- Feature Card 4 -->
                    <div class="stat-card amber p-6 shadow-xl hover:shadow-2xl transition-all duration-300 animate-fade-in-up delay-400">
                        <div class="icon-container amber size-14 mb-4">
                            <svg class="size-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-zinc-800 dark:text-white mb-2">Laporan</h3>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Laporan komprehensif dan analitik data sekolah</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Section -->
        <div class="relative py-16 bg-white/50 dark:bg-zinc-900/50 backdrop-blur-xl border-y border-zinc-200/50 dark:border-zinc-700/50">
            <div class="max-w-7xl mx-auto px-6 lg:px-12">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-8">
                    <div class="text-center animate-fade-in-up">
                        <div class="text-4xl lg:text-5xl font-extrabold bg-linear-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">6+</div>
                        <div class="text-sm text-zinc-500 dark:text-zinc-400 mt-2">Role Pengguna</div>
                    </div>
                    <div class="text-center animate-fade-in-up delay-100">
                        <div class="text-4xl lg:text-5xl font-extrabold bg-linear-to-r from-green-600 to-emerald-600 bg-clip-text text-transparent">50+</div>
                        <div class="text-sm text-zinc-500 dark:text-zinc-400 mt-2">Fitur Lengkap</div>
                    </div>
                    <div class="text-center animate-fade-in-up delay-200">
                        <div class="text-4xl lg:text-5xl font-extrabold bg-linear-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">100%</div>
                        <div class="text-sm text-zinc-500 dark:text-zinc-400 mt-2">Terintegrasi</div>
                    </div>
                    <div class="text-center animate-fade-in-up delay-300">
                        <div class="text-4xl lg:text-5xl font-extrabold bg-linear-to-r from-amber-500 to-orange-500 bg-clip-text text-transparent">24/7</div>
                        <div class="text-sm text-zinc-500 dark:text-zinc-400 mt-2">Akses Kapanpun</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Features Detail Section -->
        <div class="max-w-7xl mx-auto px-6 lg:px-12 py-24">
            <div class="text-center mb-16 animate-fade-in-up">
                <h2 class="text-3xl lg:text-4xl font-bold text-zinc-900 dark:text-white mb-4">
                    Fitur Unggulan
                </h2>
                <p class="text-lg text-zinc-500 dark:text-zinc-400 max-w-2xl mx-auto">
                    Sistem yang dirancang khusus untuk memenuhi kebutuhan administrasi sekolah modern
                </p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="glass p-8 rounded-2xl card-hover animate-fade-in-up">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-linear-to-br from-blue-500 to-blue-600 text-white shadow-lg">
                            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-zinc-800 dark:text-white">Master Data Sekolah</h3>
                    </div>
                    <p class="text-zinc-500 dark:text-zinc-400">Kelola profil sekolah, tahun ajaran, jurusan, dan kelas dengan mudah dan terstruktur.</p>
                </div>

                <!-- Feature 2 -->
                <div class="glass p-8 rounded-2xl card-hover animate-fade-in-up delay-100">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-linear-to-br from-green-500 to-emerald-600 text-white shadow-lg">
                            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-zinc-800 dark:text-white">Absensi Digital</h3>
                    </div>
                    <p class="text-zinc-500 dark:text-zinc-400">Sistem kehadiran digital untuk siswa dan pegawai dengan rekap otomatis.</p>
                </div>

                <!-- Feature 3 -->
                <div class="glass p-8 rounded-2xl card-hover animate-fade-in-up delay-200">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-linear-to-br from-purple-500 to-indigo-600 text-white shadow-lg">
                            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-zinc-800 dark:text-white">Keuangan Sekolah</h3>
                    </div>
                    <p class="text-zinc-500 dark:text-zinc-400">Kelola jenis pembayaran, tagihan siswa, transaksi, dan laporan keuangan secara efisien.</p>
                </div>

                <!-- Feature 4 -->
                <div class="glass p-8 rounded-2xl card-hover animate-fade-in-up delay-300">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-linear-to-br from-amber-500 to-orange-600 text-white shadow-lg">
                            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-zinc-800 dark:text-white">Pengumuman</h3>
                    </div>
                    <p class="text-zinc-500 dark:text-zinc-400">Broadcast pengumuman ke siswa, orang tua, dan guru dengan target spesifik.</p>
                </div>

                <!-- Feature 5 -->
                <div class="glass p-8 rounded-2xl card-hover animate-fade-in-up delay-400">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-linear-to-br from-pink-500 to-rose-600 text-white shadow-lg">
                            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-zinc-800 dark:text-white">Surat Menyurat</h3>
                    </div>
                    <p class="text-zinc-500 dark:text-zinc-400">Buat dan kelola surat resmi dengan sistem persetujuan digital.</p>
                </div>

                <!-- Feature 6 -->
                <div class="glass p-8 rounded-2xl card-hover animate-fade-in-up delay-500">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-linear-to-br from-cyan-500 to-teal-600 text-white shadow-lg">
                            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-zinc-800 dark:text-white">Portal Orang Tua</h3>
                    </div>
                    <p class="text-zinc-500 dark:text-zinc-400">Akses khusus orang tua untuk memantau kehadiran, pengumuman, dan surat dari sekolah.</p>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="relative z-10">
        <!-- Contact Info Section -->
        <div class="relative overflow-hidden">
            <!-- Background with gradient overlay -->
            <div class="absolute inset-0 bg-linear-to-br from-slate-900 via-blue-950 to-indigo-950"></div>
            <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=%2260%22 height=%2260%22 viewBox=%220 0 60 60%22 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cg fill=%22none%22 fill-rule=%22evenodd%22%3E%3Cg fill=%22%239C92AC%22 fill-opacity=%220.03%22%3E%3Cpath d=%22M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z%22/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')]"></div>
            
            <div class="relative max-w-7xl mx-auto px-6 lg:px-12 py-20">
                <!-- Header -->
                <div class="text-center mb-16">
                    <div class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-linear-to-r from-cyan-500/20 to-blue-500/20 border border-cyan-500/30 text-cyan-300 text-sm font-semibold mb-6 shadow-lg shadow-cyan-500/10">
                        <svg class="size-4 animate-pulse" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 0C7.802 0 4 3.403 4 7.602 4 11.8 7.469 16.812 12 24c4.531-7.188 8-12.2 8-16.398C20 3.403 16.199 0 12 0zm0 11a3 3 0 110-6 3 3 0 010 6z"/>
                        </svg>
                        Hubungi Kami
                    </div>
                    <h2 class="text-4xl lg:text-5xl font-bold text-white mb-4"><?php echo e($schoolProfile?->name ?? config('app.name')); ?></h2>
                    <p class="text-blue-200 text-lg max-w-2xl mx-auto">
                        NPSN: <?php echo e($schoolProfile?->npsn ?? '-'); ?> • Terakreditasi <?php echo e($schoolProfile?->accreditation ?? '-'); ?>

                    </p>
                </div>

                <div class="grid lg:grid-cols-3 gap-10">
                    <!-- Left Column: Map -->
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($schoolProfile?->latitude && $schoolProfile?->longitude): ?>
                    <div class="lg:col-span-1">
                        <div class="rounded-3xl overflow-hidden shadow-2xl ring-2 ring-white/20 hover:ring-cyan-400/50 transition-all duration-300">
                            <iframe 
                                src="https://maps.google.com/maps?q=<?php echo e($schoolProfile->latitude); ?>,<?php echo e($schoolProfile->longitude); ?>&t=&z=15&ie=UTF8&iwloc=&output=embed"
                                width="100%" 
                                height="300" 
                                style="border:0;" 
                                allowfullscreen="" 
                                loading="lazy" 
                                referrerpolicy="no-referrer-when-downgrade"
                                class="w-full"
                            ></iframe>
                        </div>
                        <a href="<?php echo e($schoolProfile->google_maps_url); ?>" target="_blank" class="mt-5 w-full inline-flex items-center justify-center gap-3 px-6 py-4 bg-linear-to-r from-red-500 to-rose-600 hover:from-red-600 hover:to-rose-700 rounded-2xl font-semibold text-white shadow-lg shadow-red-500/30 hover:shadow-xl hover:shadow-red-500/40 transition-all hover:scale-[1.02]">
                            <svg class="size-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 0C7.802 0 4 3.403 4 7.602 4 11.8 7.469 16.812 12 24c4.531-7.188 8-12.2 8-16.398C20 3.403 16.199 0 12 0zm0 11a3 3 0 110-6 3 3 0 010 6z"/>
                            </svg>
                            Lihat di Google Maps
                        </a>
                    </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    <!-- Middle Column: Contact Info -->
                    <div class="lg:col-span-1 space-y-5">
                        <!-- Address Card -->
                        <div class="p-6 rounded-2xl bg-linear-to-br from-blue-500/10 to-cyan-500/5 backdrop-blur-sm border border-blue-400/20 hover:border-blue-400/40 transition-colors">
                            <div class="flex items-start gap-4">
                                <div class="shrink-0 p-3 bg-linear-to-br from-blue-500 to-cyan-500 rounded-xl shadow-lg shadow-blue-500/30">
                                    <svg class="size-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-bold text-white text-lg mb-2">Alamat Sekolah</h3>
                                    <p class="text-blue-100 text-sm leading-relaxed">
                                        <?php echo e($schoolProfile?->address); ?><br>
                                        <?php echo e($schoolProfile?->village); ?>, <?php echo e($schoolProfile?->district); ?><br>
                                        <?php echo e($schoolProfile?->city); ?>, <?php echo e($schoolProfile?->province); ?><br>
                                        <span class="text-cyan-300 font-medium">Kode Pos: <?php echo e($schoolProfile?->postal_code); ?></span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Phone Card -->
                        <div class="p-6 rounded-2xl bg-linear-to-br from-emerald-500/10 to-teal-500/5 backdrop-blur-sm border border-emerald-400/20 hover:border-emerald-400/40 transition-colors">
                            <div class="flex items-start gap-4">
                                <div class="shrink-0 p-3 bg-linear-to-br from-emerald-500 to-teal-500 rounded-xl shadow-lg shadow-emerald-500/30">
                                    <svg class="size-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-bold text-white text-lg mb-2">Telepon</h3>
                                    <a href="tel:<?php echo e($schoolProfile?->phone); ?>" class="text-emerald-300 hover:text-emerald-200 text-xl font-bold transition-colors">
                                        <?php echo e($schoolProfile?->phone); ?>

                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Email & Hours Card -->
                        <div class="p-6 rounded-2xl bg-linear-to-br from-purple-500/10 to-pink-500/5 backdrop-blur-sm border border-purple-400/20 hover:border-purple-400/40 transition-colors">
                            <div class="flex items-start gap-4">
                                <div class="shrink-0 p-3 bg-linear-to-br from-purple-500 to-pink-500 rounded-xl shadow-lg shadow-purple-500/30">
                                    <svg class="size-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <h3 class="font-bold text-white text-lg mb-2">Email</h3>
                                    <a href="mailto:<?php echo e($schoolProfile?->email); ?>" class="text-purple-300 hover:text-purple-200 text-sm font-medium transition-colors break-all">
                                        <?php echo e($schoolProfile?->email); ?>

                                    </a>
                                    <div class="mt-4 pt-4 border-t border-purple-400/20">
                                        <div class="flex items-center gap-2 text-pink-300 text-sm mb-2">
                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span class="font-semibold">Jam Operasional</span>
                                        </div>
                                        <p class="text-purple-200 text-sm"><?php echo e($schoolProfile?->operational_days); ?></p>
                                        <p class="text-white font-bold text-lg"><?php echo e($schoolProfile?->operational_hours); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: WhatsApp & Social -->
                    <div class="lg:col-span-1 space-y-5">
                        <!-- WhatsApp PPDB Section -->
                        <div class="p-6 rounded-2xl bg-linear-to-br from-green-500/20 to-emerald-600/10 border-2 border-green-400/40 shadow-lg shadow-green-500/10">
                            <div class="flex items-center gap-4 mb-5">
                                <div class="p-3 bg-linear-to-br from-green-500 to-emerald-600 rounded-xl shadow-lg shadow-green-500/40">
                                    <svg class="size-7 text-white" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-bold text-white text-lg">WhatsApp PPDB</h3>
                                    <p class="text-green-300 text-sm">Hubungi untuk info pendaftaran</p>
                                </div>
                            </div>
                            
                            <div class="space-y-3">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($schoolProfile?->whatsapp_1): ?>
                                <a href="<?php echo e($schoolProfile->whatsapp_1_url); ?>" target="_blank" class="flex items-center gap-4 p-4 bg-linear-to-r from-green-500/20 to-emerald-500/10 hover:from-green-500/30 hover:to-emerald-500/20 border border-green-400/30 hover:border-green-400/50 rounded-xl transition-all hover:scale-[1.02] group">
                                    <div class="w-12 h-12 rounded-full bg-linear-to-br from-green-400 to-emerald-500 flex items-center justify-center text-white font-bold text-lg shadow-lg shadow-green-500/30">
                                        <?php echo e(substr($schoolProfile->whatsapp_1_name, 4, 1)); ?>

                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-semibold text-white truncate"><?php echo e($schoolProfile->whatsapp_1_name); ?></p>
                                        <p class="text-green-300 text-sm font-mono">+<?php echo e(preg_replace('/(\d{2})(\d{3})(\d{4})(\d{4})/', '$1 $2-$3-$4', $schoolProfile->whatsapp_1)); ?></p>
                                    </div>
                                    <div class="p-2 bg-green-500 rounded-full group-hover:scale-110 transition-transform">
                                        <svg class="size-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                        </svg>
                                    </div>
                                </a>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($schoolProfile?->whatsapp_2): ?>
                                <a href="<?php echo e($schoolProfile->whatsapp_2_url); ?>" target="_blank" class="flex items-center gap-4 p-4 bg-linear-to-r from-green-500/20 to-emerald-500/10 hover:from-green-500/30 hover:to-emerald-500/20 border border-green-400/30 hover:border-green-400/50 rounded-xl transition-all hover:scale-[1.02] group">
                                    <div class="w-12 h-12 rounded-full bg-linear-to-br from-emerald-400 to-teal-500 flex items-center justify-center text-white font-bold text-lg shadow-lg shadow-emerald-500/30">
                                        <?php echo e(substr($schoolProfile->whatsapp_2_name, 4, 1)); ?>

                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-semibold text-white truncate"><?php echo e($schoolProfile->whatsapp_2_name); ?></p>
                                        <p class="text-green-300 text-sm font-mono">+<?php echo e(preg_replace('/(\d{2})(\d{3})(\d{4})(\d{4})/', '$1 $2-$3-$4', $schoolProfile->whatsapp_2)); ?></p>
                                    </div>
                                    <div class="p-2 bg-emerald-500 rounded-full group-hover:scale-110 transition-transform">
                                        <svg class="size-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                        </svg>
                                    </div>
                                </a>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </div>

                        <!-- Social Media Section -->
                        <div class="p-6 rounded-2xl bg-linear-to-br from-pink-500/10 to-violet-500/5 border border-pink-400/20">
                            <h3 class="font-bold text-white text-lg mb-5 flex items-center gap-3">
                                <span class="p-2 bg-linear-to-br from-pink-500 to-violet-500 rounded-lg">
                                    <svg class="size-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" />
                                    </svg>
                                </span>
                                Ikuti Media Sosial Kami
                            </h3>
                            
                            <div class="grid grid-cols-2 gap-3">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($schoolProfile?->facebook): ?>
                                <a href="<?php echo e($schoolProfile->facebook_url); ?>" target="_blank" class="flex items-center gap-3 p-4 bg-linear-to-r from-blue-600/30 to-blue-500/20 hover:from-blue-600/50 hover:to-blue-500/30 border border-blue-400/30 hover:border-blue-400/50 rounded-xl transition-all group">
                                    <svg class="size-7 text-blue-400" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                    </svg>
                                    <span class="text-sm text-blue-200 font-medium group-hover:text-white transition-colors">Facebook</span>
                                </a>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($schoolProfile?->instagram): ?>
                                <a href="<?php echo e($schoolProfile->instagram_url); ?>" target="_blank" class="flex items-center gap-3 p-4 bg-linear-to-r from-pink-600/30 to-purple-500/20 hover:from-pink-600/50 hover:to-purple-500/30 border border-pink-400/30 hover:border-pink-400/50 rounded-xl transition-all group">
                                    <svg class="size-7 text-pink-400" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
                                    </svg>
                                    <span class="text-sm text-pink-200 font-medium group-hover:text-white transition-colors">Instagram</span>
                                </a>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($schoolProfile?->youtube): ?>
                                <a href="<?php echo e($schoolProfile->youtube); ?>" target="_blank" class="flex items-center gap-3 p-4 bg-linear-to-r from-red-600/30 to-rose-500/20 hover:from-red-600/50 hover:to-rose-500/30 border border-red-400/30 hover:border-red-400/50 rounded-xl transition-all group">
                                    <svg class="size-7 text-red-400" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                                    </svg>
                                    <span class="text-sm text-red-200 font-medium group-hover:text-white transition-colors">YouTube</span>
                                </a>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($schoolProfile?->tiktok): ?>
                                <a href="<?php echo e($schoolProfile->tiktok); ?>" target="_blank" class="flex items-center gap-3 p-4 bg-linear-to-r from-slate-600/30 to-zinc-500/20 hover:from-slate-600/50 hover:to-zinc-500/30 border border-slate-400/30 hover:border-slate-400/50 rounded-xl transition-all group">
                                    <svg class="size-7 text-slate-300" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/>
                                    </svg>
                                    <span class="text-sm text-slate-200 font-medium group-hover:text-white transition-colors">TikTok</span>
                                </a>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                            
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($schoolProfile?->instagram): ?>
                            <div class="mt-5 p-3 rounded-xl bg-linear-to-r from-pink-500/10 to-purple-500/10 border border-pink-400/20 text-center">
                                <p class="text-pink-200 text-sm">
                                    Follow: <span class="text-pink-300 font-bold">{{ $schoolProfile->instagram }}</span>
                                </p>
                            </div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Copyright -->
        <div class="py-5 bg-linear-to-r from-slate-950 via-blue-950 to-slate-950 border-t border-white/10">
            <div class="max-w-7xl mx-auto px-6 lg:px-12">
                <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($schoolProfile?->logo_url): ?>
                            <img src="<?php echo e($schoolProfile->logo_url); ?>" alt="<?php echo e($schoolProfile->name); ?>" class="h-8 w-auto" />
                        <?php else: ?>
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-linear-to-br from-blue-500 to-indigo-600 shadow-lg shadow-blue-500/30">
                                <svg class="size-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" />
                                </svg>
                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <span class="text-sm text-blue-200">
                            © <?php echo e(date('Y')); ?> <span class="font-semibold text-white"><?php echo e($schoolProfile?->name ?? config('app.name')); ?></span>
                        </span>
                    </div>
                    <div class="flex items-center gap-4 text-sm text-blue-300/70">
                        <span>All rights reserved</span>
                    </div>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>

<?php /**PATH C:\laragon\www\tata-usaha-sekolah\resources\views/welcome.blade.php ENDPATH**/ ?>