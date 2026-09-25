@extends('layouts.app')

@section('content')
@php
    $today = $today ?? now()->toDateString();

    $currentShiftAssignment = $employee->shiftAssignments()
        ->with(['shift', 'assignmentType'])
        ->whereDate('effective_start_date', '<=', $today)
        ->where(function ($query) use ($today) {
            $query->whereNull('effective_end_date')
                ->orWhereDate('effective_end_date', '>=', $today);
        })
        ->orderByDesc('effective_start_date')
        ->orderByDesc('employee_shift_assignment_id')
        ->first();

    $currentWorkPatternAssignment = $employee->workPatternAssignments()
        ->with('workPattern')
        ->whereDate('effective_start_date', '<=', $today)
        ->where(function ($query) use ($today) {
            $query->whereNull('effective_end_date')
                ->orWhereDate('effective_end_date', '>=', $today);
        })
        ->orderByDesc('effective_start_date')
        ->orderByDesc('employee_work_pattern_assignment_id')
        ->first();
@endphp

<div class="space-y-6">
    <x-ui.page-header
        :title="$employee->full_name"
        subtitle="Detail employee, assignment aktif, shift assignment, work pattern assignment, dan histori assignment terbaru."
        :breadcrumbs="[
            ['label' => 'Master'],
            ['label' => 'Employees', 'url' => route('master.employees.index')],
            ['label' => 'Detail'],
        ]"
    >
        <x-slot:actions>
            <div class="flex flex-wrap items-center gap-3">
                <x-ui.button
                    type="button"
                    variant="ghost"
                    onclick="window.location='{{ route('master.employees.index') }}'"
                >
                    Back
                </x-ui.button>

                @if(auth()->user()->hasPermission('employee.manage'))
                    <x-ui.button
                        type="button"
                        onclick="window.location='{{ route('master.employees.edit', $employee->emp_id) }}'"
                    >
                        Edit Employee
                    </x-ui.button>
                @endif
            </div>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <x-ui.page-section
                title="Employee Snapshot"
                subtitle="Informasi dasar employee dan status aktif saat ini."
            >
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Employee Code</div>
                        <div class="mt-2 text-sm font-semibold text-slate-900">
                            {{ $employee->emp_code }}
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Status</div>
                        <div class="mt-2">
                            @if($employee->active)
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200">
                                    Active
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700 ring-1 ring-slate-200">
                                    Inactive
                                </span>
                            @endif
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Biometric Code</div>
                        <div class="mt-2 text-sm font-medium text-slate-900">
                            {{ $employee->biometric_code ?: '—' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Employment Type</div>
                        <div class="mt-2 text-sm font-medium text-slate-900">
                            {{ $employee->employmentType->employment_type_name ?? '—' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Join Date</div>
                        <div class="mt-2 text-sm font-medium text-slate-900">
                            {{ optional($employee->join_date)->format('Y-m-d') ?? '—' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Resign Date</div>
                        <div class="mt-2 text-sm font-medium text-slate-900">
                            {{ optional($employee->resign_date)->format('Y-m-d') ?? '—' }}
                        </div>
                    </div>
                </div>
            </x-ui.page-section>

            <x-ui.page-section
                title="Current Assignment"
                subtitle="Assignment organisasi yang aktif pada tanggal hari ini."
            >
                @if($currentAssignment)
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Branch</div>
                            <div class="mt-2 text-sm font-medium text-slate-900">
                                {{ $currentAssignment->branch->branch_name ?? '—' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Department</div>
                            <div class="mt-2 text-sm font-medium text-slate-900">
                                {{ $currentAssignment->department->dept_name ?? '—' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Role</div>
                            <div class="mt-2 text-sm font-medium text-slate-900">
                                {{ $currentAssignment->role->role_name ?? '—' }}
                            </div>
                            <div class="mt-1 text-xs text-slate-500">
                                {{ $currentAssignment->role->department->dept_name ?? '—' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Grade</div>
                            <div class="mt-2 text-sm font-medium text-slate-900">
                                {{ $currentAssignment->grade->grade_name ?? '—' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Effective Start</div>
                            <div class="mt-2 text-sm font-medium text-slate-900">
                                {{ optional($currentAssignment->effective_start_date)->format('Y-m-d') ?? '—' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Effective End</div>
                            <div class="mt-2 text-sm font-medium text-slate-900">
                                {{ optional($currentAssignment->effective_end_date)->format('Y-m-d') ?? 'Open End' }}
                            </div>
                        </div>

                        <div class="md:col-span-2">
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200">
                                    Current
                                </span>

                                @if($currentAssignment->is_primary)
                                    <span class="inline-flex items-center rounded-full bg-violet-100 px-2.5 py-1 text-xs font-medium text-violet-700 ring-1 ring-violet-200">
                                        Primary
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-700 ring-1 ring-amber-200">
                                        Secondary
                                    </span>
                                @endif

                                <x-ui.button
                                    type="button"
                                    size="sm"
                                    variant="ghost"
                                    onclick="window.location='{{ route('master.employee-assignments.show', $currentAssignment->assignment_id) }}'"
                                >
                                    Open Assignment Detail
                                </x-ui.button>
                            </div>
                        </div>
                    </div>
                @else
                    <x-ui.empty-state
                        title="No current assignment"
                        description="Belum ada assignment aktif untuk employee ini pada tanggal hari ini."
                    />
                @endif
            </x-ui.page-section>

            <div class="grid gap-6 lg:grid-cols-2">
                <x-ui.page-section
                    title="Current Shift Assignment"
                    subtitle="Shift assignment yang aktif pada tanggal hari ini."
                >
                    @if($currentShiftAssignment)
                        <div class="space-y-4">
                            <div>
                                <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Shift</div>
                                <div class="mt-2 text-sm font-medium text-slate-900">
                                    {{ $currentShiftAssignment->shift->shift_name ?? '—' }}
                                </div>
                                <div class="mt-1 text-xs text-slate-500">
                                    {{ $currentShiftAssignment->shift->shift_code ?? '—' }}
                                </div>
                            </div>

                            <div>
                                <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Assignment Type</div>
                                <div class="mt-2 text-sm font-medium text-slate-900">
                                    {{ $currentShiftAssignment->assignmentType->assignment_type_name ?? $currentShiftAssignment->assignment_type_code ?? '—' }}
                                </div>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                                <div>
                                    <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Effective Start</div>
                                    <div class="mt-2 text-sm font-medium text-slate-900">
                                        {{ optional($currentShiftAssignment->effective_start_date)->format('Y-m-d') ?? '—' }}
                                    </div>
                                </div>

                                <div>
                                    <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Effective End</div>
                                    <div class="mt-2 text-sm font-medium text-slate-900">
                                        {{ optional($currentShiftAssignment->effective_end_date)->format('Y-m-d') ?? 'Open End' }}
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200">
                                    Current
                                </span>

                                <x-ui.button
                                    type="button"
                                    size="sm"
                                    variant="ghost"
                                    onclick="window.location='{{ route('scheduling.employee-shift-assignments.show', $currentShiftAssignment->employee_shift_assignment_id) }}'"
                                >
                                    Open Shift Assignment
                                </x-ui.button>
                            </div>
                        </div>
                    @else
                        <x-ui.empty-state
                            title="No current shift assignment"
                            description="Belum ada shift assignment aktif untuk employee ini pada tanggal hari ini."
                        />
                    @endif
                </x-ui.page-section>

                <x-ui.page-section
                    title="Current Work Pattern Assignment"
                    subtitle="Work pattern assignment yang aktif pada tanggal hari ini."
                >
                    @if($currentWorkPatternAssignment)
                        <div class="space-y-4">
                            <div>
                                <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Work Pattern</div>
                                <div class="mt-2 text-sm font-medium text-slate-900">
                                    {{ $currentWorkPatternAssignment->workPattern->work_pattern_name ?? '—' }}
                                </div>
                                <div class="mt-1 text-xs text-slate-500">
                                    {{ $currentWorkPatternAssignment->workPattern->work_pattern_code ?? '—' }}
                                </div>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                                <div>
                                    <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Effective Start</div>
                                    <div class="mt-2 text-sm font-medium text-slate-900">
                                        {{ optional($currentWorkPatternAssignment->effective_start_date)->format('Y-m-d') ?? '—' }}
                                    </div>
                                </div>

                                <div>
                                    <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Effective End</div>
                                    <div class="mt-2 text-sm font-medium text-slate-900">
                                        {{ optional($currentWorkPatternAssignment->effective_end_date)->format('Y-m-d') ?? 'Open End' }}
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200">
                                    Current
                                </span>

                                <x-ui.button
                                    type="button"
                                    size="sm"
                                    variant="ghost"
                                    onclick="window.location='{{ route('scheduling.employee-work-pattern-assignments.show', $currentWorkPatternAssignment->employee_work_pattern_assignment_id) }}'"
                                >
                                    Open Work Pattern Assignment
                                </x-ui.button>
                            </div>
                        </div>
                    @else
                        <x-ui.empty-state
                            title="No current work pattern assignment"
                            description="Belum ada work pattern assignment aktif untuk employee ini pada tanggal hari ini."
                        />
                    @endif
                </x-ui.page-section>
            </div>

            <x-ui.page-section
                title="Recent Assignment History"
                subtitle="Beberapa histori assignment terbaru untuk employee ini."
            >
                <x-ui.table-shell>
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50/80">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Organization
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
                            @forelse($recentAssignments as $assignment)
                                @php
                                    $rowStatus = 'current';

                                    if ($assignment->effective_start_date?->format('Y-m-d') > $today) {
                                        $rowStatus = 'upcoming';
                                    } elseif ($assignment->effective_end_date && $assignment->effective_end_date->format('Y-m-d') < $today) {
                                        $rowStatus = 'historical';
                                    }
                                @endphp

                                <tr class="transition hover:bg-slate-50">
                                    <td class="px-5 py-4">
                                        <div class="font-medium text-slate-900">
                                            {{ $assignment->branch->branch_name ?? '—' }}
                                        </div>
                                        <div class="mt-1 text-xs text-slate-500">
                                            {{ $assignment->department->dept_name ?? '—' }} · {{ $assignment->role->role_name ?? '—' }}
                                        </div>
                                    </td>

                                    <td class="px-5 py-4 text-slate-600">
                                        <div>{{ optional($assignment->effective_start_date)->format('Y-m-d') ?? '—' }}</div>
                                        <div class="mt-1 text-xs text-slate-500">
                                            s/d {{ optional($assignment->effective_end_date)->format('Y-m-d') ?? 'Open End' }}
                                        </div>
                                    </td>

                                    <td class="px-5 py-4">
                                        <div class="flex flex-wrap items-center gap-2">
                                            @if($rowStatus === 'current')
                                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200">
                                                    Current
                                                </span>
                                            @elseif($rowStatus === 'upcoming')
                                                <span class="inline-flex items-center rounded-full bg-sky-100 px-2.5 py-1 text-xs font-medium text-sky-700 ring-1 ring-sky-200">
                                                    Upcoming
                                                </span>
                                            @else
                                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700 ring-1 ring-slate-200">
                                                    Historical
                                                </span>
                                            @endif

                                            @if($assignment->is_primary)
                                                <span class="inline-flex items-center rounded-full bg-violet-100 px-2.5 py-1 text-xs font-medium text-violet-700 ring-1 ring-violet-200">
                                                    Primary
                                                </span>
                                            @endif
                                        </div>
                                    </td>

                                    <td class="px-5 py-4">
                                        <div class="flex justify-end">
                                            <x-ui.button
                                                type="button"
                                                size="sm"
                                                variant="ghost"
                                                onclick="window.location='{{ route('master.employee-assignments.show', $assignment->assignment_id) }}'"
                                            >
                                                View
                                            </x-ui.button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-14">
                                        <x-ui.empty-state
                                            title="No assignment history"
                                            description="Belum ada histori assignment untuk employee ini."
                                        />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </x-ui.table-shell>
            </x-ui.page-section>
        </div>

        <div class="space-y-6">
            <x-ui.page-section
                title="Quick Assignment Actions"
                subtitle="Akses cepat ke modul assignment terkait employee ini."
            >
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                    <button
                        type="button"
                        onclick="window.location='{{ route('master.employee-assignments.index', ['emp_id' => $employee->emp_id]) }}'"
                        class="flex w-full items-start gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-4 text-left transition hover:border-slate-300 hover:bg-slate-50"
                    >
                        <div class="mt-0.5 h-9 w-9 rounded-xl bg-slate-100"></div>
                        <div>
                            <div class="text-sm font-semibold text-slate-900">Open Assignment Directory</div>
                            <div class="mt-1 text-xs text-slate-500">Lihat daftar assignment organisasi employee ini.</div>
                        </div>
                    </button>

                    @if(auth()->user()->hasPermission('shift.view'))
                        <button
                            type="button"
                            onclick="window.location='{{ route('scheduling.employee-shift-assignments.index', ['q' => $employee->emp_code]) }}'"
                            class="flex w-full items-start gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-4 text-left transition hover:border-slate-300 hover:bg-slate-50"
                        >
                            <div class="mt-0.5 h-9 w-9 rounded-xl bg-sky-100"></div>
                            <div>
                                <div class="text-sm font-semibold text-slate-900">Open Shift Assignment Directory</div>
                                <div class="mt-1 text-xs text-slate-500">Lihat histori dan assignment shift yang terkait.</div>
                            </div>
                        </button>
                    @endif

                    @if(auth()->user()->hasPermission('workpattern.view'))
                        <button
                            type="button"
                            onclick="window.location='{{ route('scheduling.employee-work-pattern-assignments.index', ['q' => $employee->emp_code]) }}'"
                            class="flex w-full items-start gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-4 text-left transition hover:border-slate-300 hover:bg-slate-50"
                        >
                            <div class="mt-0.5 h-9 w-9 rounded-xl bg-violet-100"></div>
                            <div>
                                <div class="text-sm font-semibold text-slate-900">Open Work Pattern Assignment Directory</div>
                                <div class="mt-1 text-xs text-slate-500">Lihat histori dan assignment work pattern aktif.</div>
                            </div>
                        </button>
                    @endif

                    @if(auth()->user()->hasPermission('assignment.manage'))
                        <button
                            type="button"
                            onclick="window.location='{{ route('master.employee-assignments.create', ['emp_id' => $employee->emp_id]) }}'"
                            class="flex w-full items-start gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-4 text-left transition hover:border-slate-300 hover:bg-slate-50"
                        >
                            <div class="mt-0.5 h-9 w-9 rounded-xl bg-emerald-100"></div>
                            <div>
                                <div class="text-sm font-semibold text-slate-900">Add Assignment</div>
                                <div class="mt-1 text-xs text-slate-500">Tambahkan assignment organisasi baru untuk employee ini.</div>
                            </div>
                        </button>
                    @endif

                    @if(auth()->user()->hasPermission('shift.manage'))
                        <button
                            type="button"
                            onclick="window.location='{{ route('scheduling.employee-shift-assignments.create', ['emp_id' => $employee->emp_id]) }}'"
                            class="flex w-full items-start gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-4 text-left transition hover:border-slate-300 hover:bg-slate-50"
                        >
                            <div class="mt-0.5 h-9 w-9 rounded-xl bg-emerald-100"></div>
                            <div>
                                <div class="text-sm font-semibold text-slate-900">Add Shift Assignment</div>
                                <div class="mt-1 text-xs text-slate-500">Tambahkan shift assignment baru untuk employee ini.</div>
                            </div>
                        </button>
                    @endif

                    @if(auth()->user()->hasPermission('workpattern.manage'))
                        <button
                            type="button"
                            onclick="window.location='{{ route('scheduling.employee-work-pattern-assignments.create', ['emp_id' => $employee->emp_id]) }}'"
                            class="flex w-full items-start gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-4 text-left transition hover:border-slate-300 hover:bg-slate-50"
                        >
                            <div class="mt-0.5 h-9 w-9 rounded-xl bg-emerald-100"></div>
                            <div>
                                <div class="text-sm font-semibold text-slate-900">Add Work Pattern Assignment</div>
                                <div class="mt-1 text-xs text-slate-500">Tambahkan work pattern assignment baru untuk employee ini.</div>
                            </div>
                        </button>
                    @endif
                </div>
            </x-ui.page-section>

            <x-ui.page-section
                title="Notes"
                subtitle="Catatan tambahan employee."
            >
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
                    {{ $employee->notes ?: 'Tidak ada catatan tambahan.' }}
                </div>
            </x-ui.page-section>
        </div>
    </div>
</div>
@endsection