@props([
    'title' => '',
    'subtitle' => '',
    'breadcrumbs' => [],
])

<div class="space-y-4">
    @if (!empty($breadcrumbs))
        <nav class="flex flex-wrap items-center gap-2 text-sm text-slate-500">
            @foreach ($breadcrumbs as $index => $crumb)
                <span>{{ $crumb['label'] }}</span>
                @if ($index < count($breadcrumbs) - 1)
                    <span>/</span>
                @endif
            @endforeach
        </nav>
    @endif

    <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-start">
        <div class="min-w-0">
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">
                {{ $title }}
            </h1>

            @if ($subtitle)
                <p class="mt-1 text-sm text-slate-500">
                    {{ $subtitle }}
                </p>
            @endif
        </div>

        @isset($actions)
            <div class="flex items-center gap-3 lg:justify-end lg:pt-1">
                {{ $actions }}
            </div>
        @endisset
    </div>
</div>