@props(['type' => 'card', 'count' => 1])

@php
    $types = [
        'card' => 'h-32 rounded-xl',
        'table-row' => 'h-12 rounded-lg',
        'text' => 'h-4 rounded',
        'avatar' => 'h-10 w-10 rounded-full',
        'button' => 'h-10 w-24 rounded-lg',
        'stat' => 'h-24 rounded-xl',
    ];
    $class = $types[$type] ?? $types['card'];
@endphp

@for ($i = 0; $i < $count; $i++)
    <div {{ $attributes->merge(['class' => "animate-pulse bg-zinc-200 dark:bg-zinc-700 $class"]) }}></div>
@endfor
