@props([
    'title' => null,
    'subtitle' => null,
    'bodyClass' => '',
])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900']) }}>
    @if ($title || $subtitle)
        <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800">
            @if ($title)
                <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ $title }}</h3>
            @endif
            @if ($subtitle)
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $subtitle }}</p>
            @endif
        </div>
    @endif

    <div class="p-5 {{ $bodyClass }}">
        {{ $slot }}
    </div>
</div>