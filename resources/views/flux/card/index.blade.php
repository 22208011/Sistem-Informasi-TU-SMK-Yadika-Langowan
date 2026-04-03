@props([
    'size' => null,
])

@php
$classes = Flux::classes()
    ->add('[:where(&)]:bg-white dark:[:where(&)]:bg-white/10')
    ->add('border border-zinc-200 dark:border-white/10')
    ->add(match ($size) {
        default => '[:where(&)]:rounded-xl',
        'sm' => '[:where(&)]:rounded-lg',
    })
    ;
@endphp

<div {{ $attributes->class($classes) }} data-flux-card>
    @if(isset($header))
        {{ $header }}
    @endif

    {{ $slot }}

    @if(isset($footer))
        {{ $footer }}
    @endif
</div>
