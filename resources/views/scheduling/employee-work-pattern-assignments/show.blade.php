@extends('layouts.app')

@section('content')
<div class="mb-6 flex items-start justify-between">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight">Employee Work Pattern Assignment Detail</h1>
        <p class="mt-1 text-sm text-slate-500">Detail assignment work pattern per employee</p>
    </div>

    @if(auth()->user()->hasPermission('workpattern.manage'))
        <a href="{{ route('scheduling.employee-work-pattern-assignments.edit', $assignment->employee_work_pattern_assignment_id) }}"
           class="rounded-2xl border border-slate-300 px-4 py-2.5 text-sm font-medium hover:bg-slate-50">
            Edit
        </a>
    @endif
</div>

<div class="grid gap-6 lg:grid-cols-2">
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold">Assignment Information</h2>

        <dl class="mt-4 space-y-4 text-sm">
            <div>
                <dt class="text-slate-500">Employee</dt>
                <dd class="mt-1 font-medium">{{ $assignment->employee->full_name ?? '-' }} ({{ $assignment->employee->emp_code ?? '-' }})</dd>
            </div>
            <div>
                <dt class="text-slate-500">Work Pattern</dt>
                <dd class="mt-1 font-medium">{{ $assignment->workPattern->work_pattern_name ?? '-' }} ({{ $assignment->workPattern->work_pattern_code ?? '-' }})</dd>
            </div>
            <div>
                <dt class="text-slate-500">Effective Start</dt>
                <dd class="mt-1 font-medium">{{ optional($assignment->effective_start_date)->format('Y-m-d') }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Effective End</dt>
                <dd class="mt-1 font-medium">{{ optional($assignment->effective_end_date)->format('Y-m-d') ?? '-' }}</dd>
            </div>
        </dl>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold">Notes</h2>
        <div class="mt-4 text-sm text-slate-700">
            {{ $assignment->notes ?: '-' }}
        </div>
    </div>
</div>
@endsection