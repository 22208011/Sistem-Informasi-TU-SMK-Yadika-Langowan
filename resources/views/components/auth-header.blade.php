@props([
    'title',
    'description',
])

@php
    $schoolProfile = \App\Models\SchoolProfile::getProfile();
@endphp

<div class="flex w-full flex-col items-center gap-6 text-center">
    <a href="{{ route('home') }}" class="group flex flex-col items-center gap-3 font-medium transition-all duration-300" wire:navigate>
        @if($schoolProfile?->logo_url)
            <div class="relative">
                <div class="absolute inset-0 bg-linear-to-r from-blue-600 to-indigo-600 rounded-full blur-lg opacity-30 group-hover:opacity-50 transition-opacity"></div>
                <img src="{{ $schoolProfile->logo_url }}" alt="{{ $schoolProfile->name }}" class="relative h-20 w-auto transform transition-transform duration-300 group-hover:scale-105" />
            </div>
        @else
            <div class="relative">
                <div class="absolute inset-0 bg-linear-to-r from-blue-600 to-indigo-600 rounded-2xl blur-lg opacity-30 group-hover:opacity-50 transition-opacity"></div>
                <span class="relative flex h-20 w-20 items-center justify-center rounded-2xl bg-linear-to-br from-blue-600 to-indigo-600 shadow-xl transform transition-all duration-300 group-hover:scale-105 group-hover:shadow-2xl">
                    <flux:icon.academic-cap class="size-12 text-white animate-bounce-subtle" />
                </span>
            </div>
        @endif
        <span class="text-center text-lg font-bold bg-linear-to-r from-zinc-800 to-zinc-600 dark:from-zinc-100 dark:to-zinc-300 bg-clip-text text-transparent">
            {{ $schoolProfile?->name ?? config('app.name', 'Laravel') }}
        </span>
    </a>

    <div class="space-y-2">
        <flux:heading size="xl" class="text-2xl! font-bold text-zinc-800 dark:text-white">{{ $title }}</flux:heading>
        <flux:subheading class="text-zinc-500 dark:text-zinc-400">{{ $description }}</flux:subheading>
    </div>
</div>
