@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Shifts"
        subtitle="Kelola master shift kerja untuk kebutuhan penjadwalan dan absensi."
        :breadcrumbs="[
            ['label' => 'Scheduling'],
            ['label' => 'Shifts'],
        ]"
    >
        <x-slot:actions>
            @if(auth()->user()->hasPermission('shift.manage'))
                <x-ui.button
                    variant="primary"
                    onclick="window.location='{{ route('scheduling.shifts.create') }}'"
                >
                    Add Shift
                </x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.page-section
        title="Shift Directory"
        subtitle="Cari dan review data shift yang tersedia."
    >
        <form method="GET" action="{{ route('scheduling.shifts.index') }}" class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_220px_auto]">
            <x-ui.field label="Search">
                <input
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Cari shift code / shift name"
                    class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                >
            </x-ui.field>

            <x-ui.field label="Status">
                <select
                    name="active"
                    class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                >
                    <option value="">All</option>
                    <option value="1" @selected((string) request('active') === '1')>Active</option>
                    <option value="0" @selected((string) request('active') === '0')>Inactive</option>
                </select>
            </x-ui.field>

            <div class="flex items-end gap-3">
                <x-ui.button type="submit">
                    Search
                </x-ui.button>

                <x-ui.button
                    type="button"
                    variant="ghost"
                    onclick="window.location='{{ route('scheduling.shifts.index') }}'"
                >
                    Reset
                </x-ui.button>
            </div>
        </form>
    </x-ui.page-section>

    <x-ui.table-shell>
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50/80">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Shift
                    </th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Start
                    </th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        End
                    </th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Cross Day
                    </th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Status
                    </th>
                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Action
                    </th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse($shifts as $shift)
                    <tr class="transition hover:bg-slate-50">
                        <td class="px-5 py-4">
                            <div class="font-medium text-slate-900">
                                {{ $shift->shift_name }}
                            </div>
                            <div class="mt-1 text-xs text-slate-500">
                                {{ $shift->shift_code }}
                            </div>
                        </td>

                        <td class="px-5 py-4 text-slate-600">
                            {{ $shift->start_time ? \Illuminate\Support\Carbon::parse($shift->start_time)->format('H:i') : '—' }}
                        </td>

                        <td class="px-5 py-4 text-slate-600">
                            {{ $shift->end_time ? \Illuminate\Support\Carbon::parse($shift->end_time)->format('H:i') : '—' }}
                        </td>

                        <td class="px-5 py-4">
                            @if($shift->cross_day_flag)
                                <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-700 ring-1 ring-amber-200">
                                    Yes
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700 ring-1 ring-slate-200">
                                    No
                                </span>
                            @endif
                        </td>

                        <td class="px-5 py-4">
                            @if($shift->active)
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200">
                                    Active
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700 ring-1 ring-slate-200">
                                    Inactive
                                </span>
                            @endif
                        </td>

                        <td class="px-5 py-4">
                            <div class="flex justify-end gap-2">
                                <x-ui.button
                                    type="button"
                                    size="sm"
                                    variant="ghost"
                                    onclick="window.location='{{ route('scheduling.shifts.show', $shift->shift_id) }}'"
                                >
                                    View
                                </x-ui.button>

                                @if(auth()->user()->hasPermission('shift.manage'))
                                    <x-ui.button
                                        type="button"
                                        size="sm"
                                        onclick="window.location='{{ route('scheduling.shifts.edit', $shift->shift_id) }}'"
                                    >
                                        Edit
                                    </x-ui.button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-14">
                            <x-ui.empty-state
                                title="No shifts found"
                                description="Belum ada data shift untuk filter yang sedang dipakai."
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($shifts->hasPages())
            <div class="border-t border-slate-200 bg-white px-5 py-4">
                {{ $shifts->withQueryString()->links() }}
            </div>
        @endif
    </x-ui.table-shell>
</div>
@endsection