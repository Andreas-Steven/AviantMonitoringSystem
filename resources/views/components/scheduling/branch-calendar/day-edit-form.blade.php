@props([
    'day' => null,
    'filters' => [],
    'dayTypeOptions' => [],
    'canManage' => false,
])

@if (!$day)
    <x-ui.empty-state
        title="No date selected"
        description="Select a date from the calendar to edit its details."
    />
@elseif (!$canManage)
    <x-ui.empty-state
        title="Permission required"
        description="You do not have permission to edit this calendar day."
    />
@else
    @php
        $cancelUrl = route('scheduling.branch-calendars.index', array_merge($filters, [
            'selected_date' => $day['work_date'],
            'edit_day' => 0,
        ]));
    @endphp

    <form
        method="POST"
        action="{{ route('scheduling.branch-calendars.update-day', $day['id']) }}"
        class="space-y-5"
    >
        @csrf
        @method('PATCH')

        <input type="hidden" name="period_code" value="{{ $filters['period_code'] ?? '' }}">
        <input type="hidden" name="view_mode" value="{{ $filters['view_mode'] ?? 'grid' }}">

        <div class="space-y-2">
            <div class="text-sm font-medium text-slate-500">
                Edit Day
            </div>

            <div class="text-xl font-semibold text-slate-900">
                {{ $day['work_date'] }}
            </div>

            <div class="text-sm text-slate-500">
                {{ $day['day_name'] }}
            </div>
        </div>

        <div class="space-y-4">
            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">
                    Day Type
                </label>
                <select
                    name="day_type_code"
                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                >
                    @foreach ($dayTypeOptions as $option)
                        <option
                            value="{{ $option['value'] }}"
                            @selected(old('day_type_code', $day['day_type_code']) === $option['value'])
                        >
                            {{ $option['label'] }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">
                    Is Workday
                </label>

                <div class="flex items-center gap-6 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input
                            type="radio"
                            name="is_workday"
                            value="1"
                            class="border-slate-300 text-indigo-600 focus:ring-indigo-500"
                            @checked((string) old('is_workday', $day['is_workday'] ? '1' : '0') === '1')
                        >
                        <span>Yes</span>
                    </label>

                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input
                            type="radio"
                            name="is_workday"
                            value="0"
                            class="border-slate-300 text-indigo-600 focus:ring-indigo-500"
                            @checked((string) old('is_workday', $day['is_workday'] ? '1' : '0') === '0')
                        >
                        <span>No</span>
                    </label>
                </div>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">
                    Notes
                </label>
                <textarea
                    name="notes"
                    rows="4"
                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                    placeholder="Optional notes for this date..."
                >{{ old('notes', $day['notes']) }}</textarea>

                <p class="mt-2 text-xs text-slate-500">
                    This affects scheduling and attendance interpretation for the selected date.
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3 pt-2">
            <a
                href="{{ $cancelUrl }}"
                class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
            >
                Cancel
            </a>

            <button
                type="submit"
                class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
            >
                Save Changes
            </button>
        </div>
    </form>
@endif