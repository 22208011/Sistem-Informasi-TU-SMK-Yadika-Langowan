@props([])

@php
$classes = Flux::classes()
    ->add('p-6')
    ;
@endphp

<div {{ $attributes->class($classes) }} data-flux-card-body>
    {{ $slot }}
</div>
