@props([
    'title',
    'value',
    'icon' => null,
    'color' => 'blue', // blue, green, purple, amber, red, indigo
    'trend' => null,
    'trendLabel' => null,
    'suffix' => null,
])

@php
    $colorClasses = [
        'blue' => [
            'stat' => 'stat-card blue',
            'icon' => 'icon-container blue',
            'trend-up' => 'text-green-500',
            'trend-down' => 'text-red-500',
        ],
        'green' => [
            'stat' => 'stat-card green',
            'icon' => 'icon-container green',
            'trend-up' => 'text-green-500',
            'trend-down' => 'text-red-500',
        ],
        'purple' => [
            'stat' => 'stat-card purple',
            'icon' => 'icon-container purple',
            'trend-up' => 'text-green-500',
            'trend-down' => 'text-red-500',
        ],
        'amber' => [
            'stat' => 'stat-card amber',
            'icon' => 'icon-container amber',
            'trend-up' => 'text-green-500',
            'trend-down' => 'text-red-500',
        ],
        'red' => [
            'stat' => 'stat-card red',
            'icon' => 'icon-container red',
            'trend-up' => 'text-green-500',
            'trend-down' => 'text-red-500',
        ],
        'indigo' => [
            'stat' => 'stat-card indigo',
            'icon' => 'icon-container indigo',
            'trend-up' => 'text-green-500',
            'trend-down' => 'text-red-500',
        ],
    ];
    
    $classes = $colorClasses[$color] ?? $colorClasses['blue'];
@endphp

<div {{ $attributes->merge(['class' => $classes['stat'] . ' p-5 shadow-sm hover:shadow-lg transition-all duration-300']) }}>
    <div class="flex items-start justify-between">
        <div class="flex-1">
            <p class="text-xs font-semibold text-zinc-400 dark:text-zinc-500 mb-2 uppercase tracking-wider">{{ $title }}</p>
            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-bold text-zinc-900 dark:text-zinc-100 animate-count-up">{{ $value }}</span>
                @if($suffix)
                    <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ $suffix }}</span>
                @endif
            </div>
            @if($trend !== null && $trendLabel)
                <div class="flex items-center gap-1 mt-2">
                    @if($trend > 0)
                        <svg class="size-4 {{ $classes['trend-up'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12" />
                        </svg>
                        <span class="text-sm {{ $classes['trend-up'] }}">+{{ $trend }}%</span>
                    @else
                        <svg class="size-4 {{ $classes['trend-down'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6" />
                        </svg>
                        <span class="text-sm {{ $classes['trend-down'] }}">{{ $trend }}%</span>
                    @endif
                    <span class="text-xs text-zinc-400">{{ $trendLabel }}</span>
                </div>
            @endif
        </div>
        @if($icon)
            <div class="{{ $classes['icon'] }} size-12">
                {{ $icon }}
            </div>
        @endif
    </div>
    
    @if(isset($slot) && !$slot->isEmpty())
        <div class="mt-4 pt-4 border-t border-zinc-100 dark:border-zinc-800">
            {{ $slot }}
        </div>
    @endif
</div>
