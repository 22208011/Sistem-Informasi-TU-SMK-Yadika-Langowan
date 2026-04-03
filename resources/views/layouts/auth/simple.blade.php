@php
    $schoolProfile = \App\Models\SchoolProfile::getProfile();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen antialiased bg-linear-to-br from-sky-50 via-cyan-50 to-emerald-50 dark:from-zinc-950 dark:via-slate-950 dark:to-zinc-900">
        <!-- Layered decorative background to keep auth screens feeling alive in both themes -->
        <div class="fixed inset-0 overflow-hidden pointer-events-none">
            <div class="absolute inset-0 opacity-50 dark:opacity-25 [background:radial-gradient(circle_at_1px_1px,rgba(15,23,42,.10)_1px,transparent_0)] bg-size-[24px_24px]"></div>
            <div class="absolute -top-44 -left-16 h-80 w-80 rounded-full bg-sky-400/20 blur-3xl dark:bg-sky-500/15"></div>
            <div class="absolute top-1/3 -right-24 h-96 w-96 rounded-full bg-cyan-400/20 blur-3xl dark:bg-cyan-500/15"></div>
            <div class="absolute -bottom-40 left-1/4 h-80 w-80 rounded-full bg-emerald-400/20 blur-3xl dark:bg-emerald-500/15"></div>
            <div class="absolute inset-x-0 top-0 h-48 bg-linear-to-b from-white/80 to-transparent dark:from-zinc-900/40"></div>
        </div>

        <div class="relative flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10 overflow-y-auto">
            <div class="w-full max-w-md">
                <!-- Card -->
                <div class="rounded-3xl bg-white/90 dark:bg-zinc-900/90 backdrop-blur-xl p-8 shadow-2xl shadow-cyan-900/10 dark:shadow-black/50 border border-white/70 dark:border-zinc-700/60">
                    <div class="flex flex-col gap-8">
                        {{ $slot }}
                    </div>
                </div>
                
                <!-- Footer -->
                <div class="mt-6 text-center">
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        © {{ date('Y') }} {{ $schoolProfile?->name ?? config('app.name') }}. All rights reserved.
                    </p>
                </div>
            </div>
        </div>
        
        {{-- Flux Scripts - using direct file --}}
        <?php app('livewire')->forceAssetInjection(); ?>
        <script src="{{ asset('vendor/flux/flux.js') }}" data-navigate-once></script>
        @livewireScripts
    </body>
</html>

