@props([
    'label' => '',
    'value' => 0,
    'hint' => '',
    'valueExpression' => null,
])

<div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
        {{ $label }}
    </div>

    <div class="mt-2 text-4xl font-semibold tracking-tight text-slate-900 dark:text-slate-100" @if ($valueExpression) x-text="{{ $valueExpression }}" @endif>
        {{ $value }}
    </div>

    @if ($hint)
        <div class="mt-2 text-sm text-slate-500 dark:text-slate-400">
            {{ $hint }}
        </div>
    @endif
</div>