@props([
    'rows' => [],
    'filters' => [],
])

<x-ui.section-card>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead>
                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Day</th>
                    <th class="px-4 py-3">Day Type</th>
                    <th class="px-4 py-3">Workday</th>
                    <th class="px-4 py-3">Notes</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse ($rows as $row)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 text-sm font-medium text-slate-900">
                            {{ $row['work_date'] }}
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600">
                            {{ $row['day_name'] }}
                        </td>
                        <td class="px-4 py-3 text-sm">
                            @php
                                $tone = match ($row['day_type_code']) {
                                    'HOLIDAY_NATIONAL' => 'danger',
                                    'HOLIDAY_COMPANY' => 'warning',
                                    'WEEKOFF' => 'neutral',
                                    'SPECIAL', 'HALF_DAY' => 'info',
                                    default => 'default',
                                };
                            @endphp

                            <x-ui.status-badge
                                :label="$row['day_type_label']"
                                :tone="$tone"
                            />
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600">
                            {{ $row['is_workday'] ? 'Yes' : 'No' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600">
                            {{ $row['notes'] ?: '—' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600">
                            {{ $row['is_override'] ? 'Manual Override' : 'Generated' }}
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <a
                                href="{{ route('scheduling.branch-calendars.index', array_merge($filters, ['selected_date' => $row['work_date'], 'edit_day' => 0])) }}"
                                class="font-medium text-indigo-600 hover:text-indigo-500"
                            >
                                View
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-sm text-slate-500">
                            No rows found for the current filter.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-ui.section-card>