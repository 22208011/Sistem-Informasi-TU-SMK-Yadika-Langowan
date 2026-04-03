@props([
    'class' => '',
])

<div {{ $attributes->class([
    'p-6 pt-0',
    $class,
]) }}>
    {{ $slot }}
</div>
