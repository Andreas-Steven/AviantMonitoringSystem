@extends('layouts.app')

@section('content')
@php
    $today = now()->toDateString();
@endphp

<div class="space-y-6">
    <x-ui.page-header
        title="Employee Shift Assignments"
        subtitle="Kelola histori assignment shift per employee dengan filter yang lebih mudah dibaca."
        :breadcrumbs="[
            ['label' => 'Scheduling'],
            ['label' => 'Employee Shift Assignments'],
        ]"
    >
        <x-slot:actions>
            <div class="flex items-center gap-3">
                <x-ui.button
                    type="button"
                    variant="ghost"
                    onclick="window.location='{{ route('scheduling.employee-shift-assignments.coverage') }}'"
                >
                    Coverage
                </x-ui.button>

                 @if(auth()->user()?->hasPermission('roster.manage') && Route::has('scheduling.employee-shift-assignments.import.create'))
                    <x-ui.button
                        type="button"
                        onclick="window.location='{{ route('scheduling.employee-shift-assignments.import.create') }}'"
                    >
                        Import Shift Assignment
                    </x-ui.button>
                @endif

                @if(auth()->user()->hasPermission('shift.manage'))
                    <x-ui.button
                        type="button"
                        variant="ghost"
                        onclick="window.location='{{ route('scheduling.employee-shift-assignments.bulk-create') }}'"
                    >
                        Bulk Assignment
                    </x-ui.button>

                    <x-ui.button
                        variant="primary"
                        onclick="window.location='{{ route('scheduling.employee-shift-assignments.create') }}'"
                    >
                        Add Assignment
                    </x-ui.button>
                @endif
            </div>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.page-section
        title="Assignment Directory"
        subtitle="Filter berdasarkan employee, shift, dan assignment type."
    >
        <form method="GET" action="{{ route('scheduling.employee-shift-assignments.index') }}" class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_220px_220px_auto]">
            <x-ui.field label="Search">
                <input
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Cari emp code / nama employee"
                    class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                >
            </x-ui.field>

            <x-ui.field label="Shift">
                <select
                    name="shift_id"
                    class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                >
                    <option value="">All</option>
                    @foreach($shifts as $shift)
                        <option value="{{ $shift->shift_id }}" @selected((string) request('shift_id') === (string) $shift->shift_id)>
                            {{ $shift->shift_name }}
                        </option>
                    @endforeach
                </select>
            </x-ui.field>

            <x-ui.field label="Assignment Type">
                <select
                    name="assignment_type_code"
                    class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                >
                    <option value="">All</option>
                    @foreach($assignmentTypes as $type)
                        <option value="{{ $type->assignment_type_code }}" @selected(request('assignment_type_code') === $type->assignment_type_code)>
                            {{ $type->assignment_type_name }}
                        </option>
                    @endforeach
                </select>
            </x-ui.field>

            <div class="flex items-end gap-3">
                <x-ui.button type="submit">
                    Search
                </x-ui.button>

                <x-ui.button
                    type="button"
                    variant="ghost"
                    onclick="window.location='{{ route('scheduling.employee-shift-assignments.index') }}'"
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
                        Shift
                    </th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Assignment Type
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
                                {{ $assignment->employee->full_name ?? '-' }}
                            </div>
                            <div class="mt-1 text-xs text-slate-500">
                                {{ $assignment->employee->emp_code ?? '-' }}
                            </div>
                        </td>

                        <td class="px-5 py-4">
                            <div class="font-medium text-slate-900">
                                {{ $assignment->shift->shift_name ?? '-' }}
                            </div>
                            <div class="mt-1 text-xs text-slate-500">
                                {{ $assignment->shift->shift_code ?? '-' }}
                            </div>
                        </td>

                        <td class="px-5 py-4 text-slate-600">
                            {{ $assignment->assignmentType->assignment_type_name ?? $assignment->assignment_type_code }}
                        </td>

                        <td class="px-5 py-4 text-slate-600">
                            <div>{{ optional($assignment->effective_start_date)->format('Y-m-d') ?? '-' }}</div>
                            <div class="mt-1 text-xs text-slate-500">
                                s/d {{ optional($assignment->effective_end_date)->format('Y-m-d') ?? 'Open End' }}
                            </div>
                        </td>

                        <td class="px-5 py-4">
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 {{ $statusConfig['class'] }}">
                                {{ $statusConfig['label'] }}
                            </span>
                        </td>

                        <td class="px-5 py-4">
                            <div class="flex justify-end gap-2">
                                <x-ui.button
                                    size="sm"
                                    variant="ghost"
                                    onclick="window.location='{{ route('scheduling.employee-shift-assignments.show', $assignment->employee_shift_assignment_id) }}'"
                                >
                                    View
                                </x-ui.button>

                                @if(auth()->user()->hasPermission('shift.manage'))
                                    <x-ui.button
                                        size="sm"
                                        onclick="window.location='{{ route('scheduling.employee-shift-assignments.edit', $assignment->employee_shift_assignment_id) }}'"
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
                                description="Belum ada data employee shift assignment yang sesuai filter."
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if(method_exists($assignments, 'hasPages') && $assignments->hasPages())
            <div class="border-t border-slate-200 bg-white px-5 py-4">
                {{ $assignments->links() }}
            </div>
        @endif
    </x-ui.table-shell>
</div>
@endsection