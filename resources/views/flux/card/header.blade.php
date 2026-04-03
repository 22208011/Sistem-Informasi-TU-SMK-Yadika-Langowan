@props([])

@php
$classes = Flux::classes()
    ->add('px-6 py-4 border-b border-zinc-200 dark:border-white/10')
    ;
@endphp

<div {{ $attributes->class($classes) }} data-flux-card-header>
    {{ $slot }}
</div>
