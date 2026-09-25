@props([
    'day' => [],
    'filters' => [],
])

@php
    $toneMap = [
        'WORKDAY' => 'default',
        'HOLIDAY_NATIONAL' => 'danger',
        'HOLIDAY_COMPANY' => 'warning',
        'WEEKOFF' => 'neutral',
        'SPECIAL' => 'info',
        'HALF_DAY' => 'info',
        'UNGENERATED' => 'neutral',
    ];

    $bgMap = [
        'WORKDAY' => 'bg-white',
        'HOLIDAY_NATIONAL' => 'bg-rose-50',
        'HOLIDAY_COMPANY' => 'bg-amber-50',
        'WEEKOFF' => 'bg-slate-50',
        'SPECIAL' => 'bg-sky-50',
        'HALF_DAY' => 'bg-sky-50',
        'UNGENERATED' => 'bg-slate-50',
    ];

    $tone = $toneMap[$day['day_type_code']] ?? 'default';
    $bg = $bgMap[$day['day_type_code']] ?? 'bg-white';
    $selectedClass = !empty($day['is_selected'])
        ? 'ring-2 ring-indigo-500 border-indigo-500 shadow-md bg-indigo-50/30'
        : 'border-slate-200 hover:border-slate-300 hover:shadow-sm';

    $url = route('scheduling.branch-calendars.index', array_merge($filters, [
        'selected_date' => $day['work_date'],
        'edit_day' => 0,
    ]));
@endphp

<a
    href="{{ $url }}"
    class="block min-h-[116px] rounded-2xl border px-3 py-3 shadow-sm transition {{ $bg }} {{ $selectedClass }}"
>
    <div class="flex items-start justify-between gap-2">
        <div>
            <div class="text-2xl font-semibold leading-none text-slate-900">
                {{ $day['day_number'] }}
            </div>
            <div class="mt-2 text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                {{ $day['day_name_short'] }}
            </div>
        </div>

        @if (!empty($day['has_override']))
            <span class="inline-flex rounded-full bg-indigo-50 px-2 py-0.5 text-[10px] font-semibold text-indigo-700 ring-1 ring-indigo-200">
                Edited
            </span>
        @endif
    </div>

    <div class="mt-3">
        <x-ui.status-badge
            :label="$day['day_type_label'] ?? str_replace('_', ' ', $day['day_type_code'])"
            :tone="$tone"
        />
    </div>

    @if (!empty($day['notes']))
        <div class="mt-2 line-clamp-2 text-xs leading-5 text-slate-500">
            {{ $day['notes'] }}
        </div>
    @endif
</a>