@props([
    'padding' => 'default',
])

@php
    $paddingClass = match ($padding) {
        'none' => '',
        'sm' => 'p-4',
        'lg' => 'p-6',
        default => 'p-5',
    };
@endphp

<div {{ $attributes->class([
    'rounded-3xl border border-slate-200 bg-white shadow-sm',
    $paddingClass,
]) }}>
    {{ $slot }}
</div>