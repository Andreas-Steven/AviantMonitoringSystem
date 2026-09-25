@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Assignment Coverage"
        subtitle="Audit kelengkapan dan konflik assignment employee berdasarkan tanggal referensi."
        :breadcrumbs="[
            ['label' => 'Master'],
            ['label' => 'Employee Assignments', 'url' => route('master.employee-assignments.index')],
            ['label' => 'Coverage'],
        ]"
    >
        <x-slot:actions>
            <div class="flex items-center gap-3">
                @if(auth()->user()->hasPermission('assignment.manage'))
                    <x-ui.button
                        type="button"
                        variant="ghost"
                        onclick="window.location='{{ route('master.employee-assignments.bulk-create') }}'"
                    >
                        Bulk Assignment
                    </x-ui.button>
                @endif

                <x-ui.button
                    type="button"
                    variant="ghost"
                    onclick="window.location='{{ route('master.employee-assignments.index') }}'"
                >
                    Back to Assignments
                </x-ui.button>
            </div>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat-card
            label="No Assignment"
            :value="$summary['no_assignment']"
            hint="Belum pernah memiliki assignment"
        />
        <x-ui.stat-card
            label="No Active Assignment"
            :value="$summary['no_active_assignment']"
            hint="Tidak ada assignment aktif"
        />
        <x-ui.stat-card
            label="Has Active Assignment"
            :value="$summary['has_active_assignment']"
            hint="Tepat satu assignment aktif"
        />
        <x-ui.stat-card
            label="Overlap Detected"
            :value="$summary['overlap_detected']"
            hint="Lebih dari satu assignment aktif"
        />
    </div>

    <x-ui.page-section
        title="Coverage Filter"
        subtitle="Gunakan tanggal referensi untuk melihat assignment aktif pada tanggal tertentu."
    >
        <form method="GET" action="{{ route('master.employee-assignments.coverage') }}" class="grid gap-4 xl:grid-cols-[180px_minmax(0,1fr)_240px_240px_auto]">
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

            <x-ui.field label="Employment Type">
                <select
                    name="employment_type_id"
                    class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                >
                    <option value="">All</option>
                    @foreach($employmentTypes as $employmentType)
                        <option
                            value="{{ $employmentType->employment_type_id }}"
                            @selected((string) request('employment_type_id') === (string) $employmentType->employment_type_id)
                        >
                            {{ $employmentType->employment_type_name }}
                        </option>
                    @endforeach
                </select>
            </x-ui.field>

            <x-ui.field label="Coverage Status">
                <select
                    name="coverage_status"
                    class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                >
                    <option value="">All</option>
                    <option value="NO_ASSIGNMENT" @selected(request('coverage_status') === 'NO_ASSIGNMENT')>No Assignment</option>
                    <option value="NO_ACTIVE_ASSIGNMENT" @selected(request('coverage_status') === 'NO_ACTIVE_ASSIGNMENT')>No Active Assignment</option>
                    <option value="HAS_ACTIVE_ASSIGNMENT" @selected(request('coverage_status') === 'HAS_ACTIVE_ASSIGNMENT')>Has Active Assignment</option>
                    <option value="OVERLAP_DETECTED" @selected(request('coverage_status') === 'OVERLAP_DETECTED')>Overlap Detected</option>
                </select>
            </x-ui.field>

            <div class="flex items-end gap-3">
                <x-ui.button type="submit">
                    Search
                </x-ui.button>

                <x-ui.button
                    type="button"
                    variant="ghost"
                    onclick="window.location='{{ route('master.employee-assignments.coverage') }}'"
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
                        Employment Type
                    </th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Coverage Status
                    </th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Active Assignment Snapshot
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
                            'NO_ASSIGNMENT' => [
                                'label' => 'No Assignment',
                                'class' => 'bg-rose-100 text-rose-700 ring-rose-200',
                            ],
                            'NO_ACTIVE_ASSIGNMENT' => [
                                'label' => 'No Active Assignment',
                                'class' => 'bg-amber-100 text-amber-700 ring-amber-200',
                            ],
                            'HAS_ACTIVE_ASSIGNMENT' => [
                                'label' => 'Has Active Assignment',
                                'class' => 'bg-emerald-100 text-emerald-700 ring-emerald-200',
                            ],
                            default => [
                                'label' => 'Overlap Detected',
                                'class' => 'bg-red-100 text-red-700 ring-red-200',
                            ],
                        };

                        $activeAssignment = $row['active_assignment'];
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

                        <td class="px-5 py-4 text-slate-600">
                            {{ $row['employment_type_name'] }}
                        </td>

                        <td class="px-5 py-4">
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 {{ $statusConfig['class'] }}">
                                {{ $statusConfig['label'] }}
                            </span>
                        </td>

                        <td class="px-5 py-4 text-slate-600">
                            @if($status === 'HAS_ACTIVE_ASSIGNMENT' && $activeAssignment)
                                <div class="font-medium text-slate-900">
                                    {{ $activeAssignment->branch->branch_name ?? '—' }}
                                </div>
                                <div class="mt-1 text-xs text-slate-500">
                                    {{ $activeAssignment->department->dept_name ?? '—' }} · {{ $activeAssignment->role->role_name ?? '—' }}
                                </div>
                                <div class="mt-1 text-xs text-slate-500">
                                    {{ optional($activeAssignment->effective_start_date)->format('Y-m-d') ?? '—' }}
                                    s/d
                                    {{ optional($activeAssignment->effective_end_date)->format('Y-m-d') ?? 'Open End' }}
                                </div>
                            @elseif($status === 'OVERLAP_DETECTED')
                                <div class="text-xs text-slate-600">
                                    {{ $row['active_assignment_count'] }} assignment aktif ditemukan pada {{ $referenceDate }}.
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
                                    onclick="window.location='{{ route('master.employees.show', $row['employee']->emp_id) }}'"
                                >
                                    View Employee
                                </x-ui.button>

                                <x-ui.button
                                    type="button"
                                    size="sm"
                                    variant="ghost"
                                    onclick="window.location='{{ route('master.employee-assignments.index', ['emp_id' => $row['employee']->emp_id]) }}'"
                                >
                                    View Assignments
                                </x-ui.button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-14">
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