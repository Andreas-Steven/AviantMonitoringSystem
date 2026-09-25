@props([
    'title' => null,
])

<div class="w-full max-w-full rounded-2xl border border-slate-200 bg-white shadow-sm lg:sticky lg:top-6">
    @if ($title)
        <div class="border-b border-slate-200 px-5 py-4">
            <h3 class="text-lg font-semibold tracking-tight text-slate-900">
                {{ $title }}
            </h3>
        </div>
    @endif

    <div class="p-5">
        {{ $slot }}
    </div>
</div>