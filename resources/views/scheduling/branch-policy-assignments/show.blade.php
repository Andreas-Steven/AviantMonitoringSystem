@extends('layouts.app')

@section('content')
<div class="mb-6 flex items-start justify-between">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight">Branch Policy Assignment Detail</h1>
        <p class="mt-1 text-sm text-slate-500">Detail assignment policy ke branch</p>
    </div>

    @if(auth()->user()->hasPermission('policy.manage'))
        <a href="{{ route('scheduling.branch-policy-assignments.edit', $assignment->branch_policy_assignment_id) }}"
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
                <dt class="text-slate-500">Branch</dt>
                <dd class="mt-1 font-medium">{{ $assignment->branch->branch_name ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Policy</dt>
                <dd class="mt-1 font-medium">{{ $assignment->policy->policy_name ?? '-' }} ({{ $assignment->policy->policy_code ?? '-' }})</dd>
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