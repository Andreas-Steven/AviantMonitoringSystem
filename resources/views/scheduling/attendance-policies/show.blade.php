@extends('layouts.app')

@section('content')
<div class="mb-6 flex items-start justify-between">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight">{{ $policy->policy_name }}</h1>
        <p class="mt-1 text-sm text-slate-500">Detail attendance policy</p>
    </div>

    @if(auth()->user()->hasPermission('policy.manage'))
        <a href="{{ route('scheduling.attendance-policies.edit', $policy->policy_id) }}"
           class="rounded-2xl border border-slate-300 px-4 py-2.5 text-sm font-medium hover:bg-slate-50">
            Edit
        </a>
    @endif
</div>

<div class="grid gap-6 lg:grid-cols-2">
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold">Policy Configuration</h2>

        <dl class="mt-4 space-y-4 text-sm">
            <div>
                <dt class="text-slate-500">Policy Code</dt>
                <dd class="mt-1 font-medium">{{ $policy->policy_code }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Late Grace In</dt>
                <dd class="mt-1 font-medium">{{ $policy->late_grace_in_min }} min</dd>
            </div>
            <div>
                <dt class="text-slate-500">Early Out Grace</dt>
                <dd class="mt-1 font-medium">{{ $policy->early_out_grace_min }} min</dd>
            </div>
            <div>
                <dt class="text-slate-500">Min Work Half Day</dt>
                <dd class="mt-1 font-medium">{{ $policy->min_work_min_half_day }} min</dd>
            </div>
            <div>
                <dt class="text-slate-500">Min Work Full Day</dt>
                <dd class="mt-1 font-medium">{{ $policy->min_work_min_full_day }} min</dd>
            </div>
            <div>
                <dt class="text-slate-500">Overtime Before</dt>
                <dd class="mt-1 font-medium">{{ $policy->overtime_min_before }} min</dd>
            </div>
            <div>
                <dt class="text-slate-500">Rounding Mode</dt>
                <dd class="mt-1 font-medium">{{ $policy->overtime_rounding_mode_code }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Rounding Unit</dt>
                <dd class="mt-1 font-medium">{{ $policy->overtime_rounding_unit_min }} min</dd>
            </div>
            <div>
                <dt class="text-slate-500">Double Tap Window</dt>
                <dd class="mt-1 font-medium">{{ $policy->double_tap_window_min }} min</dd>
            </div>
            <div>
                <dt class="text-slate-500">Max Pair Gap Hour</dt>
                <dd class="mt-1 font-medium">{{ $policy->max_pair_gap_hour }} hour</dd>
            </div>
        </dl>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold">Handling & Status</h2>

        <dl class="mt-4 space-y-4 text-sm">
            <div>
                <dt class="text-slate-500">Missing In Policy</dt>
                <dd class="mt-1 font-medium">{{ $policy->missing_in_policy_code }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Missing Out Policy</dt>
                <dd class="mt-1 font-medium">{{ $policy->missing_out_policy_code }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Status</dt>
                <dd class="mt-1 font-medium">{{ $policy->active ? 'Active' : 'Inactive' }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Notes</dt>
                <dd class="mt-1 font-medium">{{ $policy->notes ?: '-' }}</dd>
            </div>
        </dl>
    </div>
</div>
@endsection