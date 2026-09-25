@props([
    'calendarMatrix' => [],
    'filters' => [],
])

<x-ui.section-card>
    <div
        class="gap-2"
        style="display:grid; grid-template-columns:repeat(7, minmax(0, 1fr));"
    >
        @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $dayHeader)
            <div class="px-2 py-2 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">
                {{ $dayHeader }}
            </div>
        @endforeach
    </div>

    <div class="mt-2 space-y-2">
        @foreach ($calendarMatrix as $week)
            <div
                class="gap-2"
                style="display:grid; grid-template-columns:repeat(7, minmax(0, 1fr));"
            >
                @foreach ($week as $day)
                    @if ($day)
                        <x-scheduling.branch-calendar.day-cell
                            :day="$day"
                            :filters="$filters"
                        />
                    @else
                        <div class="min-h-[132px] rounded-2xl border border-dashed border-slate-200 bg-slate-50/60"></div>
                    @endif
                @endforeach
            </div>
        @endforeach
    </div>
</x-ui.section-card>