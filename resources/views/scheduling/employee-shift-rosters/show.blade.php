@extends('layouts.app')

@section('content')
<div class="mb-6 flex items-start justify-between">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight">Employee Shift Roster Detail</h1>
        <p class="mt-1 text-sm text-slate-500">Detail roster harian employee</p>
    </div>

    @if(auth()->user()->hasPermission('roster.manage'))
        <a href="{{ route('scheduling.employee-shift-rosters.edit', $roster->roster_id) }}"
           class="rounded-2xl border border-slate-300 px-4 py-2.5 text-sm font-medium hover:bg-slate-50">
            Edit
        </a>
    @endif
</div>

<div class="grid gap-6 lg:grid-cols-2">
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold">Roster Information</h2>

        <dl class="mt-4 space-y-4 text-sm">
            <div>
                <dt class="text-slate-500">Employee</dt>
                <dd class="mt-1 font-medium">{{ $roster->employee->full_name ?? '-' }} ({{ $roster->employee->emp_code ?? '-' }})</dd>
            </div>
            <div>
                <dt class="text-slate-500">Work Date</dt>
                <dd class="mt-1 font-medium">{{ optional($roster->work_date)->format('Y-m-d') }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Shift</dt>
                <dd class="mt-1 font-medium">{{ $roster->shift->shift_name ?? '-' }} ({{ $roster->shift->shift_code ?? '-' }})</dd>
            </div>
            <div>
                <dt class="text-slate-500">Source Type</dt>
                <dd class="mt-1 font-medium">{{ $roster->sourceType->source_type_name ?? $roster->source_type_code }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Source Ref ID</dt>
                <dd class="mt-1 font-medium">{{ $roster->source_ref_id ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Published At</dt>
                <dd class="mt-1 font-medium">{{ optional($roster->published_at)->format('Y-m-d H:i') ?? '-' }}</dd>
            </div>
        </dl>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold">Notes</h2>
        <div class="mt-4 text-sm text-slate-700">
            {{ $roster->notes ?: '-' }}
        </div>
    </div>
</div>
@endsection