@props([
    'label' => '',
    'tone' => 'default',
])

@php
    $classes = match ($tone) {
        'success' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'danger' => 'bg-rose-50 text-rose-700 ring-rose-200',
        'warning' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'info' => 'bg-sky-50 text-sky-700 ring-sky-200',
        'neutral' => 'bg-slate-100 text-slate-700 ring-slate-200',
        default => 'bg-slate-100 text-slate-700 ring-slate-200',
    };
@endphp

<span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $classes }}">
    {{ $label }}
</span>