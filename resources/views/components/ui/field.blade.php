@props([
    'label' => null,
    'hint' => null,
    'error' => null,
])

<div class="space-y-2">
    @if ($label)
        <label class="block text-sm font-medium text-slate-700">
            {{ $label }}
        </label>
    @endif

    {{ $slot }}

    @if ($hint && !$error)
        <p class="text-xs text-slate-500">
            {{ $hint }}
        </p>
    @endif

    @if ($error)
        <p class="text-xs text-rose-600">
            {{ $error }}
        </p>
    @endif
</div>