@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Holiday Calendar"
        subtitle="Kelola holiday source untuk national, company, half day, dan special event."
        :breadcrumbs="[
            ['label' => 'Scheduling'],
            ['label' => 'Holiday Calendar'],
        ]"
    >
        <x-slot:actions>
            @if(auth()->user()?->hasPermission('holiday.manage'))
                <x-ui.button
                    type="button"
                    variant="ghost"
                    onclick="window.location='{{ route('scheduling.holiday-events.import.create') }}'"
                >
                    Import from Google
                </x-ui.button>
            @endif
            @if(auth()->user()->hasPermission('holiday.manage'))
                <x-ui.button
                    variant="primary"
                    onclick="window.location='{{ route('scheduling.holiday-events.create') }}'"
                >
                    Add Holiday Event
                </x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.page-section
        title="Holiday Directory"
        subtitle="Filter berdasarkan kode, nama, jenis hari, status, dan tanggal."
    >
        <form method="GET" class="grid gap-4 lg:grid-cols-[1fr_1fr_180px_180px_180px_auto]">
            <x-ui.field label="Search">
                <input
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Cari holiday code / holiday name"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                >
            </x-ui.field>

            <x-ui.field label="Day Type">
                <select name="day_type_code" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                    <option value="">Semua day type</option>
                    @foreach($dayTypes as $dayType)
                        <option value="{{ $dayType->day_type_code }}" @selected(request('day_type_code') === $dayType->day_type_code)>
                            {{ $dayType->day_type_name }}
                        </option>
                    @endforeach
                </select>
            </x-ui.field>

            <x-ui.field label="Status">
                <select name="active" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                    <option value="">Semua status</option>
                    <option value="1" @selected(request('active') === '1')>Active</option>
                    <option value="0" @selected(request('active') === '0')>Inactive</option>
                </select>
            </x-ui.field>

            <x-ui.field label="Date From">
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
            </x-ui.field>

            <x-ui.field label="Date To">
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
            </x-ui.field>

            <div class="flex items-end gap-3">
                <x-ui.button type="submit">Search</x-ui.button>
                <x-ui.button
                    type="button"
                    variant="ghost"
                    onclick="window.location='{{ route('scheduling.holiday-events.index') }}'"
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
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Holiday</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Date</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Day Type</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Scope</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse($events as $event)
                    @php
                        $scopeSummary = $event->scopes->contains(fn ($scope) => $scope->applies_to_all_branches)
                            ? 'All Branches'
                            : $event->scopes->pluck('branch.branch_name')->filter()->implode(', ');

                        $dayTypeClass = match($event->day_type_code) {
                            'HOLIDAY_NATIONAL' => 'bg-rose-100 text-rose-700 ring-1 ring-rose-200',
                            'HOLIDAY_COMPANY' => 'bg-amber-100 text-amber-700 ring-1 ring-amber-200',
                            'HALF_DAY' => 'bg-sky-100 text-sky-700 ring-1 ring-sky-200',
                            default => 'bg-indigo-100 text-indigo-700 ring-1 ring-indigo-200',
                        };
                    @endphp

                    <tr class="transition hover:bg-slate-50">
                        <td class="px-5 py-4">
                            <div class="font-medium text-slate-900">{{ $event->holiday_name }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ $event->holiday_code }}</div>
                        </td>

                        <td class="px-5 py-4 text-slate-600">
                            {{ optional($event->holiday_date)->format('Y-m-d') }}
                        </td>

                        <td class="px-5 py-4">
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium {{ $dayTypeClass }}">
                                {{ $event->dayType->day_type_name ?? $event->day_type_code }}
                            </span>
                        </td>

                        <td class="px-5 py-4 text-slate-600">
                            {{ $scopeSummary ?: '-' }}
                        </td>

                        <td class="px-5 py-4">
                            @if($event->active)
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200">Active</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700 ring-1 ring-slate-200">Inactive</span>
                            @endif
                        </td>

                        <td class="px-5 py-4">
                            <div class="flex justify-end gap-2">
                                <x-ui.button
                                    size="sm"
                                    variant="ghost"
                                    onclick="window.location='{{ route('scheduling.holiday-events.show', $event->holiday_event_id) }}'"
                                >
                                    Detail
                                </x-ui.button>

                                @if(auth()->user()->hasPermission('holiday.manage'))
                                    <x-ui.button
                                        size="sm"
                                        onclick="window.location='{{ route('scheduling.holiday-events.edit', $event->holiday_event_id) }}'"
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
                                title="No holiday events found"
                                description="Belum ada data holiday event."
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($events->hasPages())
            <div class="border-t border-slate-200 bg-white px-5 py-4">
                {{ $events->withQueryString()->links() }}
            </div>
        @endif
    </x-ui.table-shell>
</div>
@endsection