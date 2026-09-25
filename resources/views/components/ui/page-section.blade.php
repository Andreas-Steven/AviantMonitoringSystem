@props([
    'title' => null,
    'subtitle' => null,
    'actions' => null,
])

<x-ui.card>
    @if ($title || $subtitle || $actions)
        <div class="flex flex-col gap-4 border-b border-slate-200 pb-4 md:flex-row md:items-start md:justify-between">
            <div class="space-y-1">
                @if ($title)
                    <h3 class="text-lg font-semibold tracking-tight text-slate-900">
                        {{ $title }}
                    </h3>
                @endif

                @if ($subtitle)
                    <p class="text-sm text-slate-500">
                        {{ $subtitle }}
                    </p>
                @endif
            </div>

            @if ($actions)
                <div class="shrink-0">
                    {{ $actions }}
                </div>
            @endif
        </div>
    @endif

    <div @class(['pt-5' => $title || $subtitle || $actions])>
        {{ $slot }}
    </div>
</x-ui.card>