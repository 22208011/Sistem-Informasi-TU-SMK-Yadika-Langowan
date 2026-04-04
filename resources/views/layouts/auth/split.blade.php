@php
    $schoolProfile = \App\Models\SchoolProfile::getProfile();
    $brandLogo = $schoolProfile?->logo_url ?? asset('images/logo-yadika.png');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
        <div class="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0">
            <div class="bg-muted relative hidden h-full flex-col p-10 text-white lg:flex dark:border-e dark:border-neutral-800">
                <div class="absolute inset-0 bg-neutral-900"></div>
                <a href="{{ route('home') }}" class="relative z-20 flex items-center gap-3 text-lg font-medium" wire:navigate>
                    <img src="{{ $brandLogo }}" alt="{{ $schoolProfile?->name ?? 'Logo SMK Yadika Langowan' }}" class="h-10 w-auto object-contain" />
                    {{ $schoolProfile?->name ?? config('app.name', 'Laravel') }}
                </a>

                @php
                    [$message, $author] = str(Illuminate\Foundation\Inspiring::quotes()->random())->explode('-');
                @endphp

                <div class="relative z-20 mt-auto">
                    <blockquote class="space-y-2">
                        <flux:heading size="lg">&ldquo;{{ trim($message) }}&rdquo;</flux:heading>
                        <footer><flux:heading>{{ trim($author) }}</flux:heading></footer>
                    </blockquote>
                </div>
            </div>
            <div class="w-full lg:p-8">
                <div class="mx-auto flex w-full flex-col justify-center space-y-6 sm:max-w-sm">
                    {{ $slot }}
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
