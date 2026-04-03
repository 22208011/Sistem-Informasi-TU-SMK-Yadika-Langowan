@php
    $schoolProfile = \App\Models\SchoolProfile::getProfile();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-neutral-100 antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
        <div class="bg-muted flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div class="flex w-full max-w-md flex-col gap-6">
                <div class="flex flex-col gap-6">
                    <div class="rounded-xl border bg-white dark:bg-stone-950 dark:border-stone-800 text-stone-800 shadow-xs">
                        <div class="px-10 py-8">{{ $slot }}</div>
                    </div>
                </div>
            </div>
        </div>
        {{-- Flux Scripts - using direct file --}}
        <?php app('livewire')->forceAssetInjection(); ?>
        <script src="{{ asset('vendor/flux/flux.js') }}" data-navigate-once></script>
        <script>
            function startAlpineWhenReady() {
                if (window.Alpine && !window.alpineStarted) {
                    window.alpineStarted = true;
                    window.Alpine.start();
                } else if (!window.Alpine) {
                    setTimeout(startAlpineWhenReady, 10);
                }
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', startAlpineWhenReady);
            } else {
                startAlpineWhenReady();
            }
        </script>
    </body>
</html>
