@props([
    'label' => '',
    'value' => 0,
    'hint' => '',
    'valueExpression' => null,
])

<div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
        {{ $label }}
    </div>

    <div class="mt-2 text-4xl font-semibold tracking-tight text-slate-900" @if ($valueExpression) x-text="{{ $valueExpression }}" @endif>
        {{ $value }}
    </div>

    @if ($hint)
        <div class="mt-2 text-sm text-slate-500">
            {{ $hint }}
        </div>
    @endif
</div>