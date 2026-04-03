@props([
    'class' => '',
])

<div {{ $attributes->class([
    'p-6 pb-0',
    $class,
]) }}>
    {{ $slot }}
</div>
