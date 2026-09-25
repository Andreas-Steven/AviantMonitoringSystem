@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Employee Assignments"
        subtitle="Kelola histori assignment organisasi karyawan dan lakukan pencarian terarah."
        :breadcrumbs="[
            ['label' => 'Master'],
            ['label' => 'Employee Assignments'],
        ]"
    >
        <x-slot:actions>
            <div class="flex items-center gap-3">
                <x-ui.button
                    type="button"
                    variant="ghost"
                    onclick="window.location='{{ route('master.employee-assignments.coverage') }}'"
                >
                    Coverage
                </x-ui.button>

                @if(auth()->user()?->hasPermission('assignment.manage') && Route::has('master.employee-assignments.import.create'))
                    <x-ui.button
                        type="button"
                        onclick="window.location='{{ route('master.employee-assignments.import.create') }}'"
                    >
                        Import Placement
                    </x-ui.button>
                @endif

                @if(auth()->user()->hasPermission('assignment.manage'))
                    <x-ui.button
                        type="button"
                        variant="ghost"
                        onclick="window.location='{{ route('master.employee-assignments.bulk-create') }}'"
                    >
                        Bulk Assignment
                    </x-ui.button>

                    <x-ui.button
                        variant="primary"
                        onclick="window.location='{{ route('master.employee-assignments.create') }}'"
                    >
                        Add Assignment
                    </x-ui.button>
                @endif
            </div>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.page-section
        title="Assignment Directory"
        subtitle="Filter berdasarkan employee, branch, primary flag, atau status assignment."
    >
        <form method="GET" action="{{ route('master.employee-assignments.index') }}" class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_220px_220px_220px_auto]">
            <x-ui.field label="Search">
                <input
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Cari emp code / nama employee"
                    class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                >
            </x-ui.field>

            <x-ui.field label="Branch">
                <select
                    name="branch_id"
                    class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                >
                    <option value="">All</option>
                    @foreach($branches as $branch)
                        <option
                            value="{{ $branch->branch_id }}"
                            @selected((string) request('branch_id') === (string) $branch->branch_id)
                        >
                            {{ $branch->branch_name }}
                        </option>
                    @endforeach
                </select>
            </x-ui.field>

            <x-ui.field label="Primary">
                <select
                    name="is_primary"
                    class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                >
                    <option value="">All</option>
                    <option value="1" @selected((string) request('is_primary') === '1')>Primary</option>
                    <option value="0" @selected((string) request('is_primary') === '0')>Secondary</option>
                </select>
            </x-ui.field>

            <x-ui.field label="Status">
                <select
                    name="assignment_status"
                    class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                >
                    <option value="">All</option>
                    <option value="current" @selected(request('assignment_status') === 'current')>Current</option>
                    <option value="historical" @selected(request('assignment_status') === 'historical')>Historical</option>
                    <option value="upcoming" @selected(request('assignment_status') === 'upcoming')>Upcoming</option>
                </select>
            </x-ui.field>

            <div class="flex items-end gap-3">
                <x-ui.button type="submit">
                    Search
                </x-ui.button>

                <x-ui.button
                    type="button"
                    variant="ghost"
                    onclick="window.location='{{ route('master.employee-assignments.index') }}'"
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
                        Organization
                    </th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Grade
                    </th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Period
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
                @forelse($assignments as $assignment)
                    @php
                        $rowStatus = 'current';

                        if ($assignment->effective_start_date?->format('Y-m-d') > $today) {
                            $rowStatus = 'upcoming';
                        } elseif ($assignment->effective_end_date && $assignment->effective_end_date->format('Y-m-d') < $today) {
                            $rowStatus = 'historical';
                        }

                        $statusConfig = match ($rowStatus) {
                            'upcoming' => ['label' => 'Upcoming', 'class' => 'bg-sky-100 text-sky-700 ring-sky-200'],
                            'historical' => ['label' => 'Historical', 'class' => 'bg-slate-100 text-slate-700 ring-slate-200'],
                            default => ['label' => 'Current', 'class' => 'bg-emerald-100 text-emerald-700 ring-emerald-200'],
                        };
                    @endphp

                    <tr class="transition hover:bg-slate-50">
                        <td class="px-5 py-4">
                            <div class="font-medium text-slate-900">
                                {{ $assignment->employee->full_name ?? '—' }}
                            </div>
                            <div class="mt-1 text-xs text-slate-500">
                                {{ $assignment->employee->emp_code ?? '—' }}
                            </div>
                        </td>

                        <td class="px-5 py-4">
                            <div class="font-medium text-slate-900">
                                {{ $assignment->branch->branch_name ?? '—' }}
                            </div>
                            <div class="mt-1 text-xs text-slate-500">
                                {{ $assignment->department->dept_name ?? '—' }} · {{ $assignment->role->role_name ?? '—' }}
                            </div>
                        </td>

                        <td class="px-5 py-4 text-slate-600">
                            {{ $assignment->grade->grade_name ?? '—' }}
                        </td>

                        <td class="px-5 py-4 text-slate-600">
                            <div>{{ optional($assignment->effective_start_date)->format('Y-m-d') ?? '—' }}</div>
                            <div class="mt-1 text-xs text-slate-500">
                                s/d {{ optional($assignment->effective_end_date)->format('Y-m-d') ?? 'Open End' }}
                            </div>
                        </td>

                        <td class="px-5 py-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 {{ $statusConfig['class'] }}">
                                    {{ $statusConfig['label'] }}
                                </span>

                                @if($assignment->is_primary)
                                    <span class="inline-flex items-center rounded-full bg-violet-100 px-2.5 py-1 text-xs font-medium text-violet-700 ring-1 ring-violet-200">
                                        Primary
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-700 ring-1 ring-amber-200">
                                        Secondary
                                    </span>
                                @endif
                            </div>
                        </td>

                        <td class="px-5 py-4">
                            <div class="flex justify-end gap-2">
                                <x-ui.button
                                    type="button"
                                    size="sm"
                                    variant="ghost"
                                    onclick="window.location='{{ route('master.employee-assignments.show', $assignment->assignment_id) }}'"
                                >
                                    View
                                </x-ui.button>

                                @if(auth()->user()->hasPermission('assignment.manage'))
                                    <x-ui.button
                                        type="button"
                                        size="sm"
                                        onclick="window.location='{{ route('master.employee-assignments.edit', $assignment->assignment_id) }}'"
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
                                title="No assignments found"
                                description="Belum ada data employee assignment untuk filter yang sedang dipakai."
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($assignments->hasPages())
            <div class="border-t border-slate-200 bg-white px-5 py-4">
                {{ $assignments->withQueryString()->links() }}
            </div>
        @endif
    </x-ui.table-shell>
</div>
@endsection