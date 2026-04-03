@props([
    'title' => null,
    'subtitle' => null,
    'noPadding' => false,
    'glass' => false,
    'hover' => true,
])

<div {{ $attributes->merge([
    'class' => implode(' ', [
        'rounded-2xl overflow-hidden animate-fade-in-up',
        $glass ? 'glass' : 'bg-white dark:bg-zinc-900/80',
        $hover ? 'card-hover' : '',
        'border border-zinc-200/40 dark:border-zinc-800/60',
        'shadow-sm dark:shadow-none',
    ])
]) }}>
    @if($title || isset($header))
        <div class="px-6 py-4 border-b border-zinc-100 dark:border-zinc-800/60">
            @if(isset($header))
                {{ $header }}
            @else
                <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100">{{ $title }}</h3>
                @if($subtitle)
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">{{ $subtitle }}</p>
                @endif
            @endif
        </div>
    @endif
    
    <div class="{{ $noPadding ? '' : 'p-6' }}">
        {{ $slot }}
    </div>
    
    @if(isset($footer))
        <div class="px-6 py-4 border-t border-zinc-100 dark:border-zinc-800/60 bg-zinc-50/50 dark:bg-zinc-800/30">
            {{ $footer }}
        </div>
    @endif
</div>
