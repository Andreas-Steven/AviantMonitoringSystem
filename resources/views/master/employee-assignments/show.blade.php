@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Assignment Detail"
        subtitle="Detail assignment organisasi karyawan dan masa berlakunya."
        :breadcrumbs="[
            ['label' => 'Master'],
            ['label' => 'Employee Assignments', 'url' => route('master.employee-assignments.index')],
            ['label' => 'Detail'],
        ]"
    >
        <x-slot:actions>
            <div class="flex items-center gap-3">
                <x-ui.button
                    type="button"
                    variant="ghost"
                    onclick="window.location='{{ route('master.employee-assignments.index') }}'"
                >
                    Back
                </x-ui.button>

                <x-ui.button
                    type="button"
                    variant="ghost"
                    onclick="window.location='{{ route('master.employees.show', $assignment->emp_id) }}'"
                >
                    View Employee
                </x-ui.button>

                @if(auth()->user()->hasPermission('assignment.manage'))
                    <x-ui.button
                        type="button"
                        onclick="window.location='{{ route('master.employee-assignments.edit', $assignment->assignment_id) }}'"
                    >
                        Edit
                    </x-ui.button>
                @endif
            </div>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <x-ui.page-section title="Assignment Snapshot" subtitle="Ringkasan utama assignment yang dipilih.">
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Employee</div>
                        <div class="mt-2 text-sm font-semibold text-slate-900">
                            {{ $assignment->employee->full_name ?? '—' }}
                        </div>
                        <div class="mt-1 text-xs text-slate-500">
                            {{ $assignment->employee->emp_code ?? '—' }}
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Assignment Status</div>
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            @if($status === 'current')
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200">
                                    Current
                                </span>
                            @elseif($status === 'upcoming')
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
                            @else
                                <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-700 ring-1 ring-amber-200">
                                    Secondary
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    <div>
                        <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Branch</div>
                        <div class="mt-2 text-sm font-medium text-slate-900">
                            {{ $assignment->branch->branch_name ?? '—' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Department</div>
                        <div class="mt-2 text-sm font-medium text-slate-900">
                            {{ $assignment->department->dept_name ?? '—' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Role</div>
                        <div class="mt-2 text-sm font-medium text-slate-900">
                            {{ $assignment->role->role_name ?? '—' }}
                        </div>
                        <div class="mt-1 text-xs text-slate-500">
                            {{ $assignment->role->department->dept_name ?? '—' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Grade</div>
                        <div class="mt-2 text-sm font-medium text-slate-900">
                            {{ $assignment->grade->grade_name ?? '—' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Effective Start</div>
                        <div class="mt-2 text-sm font-medium text-slate-900">
                            {{ optional($assignment->effective_start_date)->format('Y-m-d') ?? '—' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Effective End</div>
                        <div class="mt-2 text-sm font-medium text-slate-900">
                            {{ optional($assignment->effective_end_date)->format('Y-m-d') ?? 'Open End' }}
                        </div>
                    </div>
                </div>
            </x-ui.page-section>
        </div>

        <div class="space-y-6">
            <x-ui.page-section title="Notes" subtitle="Catatan tambahan untuk assignment ini.">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
                    {{ $assignment->notes ?: 'Tidak ada catatan tambahan.' }}
                </div>
            </x-ui.page-section>
        </div>
    </div>
</div>
@endsection