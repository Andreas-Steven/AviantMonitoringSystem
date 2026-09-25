@props([
    'day' => null,
    'filters' => [],
    'canManage' => false,
])

@if (!$day)
    <x-ui.empty-state
        title="No date selected"
        description="Select a date from the calendar to inspect its details."
    />
@else
    <div class="space-y-5">
        <div class="space-y-2">
            <div class="text-sm font-medium text-slate-500">
                {{ $day['day_name'] }}
            </div>

            <div class="text-2xl font-semibold tracking-tight text-slate-900">
                {{ $day['work_date'] }}
            </div>

            @php
                $tone = match ($day['day_type_code']) {
                    'HOLIDAY_NATIONAL' => 'danger',
                    'HOLIDAY_COMPANY' => 'warning',
                    'WEEKOFF' => 'neutral',
                    'SPECIAL', 'HALF_DAY' => 'info',
                    'UNGENERATED' => 'neutral',
                    default => 'default',
                };
            @endphp

            <x-ui.status-badge
                :label="$day['day_type_label'] ?? str_replace('_', ' ', $day['day_type_code'])"
                :tone="$tone"
            />
        </div>

        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <div class="space-y-3 text-sm">
                <div class="flex items-start justify-between gap-4">
                    <span class="text-slate-500">Work Date</span>
                    <span class="text-right font-medium text-slate-800">{{ $day['work_date'] }}</span>
                </div>

                <div class="flex items-start justify-between gap-4">
                    <span class="text-slate-500">Day Type</span>
                    <span class="text-right font-medium text-slate-800">{{ $day['day_type_label'] }}</span>
                </div>

                <div class="flex items-start justify-between gap-4">
                    <span class="text-slate-500">Is Workday</span>
                    <span class="text-right font-medium text-slate-800">{{ $day['is_workday'] ? 'Yes' : 'No' }}</span>
                </div>

                <div class="flex items-start justify-between gap-4">
                    <span class="text-slate-500">Status</span>
                    <span class="text-right font-medium text-slate-800">
                        {{ $day['is_override'] ? 'Manual Override' : 'Generated' }}
                    </span>
                </div>

                <div class="flex items-start justify-between gap-4">
                    <span class="text-slate-500">Updated At</span>
                    <span class="text-right font-medium text-slate-800">{{ $day['updated_at'] ?: '—' }}</span>
                </div>
            </div>
        </div>

        <div class="space-y-2">
            <div class="text-sm font-semibold text-slate-700">Notes</div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 text-sm leading-6 text-slate-600">
                {{ $day['notes'] ?: 'No notes for this date.' }}
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3 pt-1">
            @if ($canManage && !empty($day['id']))
                <a
                    href="{{ route('scheduling.branch-calendars.index', array_merge($filters, [
                        'selected_date' => $day['work_date'],
                        'edit_day' => 1,
                    ])) }}"
                    class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
                >
                    Edit Day
                </a>
            @endif

            <a
                href="{{ route('scheduling.branch-calendars.index', array_merge($filters, [
                    'selected_date' => $day['work_date'],
                    'edit_day' => 0,
                ])) }}"
                class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
            >
                Refresh Detail
            </a>
        </div>
    </div>
@endif