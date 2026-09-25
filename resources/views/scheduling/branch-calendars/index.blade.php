@extends('layouts.app')

@section('content')
<div
    x-data="branchCalendarPage({
        initialMatrix: @js($calendarMatrix),
        dayTypeOptions: @js($dayTypeOptions),
        initialSelectedDate: @js($filters['selected_date'] ?? null),
        initialViewMode: @js($filters['view_mode'] ?? 'grid'),
        initialShowNotesOnly: @js($filters['show_notes_only'] ?? false),
        initialShowNonWorkdayOnly: @js($filters['show_non_workday_only'] ?? false),
        updateUrlTemplate: @js(route('scheduling.branch-calendars.update-day', ['branchCalendar' => '__ID__'])),
        csrfToken: @js(csrf_token()),
        canManage: @js($canManage),
        hasCalendarData: @js($hasCalendarData),
        sessionSuccess: @js(session('success')),
    })"
    class="space-y-6"
>
    <x-ui.page-header
        title="Branch Calendar"
        subtitle="Manage workday, holiday, and special calendar settings per branch."
        :breadcrumbs="[
            ['label' => 'Scheduling'],
            ['label' => 'Branch Calendar'],
        ]"
    >
        <x-slot:actions>
            <div class="flex flex-wrap items-center gap-3">
                <a
                    href="{{ route('scheduling.branch-calendars.index', request()->query()) }}"
                    class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
                >
                    Refresh
                </a>

                @if(auth()->user()?->hasPermission('holiday.manage'))
                    <a
                        href="{{ route('scheduling.holiday-events.create') }}"
                        class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
                    >
                        Add Holiday
                    </a>

                    @if(Route::has('scheduling.holiday-events.import.create'))
                        <a
                            href="{{ route('scheduling.holiday-events.import.create') }}"
                            class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-md transition hover:bg-indigo-500 hover:shadow-lg"
                        >
                            Import Holiday
                        </a>
                    @endif
                @endif
            </div>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <div class="font-semibold">Please check the form input.</div>
            <ul class="mt-2 list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <template x-if="flashMessage">
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700" x-text="flashMessage"></div>
    </template>

    <x-ui.filter-bar>
        <form
            method="GET"
            action="{{ route('scheduling.branch-calendars.index') }}"
            class="space-y-5"
            x-ref="calendarFilterForm"
        >
            <div class="grid gap-5 lg:grid-cols-3">
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Branch</label>
                    <select
                        name="branch_id"
                        onchange="this.form.submit()"
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                    >
                        @foreach ($branchOptions as $option)
                            <option value="{{ $option['value'] }}" @selected((string) $option['value'] === (string) ($filters['branch_id'] ?? ''))>
                                {{ $option['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Payroll Period</label>
                    <select
                        name="period_code"
                        onchange="this.form.submit()"
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                    >
                        @foreach ($periodOptions as $option)
                            <option value="{{ $option['value'] }}" @selected((string) $option['value'] === (string) ($filters['period_code'] ?? ''))>
                                {{ $option['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">View Mode</label>
                    <div class="inline-flex rounded-xl border border-slate-300 bg-slate-50 p-1">
                        <label class="cursor-pointer">
                            <input
                                type="radio"
                                name="view_mode"
                                value="grid"
                                class="sr-only"
                                x-model="viewMode"
                                @change="$refs.calendarFilterForm.submit()"
                            >
                            <span :class="viewMode === 'grid' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'" class="inline-flex rounded-lg px-4 py-2 text-sm font-medium">
                                Grid
                            </span>
                        </label>

                        <label class="cursor-pointer">
                            <input
                                type="radio"
                                name="view_mode"
                                value="list"
                                class="sr-only"
                                x-model="viewMode"
                                @change="$refs.calendarFilterForm.submit()"
                            >
                            <span :class="viewMode === 'list' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'" class="inline-flex rounded-lg px-4 py-2 text-sm font-medium">
                                List
                            </span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-4 border-t border-slate-200 pt-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex flex-wrap items-center gap-4">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input
                            type="checkbox"
                            name="show_notes_only"
                            value="1"
                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                            x-model="showNotesOnly"
                        >
                        <span>Show Notes Only</span>
                    </label>

                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input
                            type="checkbox"
                            name="show_non_workday_only"
                            value="1"
                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                            x-model="showNonWorkdayOnly"
                        >
                        <span>Show Non-Workday Only</span>
                    </label>
                </div>

                <div class="flex items-center gap-3">
                    <a
                        href="{{ route('scheduling.branch-calendars.index') }}"
                        class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                    >
                        Reset
                    </a>

                    
                </div>
            </div>
        </form>
    </x-ui.filter-bar>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <div class="rounded-2xl border border-slate-200/80 bg-white/70 px-5 py-4 shadow-sm backdrop-blur-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Days</div>
            <div class="mt-2 text-4xl font-semibold tracking-tight text-slate-900">{{ $summary['total_days'] }}</div>
            <div class="mt-2 text-sm text-slate-500">In selected period</div>
        </div>

        <div class="rounded-2xl border border-slate-200/80 bg-white/70 px-5 py-4 shadow-sm backdrop-blur-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Workdays</div>
            <div class="mt-2 text-4xl font-semibold tracking-tight text-slate-900">{{ $summary['workdays'] }}</div>
            <div class="mt-2 text-sm text-slate-500">Scheduled working dates</div>
        </div>

        <div class="rounded-2xl border border-slate-200/80 bg-white/70 px-5 py-4 shadow-sm backdrop-blur-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Holidays</div>
            <div class="mt-2 text-4xl font-semibold tracking-tight text-slate-900">{{ $summary['holidays'] }}</div>
            <div class="mt-2 text-sm text-slate-500">National / company holidays</div>
        </div>

        <div class="rounded-2xl border border-slate-200/80 bg-white/70 px-5 py-4 shadow-sm backdrop-blur-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Weekoff</div>
            <div class="mt-2 text-4xl font-semibold tracking-tight text-slate-900">{{ $summary['weekoff'] }}</div>
            <div class="mt-2 text-sm text-slate-500">Weekly off dates</div>
        </div>

        <div class="rounded-2xl border border-slate-200/80 bg-white/70 px-5 py-4 shadow-sm backdrop-blur-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Overrides</div>
            <div class="mt-2 text-4xl font-semibold tracking-tight text-slate-900">{{ $summary['overrides'] }}</div>
            <div class="mt-2 text-sm text-slate-500">Manual calendar edits</div>
        </div>
    </section>

    @if ($activeBranch && $activePeriod)
        <div class="grid items-start gap-6 xl:gap-8" style="grid-template-columns: minmax(0, 1fr) 380px;">
            <div class="min-w-0 space-y-5">
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="space-y-1">
                        <h2 class="text-[2rem] font-semibold tracking-tight text-slate-900">
                            {{ $activePeriod['date_from'] }} — {{ $activePeriod['date_to'] }}
                        </h2>
                        <p class="text-base text-slate-500">
                            Branch:
                            <span class="font-medium text-slate-700">{{ $activeBranch['name'] }}</span>
                            <span class="mx-2 text-slate-300">•</span>
                            Period:
                            <span class="font-medium text-slate-700">{{ $activePeriod['code'] }}</span>
                        </p>
                    </div>
                </div>

                <x-scheduling.branch-calendar.generate-panel
                    :activeBranch="$activeBranch"
                    :activePeriod="$activePeriod"
                    :filters="$filters"
                    :canManage="$canManage"
                    :hasCalendarData="$hasCalendarData"
                />

                <x-scheduling.branch-calendar.legend />

                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    @if (!$hasCalendarData)
                        <x-ui.empty-state
                            title="Calendar belum tersedia"
                            description="Belum ada calendar untuk branch dan payroll period ini. Gunakan tombol Generate Calendar pada panel maintenance di atas untuk membuat periode ini."
                        />
                    @else
                        <div x-show="viewMode === 'grid'">
                            <div class="gap-2" style="display:grid; grid-template-columns:repeat(7, minmax(0, 1fr));">
                                <template x-for="header in ['Mon','Tue','Wed','Thu','Fri','Sat','Sun']" :key="header">
                                    <div class="px-2 py-2 text-center text-xs font-semibold uppercase tracking-wide text-slate-500" x-text="header"></div>
                                </template>
                            </div>

                            <div class="mt-2 space-y-2">
                                <template x-for="(week, weekIndex) in initialMatrix" :key="'week-'+weekIndex">
                                    <div class="gap-2" style="display:grid; grid-template-columns:repeat(7, minmax(0, 1fr));">
                                        <template x-for="(day, dayIndex) in week" :key="'day-'+weekIndex+'-'+dayIndex">
                                            <div>
                                                <template x-if="!day">
                                                    <div class="min-h-[116px] rounded-2xl border border-dashed border-slate-200 bg-slate-50/60"></div>
                                                </template>

                                                <template x-if="day && matchesFilter(day)">
                                                    <button
                                                        type="button"
                                                        @click="setSelectedDate(day.work_date)"
                                                        class="block min-h-[116px] w-full rounded-2xl border px-3 py-3 text-left shadow-sm transition hover:shadow-md"
                                                        :class="dayCardClass(day)"
                                                    >
                                                        <div class="flex items-start justify-between gap-2">
                                                            <div>
                                                                <div class="text-2xl font-semibold leading-none text-slate-900" x-text="day.day_number"></div>
                                                                <div class="mt-2 text-[11px] font-semibold uppercase tracking-wide text-slate-500" x-text="day.day_name_short"></div>
                                                            </div>

                                                            <template x-if="day.is_holiday">
                                                                <div class="mt-1 h-1.5 w-1.5 rounded-full bg-rose-500"></div>
                                                            </template>

                                                            <template x-if="day.has_override">
                                                                <span class="inline-flex rounded-full bg-indigo-50 px-2 py-0.5 text-[10px] font-semibold text-indigo-700 ring-1 ring-indigo-200">
                                                                    Edited
                                                                </span>
                                                            </template>
                                                        </div>

                                                        <div class="mt-3">
                                                            <span
                                                                class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset"
                                                                :class="badgeClass(day.day_type_code)"
                                                                x-text="day.day_type_label"
                                                            ></span>
                                                        </div>

                                                        <template x-if="day.notes">
                                                            <div class="mt-2 line-clamp-2 text-xs leading-5 text-slate-500" x-text="day.notes"></div>
                                                        </template>
                                                    </button>
                                                </template>

                                                <template x-if="day && !matchesFilter(day)">
                                                    <div class="min-h-[116px] rounded-2xl border border-dashed border-slate-200 bg-slate-50/40 px-3 py-3">
                                                        <div class="text-sm font-medium text-slate-300" x-text="day.day_number"></div>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div x-show="viewMode === 'list'">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-slate-200">
                                    <thead>
                                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            <th class="px-4 py-3">Date</th>
                                            <th class="px-4 py-3">Day</th>
                                            <th class="px-4 py-3">Day Type</th>
                                            <th class="px-4 py-3">Workday</th>
                                            <th class="px-4 py-3">Notes</th>
                                            <th class="px-4 py-3">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        <template x-for="row in filteredRows" :key="'list-'+row.work_date">
                                            <tr class="hover:bg-slate-50">
                                                <td class="px-4 py-3 text-sm font-medium text-slate-900" x-text="row.work_date"></td>
                                                <td class="px-4 py-3 text-sm text-slate-600" x-text="row.day_name"></td>
                                                <td class="px-4 py-3 text-sm">
                                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset" :class="badgeClass(row.day_type_code)" x-text="row.day_type_label"></span>
                                                </td>
                                                <td class="px-4 py-3 text-sm text-slate-600" x-text="row.is_workday ? 'Yes' : 'No'"></td>
                                                <td class="px-4 py-3 text-sm text-slate-600" x-text="row.notes || '—'"></td>
                                                <td class="px-4 py-3 text-sm">
                                                    <button type="button" @click="setSelectedDate(row.work_date)" class="font-medium text-indigo-600 hover:text-indigo-500">
                                                        View
                                                    </button>
                                                </td>
                                            </tr>
                                        </template>

                                        <template x-if="filteredRows.length === 0">
                                            <tr>
                                                <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">
                                                    No rows found for the current filter.
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <aside class="min-w-0">
                 <div class="sticky top-24 space-y-5">
                    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 px-5 py-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-lg font-semibold tracking-tight text-slate-900">
                                        Holiday Events
                                    </h3>
                                    <p class="mt-1 text-xs text-slate-500">
                                        Holiday yang berlaku pada periode aktif.
                                    </p>
                                </div>

                                @if(auth()->user()?->hasPermission('holiday.manage'))
                                    <a
                                        href="{{ route('scheduling.holiday-events.create') }}"
                                        class="shrink-0 rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
                                    >
                                        Add
                                    </a>
                                @endif
                            </div>
                        </div>

                        <div class="max-h-[320px] space-y-3 overflow-y-auto p-5">
                            @forelse($periodHolidayEvents ?? [] as $holiday)
                                <button
                                    type="button"
                                    @click="selectHolidayDate(@js($holiday['date']))"
                                    class="block w-full rounded-2xl border px-4 py-3 text-left transition"
                                    :class="selectedDate === @js($holiday['date'])
                                        ? 'border-indigo-300 bg-indigo-50 ring-2 ring-indigo-100'
                                        : 'border-slate-200 bg-slate-50 hover:border-indigo-200 hover:bg-indigo-50/60'"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <div class="truncate text-sm font-semibold text-slate-900">
                                                {{ $holiday['name'] }}
                                            </div>
                                            <div class="mt-1 text-xs text-slate-500">
                                                {{ $holiday['date'] }} · {{ $holiday['day_type_label'] }}
                                            </div>
                                        </div>

                                        <span class="shrink-0 rounded-full bg-white px-2 py-0.5 text-[11px] font-medium text-slate-600 ring-1 ring-slate-200">
                                            {{ $holiday['day_type_code'] }}
                                        </span>
                                    </div>

                                    <div class="mt-2 text-xs text-slate-500">
                                        Scope: {{ $holiday['scope_label'] }}
                                    </div>

                                    <div class="mt-3 text-xs font-medium text-indigo-600">
                                        Select date
                                        <span class="text-slate-300">·</span>
                                        <a
                                            href="{{ route('scheduling.holiday-events.show', $holiday['id']) }}"
                                            class="hover:text-indigo-500 hover:underline"
                                            @click.stop
                                        >
                                            Open detail
                                        </a>
                                    </div>

                                    @if(!empty($holiday['notes']))
                                        <div class="mt-2 line-clamp-2 text-xs leading-5 text-slate-500">
                                            {{ $holiday['notes'] }}
                                        </div>
                                    @endif
                                </button>
                            @empty
                                <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center">
                                    <div class="text-sm font-semibold text-slate-800">
                                        Tidak ada holiday pada periode ini
                                    </div>
                                    <div class="mt-1 text-xs text-slate-500">
                                        Tambah atau import holiday tanpa keluar dari workspace ini.
                                    </div>

                                    @if(auth()->user()?->hasPermission('holiday.manage'))
                                        <div class="mt-4 flex justify-center gap-2">
                                            <a
                                                href="{{ route('scheduling.holiday-events.create') }}"
                                                class="rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
                                            >
                                                Add Holiday
                                            </a>

                                            @if(Route::has('scheduling.holiday-events.import.create'))
                                                <a
                                                    href="{{ route('scheduling.holiday-events.import.create') }}"
                                                    class="rounded-xl bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500"
                                                >
                                                    Import
                                                </a>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endforelse
                        </div>
                    </div>
                    <div id="branch-calendar-day-detail" class="w-full max-w-full rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 px-5 py-4">
                            <h3 class="text-lg font-semibold tracking-tight text-slate-900" x-text="editMode ? 'Edit Calendar Day' : 'Day Detail'"></h3>
                        </div>

                        <div class="p-5">
                            <template x-if="selectedDay">
                                <div>
                                    <template x-if="!editMode">
                                        <div class="space-y-5">
                                            <div class="space-y-2">
                                                <div class="text-sm font-medium text-slate-500" x-text="selectedDay.day_name"></div>
                                                <div class="text-2xl font-semibold tracking-tight text-slate-900" x-text="selectedDay.work_date"></div>
                                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset" :class="badgeClass(selectedDay.day_type_code)" x-text="selectedDay.day_type_label"></span>
                                            </div>

                                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                                <div class="space-y-3 text-sm">
                                                    <div class="flex items-start justify-between gap-4">
                                                        <span class="text-slate-500">Work Date</span>
                                                        <span class="text-right font-medium text-slate-800" x-text="selectedDay.work_date"></span>
                                                    </div>

                                                    <div class="flex items-start justify-between gap-4">
                                                        <span class="text-slate-500">Day Type</span>
                                                        <span class="text-right font-medium text-slate-800" x-text="selectedDay.day_type_label"></span>
                                                    </div>

                                                    <div class="flex items-start justify-between gap-4">
                                                        <span class="text-slate-500">Is Workday</span>
                                                        <span class="text-right font-medium text-slate-800" x-text="selectedDay.is_workday ? 'Yes' : 'No'"></span>
                                                    </div>

                                                    <div class="flex items-start justify-between gap-4">
                                                        <span class="text-slate-500">Status</span>
                                                        <span class="text-right font-medium text-slate-800" x-text="selectedDay.is_override ? 'Manual Override' : 'Generated'"></span>
                                                    </div>

                                                    <div class="flex items-start justify-between gap-4">
                                                        <span class="text-slate-500">Updated At</span>
                                                        <span class="text-right font-medium text-slate-800" x-text="selectedDay.updated_at || '—'"></span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="space-y-2">
                                                <div class="text-sm font-semibold text-slate-700">Notes</div>
                                                <div class="rounded-2xl border border-slate-200 bg-white p-4 text-sm leading-6 text-slate-600" x-text="selectedDay.notes || 'No notes for this date.'"></div>
                                            </div>

                                            <div class="flex flex-wrap items-center gap-3 pt-1">
                                                <template x-if="canManage && selectedDay.id">
                                                    <button type="button" @click="startEdit()" class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                                                        Edit Day
                                                    </button>
                                                </template>

                                                <button type="button" @click="setSelectedDate(selectedDay.work_date)" class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                                    Refresh Detail
                                                </button>
                                            </div>
                                        </div>
                                    </template>

                                    <template x-if="editMode">
                                        <form @submit.prevent="saveEdit" class="space-y-5">
                                            <div class="space-y-2">
                                                <div class="text-sm font-medium text-slate-500">Edit Day</div>
                                                <div class="text-xl font-semibold text-slate-900" x-text="selectedDay.work_date"></div>
                                                <div class="text-sm text-slate-500" x-text="selectedDay.day_name"></div>
                                            </div>

                                            <div class="space-y-4">
                                                <div>
                                                    <label class="mb-2 block text-sm font-medium text-slate-700">Day Type</label>
                                                    <select
                                                        id="branch-calendar-day-type-select"
                                                        x-model="editForm.day_type_code"
                                                        @change="applyDayTypeDefaultWorkday()"
                                                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                                                    >
                                                        @foreach($dayTypeOptions as $option)
                                                            <option value="{{ $option['value'] }}">
                                                                {{ $option['label'] }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div>
                                                    <label class="mb-2 block text-sm font-medium text-slate-700">Is Workday</label>
                                                    <div class="flex items-center gap-6 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                                        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                                            <input type="radio" x-model="editForm.is_workday" :value="true" class="border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                                            <span>Yes</span>
                                                        </label>

                                                        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                                            <input type="radio" x-model="editForm.is_workday" :value="false" class="border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                                            <span>No</span>
                                                        </label>
                                                    </div>
                                                </div>

                                                <div>
                                                    <label class="mb-2 block text-sm font-medium text-slate-700">Notes</label>
                                                    <textarea x-model="editForm.notes" rows="4" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100" placeholder="Optional notes for this date..."></textarea>
                                                </div>
                                            </div>

                                            <div class="flex flex-wrap items-center gap-3 pt-2">
                                                <button type="button" @click="cancelEdit()" class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                                    Cancel
                                                </button>

                                                <button type="submit" :disabled="saving" class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-60">
                                                    <span x-text="saving ? 'Saving...' : 'Save Changes'"></span>
                                                </button>
                                            </div>
                                        </form>
                                    </template>
                                </div>
                            </template>

                            <template x-if="!selectedDay">
                                <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
                                    <h3 class="text-base font-semibold text-slate-900">No date selected</h3>
                                    <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">
                                        Select a date from the calendar to inspect its details.
                                    </p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    @else
        <x-ui.empty-state
            title="Missing calendar context"
            description="Please make sure branch and payroll period data are available."
        />
    @endif
</div>

<script>
function branchCalendarPage(config) {
    return {
        initialMatrix: config.initialMatrix || [],
        dayTypeOptions: config.dayTypeOptions || [],
        selectedDate: config.initialSelectedDate || null,
        viewMode: config.initialViewMode || 'grid',
        showNotesOnly: !!config.initialShowNotesOnly,
        showNonWorkdayOnly: !!config.initialShowNonWorkdayOnly,
        updateUrlTemplate: config.updateUrlTemplate || '',
        csrfToken: config.csrfToken || '',
        canManage: !!config.canManage,
        hasCalendarData: !!config.hasCalendarData,
        editMode: false,
        saving: false,
        flashMessage: config.sessionSuccess || null,

        editForm: {
            day_type_code: '',
            is_workday: true,
            notes: '',
        },

        init() {
            if (!this.selectedDate) {
                const first = this.flattenDays().find(day => !!day);
                if (first) this.selectedDate = first.work_date;
            }
        },

        submitGenerateForm() {
            const form = document.getElementById('branch-calendar-generate-form');
            if (form) form.submit();
        },

        flattenDays() {
            return this.initialMatrix.flat().filter(day => !!day);
        },

        get filteredRows() {
            return this.flattenDays().filter(day => this.matchesFilter(day));
        },

        get selectedDay() {
            return this.flattenDays().find(day => day.work_date === this.selectedDate) || null;
        },

        matchesFilter(day) {
            if (!day) return false;
            if (this.showNotesOnly && !day.notes) return false;
            if (this.showNonWorkdayOnly && day.is_workday) return false;
            return true;
        },

        setSelectedDate(date) {
            this.selectedDate = date;
            this.editMode = false;
            this.flashMessage = null;
        },

        selectHolidayDate(date) {
            this.setSelectedDate(date);

            this.$nextTick(() => {
                const detail = document.getElementById('branch-calendar-day-detail');

                if (detail) {
                    detail.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start',
                    });
                }
            });
        },

        startEdit() {
            if (!this.selectedDay || !this.selectedDay.id || !this.canManage) return;

            this.editForm = {
                day_type_code: String(this.selectedDay.day_type_code || ''),
                is_workday: !!this.selectedDay.is_workday,
                notes: this.selectedDay.notes || '',
            };

            this.editMode = true;
            this.flashMessage = null;

            this.$nextTick(() => {
                const select = document.getElementById('branch-calendar-day-type-select');

                if (select) {
                    select.value = this.editForm.day_type_code;
                }
            });
        },

        cancelEdit() {
            this.editMode = false;
        },

        badgeClass(dayTypeCode) {
            const map = {
                HOLIDAY_NATIONAL: 'bg-rose-50 text-rose-700 ring-rose-200',
                HOLIDAY_COMPANY: 'bg-amber-50 text-amber-700 ring-amber-200',
                WEEKOFF: 'bg-slate-100 text-slate-700 ring-slate-200',
                SPECIAL: 'bg-sky-50 text-sky-700 ring-sky-200',
                HALF_DAY: 'bg-sky-50 text-sky-700 ring-sky-200',
                UNGENERATED: 'bg-slate-100 text-slate-700 ring-slate-200',
            };

            return map[dayTypeCode] || 'bg-slate-100 text-slate-700 ring-slate-200';
        },

        dayCardClass(day) {
            const bgMap = {
                WORKDAY: 'bg-white',
                HOLIDAY_NATIONAL: 'bg-rose-50',
                HOLIDAY_COMPANY: 'bg-amber-50',
                WEEKOFF: 'bg-slate-50',
                SPECIAL: 'bg-sky-50',
                HALF_DAY: 'bg-sky-50',
                UNGENERATED: 'bg-slate-50',
            };

            const baseBg = bgMap[day.day_type_code] || 'bg-white';
            const selected = this.selectedDate === day.work_date
                ? 'ring-2 ring-indigo-500 border-indigo-500 shadow-md bg-indigo-50/30'
                : 'border-slate-200';

            return `${baseBg} ${selected}`;
        },

        dayTypeLabel(code) {
            const found = this.dayTypeOptions.find(option => option.value === code);
            return found ? found.label : code;
        },

        defaultWorkdayForDayType(code) {
            const found = this.dayTypeOptions.find(option => option.value === code);

            if (found && typeof found.is_workday_default !== 'undefined') {
                return !!found.is_workday_default;
            }

            return ['WORKDAY', 'HALF_DAY', 'SPECIAL'].includes(code);
        },

        applyDayTypeDefaultWorkday() {
            this.editForm.is_workday = this.defaultWorkdayForDayType(this.editForm.day_type_code);
        },

        async saveEdit() {
            if (!this.selectedDay || !this.selectedDay.id) return;

            this.saving = true;
            this.flashMessage = null;

            const formData = new FormData();
            formData.append('_method', 'PATCH');
            formData.append('_token', this.csrfToken);
            formData.append('period_code', @js($filters['period_code'] ?? ''));
            formData.append('view_mode', this.viewMode);
            
            const select = document.getElementById('branch-calendar-day-type-select');

            if (select) {
                this.editForm.day_type_code = select.value;
            }

            this.applyDayTypeDefaultWorkday();
            
            formData.append('day_type_code', this.editForm.day_type_code);
            formData.append('is_workday', this.editForm.is_workday ? '1' : '0');
            formData.append('notes', this.editForm.notes || '');

            try {
                const response = await fetch(
                    this.updateUrlTemplate.replace('__ID__', this.selectedDay.id),
                    {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': this.csrfToken,
                            'Accept': 'application/json',
                        }
                    }
                );

                if (!response.ok) throw new Error('Failed to update calendar day');

                const target = this.flattenDays().find(day => day.id === this.selectedDay.id);

                if (target) {
                    target.day_type_code = this.editForm.day_type_code;
                    target.day_type_label = this.dayTypeLabel(this.editForm.day_type_code);
                    target.is_workday = !!this.editForm.is_workday;
                    target.notes = this.editForm.notes || null;
                    target.updated_at = new Date().toLocaleString('sv-SE');
                    target.is_override = true;
                    target.has_override = true;
                }

                this.editMode = false;
                this.flashMessage = 'Calendar day updated successfully.';
            } catch (error) {
                this.flashMessage = 'Failed to update calendar day.';
            } finally {
                this.saving = false;
            }
        },
    }
}
</script>
@endsection