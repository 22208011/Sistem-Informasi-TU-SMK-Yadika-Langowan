@props([])

@php
$classes = Flux::classes()
    ->add('px-6 py-4 border-t border-zinc-200 dark:border-white/10')
    ;
@endphp

<div {{ $attributes->class($classes) }} data-flux-card-footer>
    {{ $slot }}
</div>
