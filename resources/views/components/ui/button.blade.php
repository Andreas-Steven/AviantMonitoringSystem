@props([
    'variant' => 'secondary',
    'size' => 'md',
    'type' => 'button',
])

@php
    $baseClass = 'inline-flex items-center justify-center rounded-xl font-medium transition focus:outline-none focus:ring-2 focus:ring-indigo-200 disabled:opacity-60 disabled:cursor-not-allowed';

    $sizeClass = match ($size) {
        'sm' => 'px-3 py-2 text-sm',
        'lg' => 'px-5 py-3 text-sm',
        default => 'px-4 py-2.5 text-sm',
    };

    $variantClass = match ($variant) {
        'primary' => 'bg-indigo-600 text-white shadow-sm hover:bg-indigo-500',
        'danger' => 'bg-rose-600 text-white shadow-sm hover:bg-rose-500',
        'ghost' => 'bg-transparent text-slate-700 hover:bg-slate-100',
        default => 'border border-slate-300 bg-white text-slate-700 shadow-sm hover:bg-slate-50',
    };
@endphp

<button
    type="{{ $type }}"
    {{ $attributes->class([$baseClass, $sizeClass, $variantClass]) }}
>
    {{ $slot }}
</button>