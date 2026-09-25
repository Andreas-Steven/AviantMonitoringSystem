@props([
    'title' => 'No data',
    'description' => '',
])

<div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center dark:border-slate-700 dark:bg-slate-800/40">
    <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">
        {{ $title }}
    </h3>

    @if ($description)
        <p class="mx-auto mt-2 max-w-md text-sm text-slate-500 dark:text-slate-400">
            {{ $description }}
        </p>
    @endif

    @if (trim($slot))
        <div class="mt-4">
            {{ $slot }}
        </div>
    @endif
</div>