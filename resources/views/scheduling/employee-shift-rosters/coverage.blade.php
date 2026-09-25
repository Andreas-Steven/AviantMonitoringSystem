@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Employee Shift Roster Coverage"
        subtitle="Audit roster harian employee berdasarkan tanggal referensi."
        :breadcrumbs="[
            ['label' => 'Scheduling'],
            ['label' => 'Employee Shift Rosters', 'url' => route('scheduling.employee-shift-rosters.index')],
            ['label' => 'Coverage'],
        ]"
    >
        <x-slot:actions>
            <div class="flex items-center gap-3">
                <x-ui.button
                    type="button"
                    variant="ghost"
                    onclick="window.location='{{ route('scheduling.employee-shift-rosters.index') }}'"
                >
                    Back to Rosters
                </x-ui.button>
            </div>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-4 md:grid-cols-3">
        <x-ui.stat-card
            label="No Roster"
            :value="$summary['no_roster']"
            hint="Belum memiliki roster"
        />
        <x-ui.stat-card
            label="Has Roster"
            :value="$summary['has_roster']"
            hint="Tepat satu roster"
        />
        <x-ui.stat-card
            label="Duplicate Roster"
            :value="$summary['duplicate_roster']"
            hint="Lebih dari satu roster"
        />
    </div>

    <x-ui.page-section
        title="Coverage Filter"
        subtitle="Gunakan tanggal referensi untuk melihat roster employee pada hari tertentu."
    >
        <form method="GET" action="{{ route('scheduling.employee-shift-rosters.coverage') }}" class="grid gap-4 xl:grid-cols-[180px_minmax(0,1fr)_240px_auto]">
            <x-ui.field label="Reference Date">
                <input
                    type="date"
                    name="reference_date"
                    value="{{ request('reference_date', $referenceDate) }}"
                    class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                >
            </x-ui.field>

            <x-ui.field label="Search">
                <input
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Cari emp code / nama / biometric"
                    class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                >
            </x-ui.field>

            <x-ui.field label="Coverage Status">
                <select
                    name="coverage_status"
                    class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                >
                    <option value="">All</option>
                    <option value="NO_ROSTER" @selected(request('coverage_status') === 'NO_ROSTER')>No Roster</option>
                    <option value="HAS_ROSTER" @selected(request('coverage_status') === 'HAS_ROSTER')>Has Roster</option>
                    <option value="DUPLICATE_ROSTER" @selected(request('coverage_status') === 'DUPLICATE_ROSTER')>Duplicate Roster</option>
                </select>
            </x-ui.field>

            <div class="flex items-end gap-3">
                <x-ui.button type="submit">
                    Search
                </x-ui.button>

                <x-ui.button
                    type="button"
                    variant="ghost"
                    onclick="window.location='{{ route('scheduling.employee-shift-rosters.coverage') }}'"
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
                        Employee
                    </th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Coverage Status
                    </th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Roster Snapshot
                    </th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Coverage Note
                    </th>
                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Action
                    </th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse($rows as $row)
                    @php
                        $status = $row['coverage_status'];

                        $statusConfig = match ($status) {
                            'NO_ROSTER' => [
                                'label' => 'No Roster',
                                'class' => 'bg-rose-100 text-rose-700 ring-rose-200',
                            ],
                            'HAS_ROSTER' => [
                                'label' => 'Has Roster',
                                'class' => 'bg-emerald-100 text-emerald-700 ring-emerald-200',
                            ],
                            default => [
                                'label' => 'Duplicate Roster',
                                'class' => 'bg-red-100 text-red-700 ring-red-200',
                            ],
                        };

                        $roster = $row['roster'];
                    @endphp

                    <tr class="transition hover:bg-slate-50">
                        <td class="px-5 py-4">
                            <div class="font-medium text-slate-900">
                                {{ $row['employee']->full_name }}
                            </div>
                            <div class="mt-1 text-xs text-slate-500">
                                {{ $row['employee']->emp_code }}
                                @if($row['employee']->biometric_code)
                                    · {{ $row['employee']->biometric_code }}
                                @endif
                            </div>
                        </td>

                        <td class="px-5 py-4">
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 {{ $statusConfig['class'] }}">
                                {{ $statusConfig['label'] }}
                            </span>
                        </td>

                        <td class="px-5 py-4 text-slate-600">
                            @if($status === 'HAS_ROSTER' && $roster)
                                <div class="font-medium text-slate-900">
                                    {{ $roster->shift->shift_name ?? '—' }}
                                </div>
                                <div class="mt-1 text-xs text-slate-500">
                                    {{ $roster->shift->shift_code ?? '—' }}
                                </div>
                                <div class="mt-1 text-xs text-slate-500">
                                    {{ $roster->sourceType->source_type_name ?? $roster->source_type_code ?? '—' }}
                                </div>
                            @elseif($status === 'DUPLICATE_ROSTER')
                                <div class="text-xs text-slate-600">
                                    {{ $row['roster_count'] }} roster ditemukan pada {{ $referenceDate }}.
                                </div>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>

                        <td class="px-5 py-4 text-slate-600">
                            {{ $row['coverage_note'] }}
                        </td>

                        <td class="px-5 py-4">
                            <div class="flex justify-end gap-2">
                                <x-ui.button
                                    type="button"
                                    size="sm"
                                    variant="ghost"
                                    onclick="window.location='{{ route('scheduling.employee-shift-rosters.index', ['q' => $row['employee']->emp_code, 'date_from' => $referenceDate, 'date_to' => $referenceDate]) }}'"
                                >
                                    View Rosters
                                </x-ui.button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-14">
                            <x-ui.empty-state
                                title="No coverage result"
                                description="Tidak ada employee yang cocok dengan filter coverage yang sedang dipakai."
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-ui.table-shell>
</div>
@endsection