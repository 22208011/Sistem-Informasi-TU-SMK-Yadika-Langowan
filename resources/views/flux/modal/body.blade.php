@props([
    'class' => '',
])

<div {{ $attributes->class([
    'p-6',
    $class,
]) }}>
    {{ $slot }}
</div>
