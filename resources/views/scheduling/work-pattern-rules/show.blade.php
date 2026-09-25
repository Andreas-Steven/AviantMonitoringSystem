@extends('layouts.app')

@section('content')
<div class="mb-6 flex items-start justify-between">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight">{{ $rule->rule_name }}</h1>
        <p class="mt-1 text-sm text-slate-500">Detail work pattern rule</p>
    </div>

    @if(auth()->user()->hasPermission('workpattern.manage'))
        <a href="{{ route('scheduling.work-pattern-rules.edit', $rule->work_pattern_rule_id) }}"
           class="rounded-2xl border border-slate-300 px-4 py-2.5 text-sm font-medium hover:bg-slate-50">
            Edit
        </a>
    @endif
</div>

<div class="grid gap-6 lg:grid-cols-2">
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold">Rule Information</h2>

        <dl class="mt-4 space-y-4 text-sm">
            <div><dt class="text-slate-500">Work Pattern</dt><dd class="mt-1 font-medium">{{ $rule->workPattern->work_pattern_name ?? '-' }}</dd></div>
            <div><dt class="text-slate-500">Rule Code</dt><dd class="mt-1 font-medium">{{ $rule->rule_code }}</dd></div>
            <div><dt class="text-slate-500">Rule Type</dt><dd class="mt-1 font-medium">{{ $rule->ruleType->rule_type_name ?? $rule->rule_type_code }}</dd></div>
            <div><dt class="text-slate-500">Day of Week</dt><dd class="mt-1 font-medium">{{ $rule->dayOfWeek->day_of_week_name ?? '-' }}</dd></div>
            <div><dt class="text-slate-500">Shift</dt><dd class="mt-1 font-medium">{{ $rule->shift->shift_name ?? '-' }}</dd></div>
            <div><dt class="text-slate-500">Target Count / Period</dt><dd class="mt-1 font-medium">{{ $rule->target_count_per_period ?? '-' }}</dd></div>
            <div><dt class="text-slate-500">Priority</dt><dd class="mt-1 font-medium">{{ $rule->priority_order }}</dd></div>
            <div><dt class="text-slate-500">Status</dt><dd class="mt-1 font-medium">{{ $rule->active ? 'Active' : 'Inactive' }}</dd></div>
        </dl>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold">Behavior</h2>

        <dl class="mt-4 space-y-4 text-sm">
            <div><dt class="text-slate-500">Holiday Wins</dt><dd class="mt-1 font-medium">{{ $rule->holiday_wins_flag ? 'Yes' : 'No' }}</dd></div>
            <div><dt class="text-slate-500">Holiday Scope</dt><dd class="mt-1 font-medium">{{ $rule->holidayScopeMode->holiday_scope_mode_name ?? '-' }}</dd></div>
            <div><dt class="text-slate-500">Substitution Allowed</dt><dd class="mt-1 font-medium">{{ $rule->substitution_allowed_flag ? 'Yes' : 'No' }}</dd></div>
            <div><dt class="text-slate-500">Excess Treatment</dt><dd class="mt-1 font-medium">{{ $rule->excessTreatmentMode->excess_treatment_mode_name ?? '-' }}</dd></div>
            <div><dt class="text-slate-500">Deficit Treatment</dt><dd class="mt-1 font-medium">{{ $rule->deficitTreatmentMode->deficit_treatment_mode_name ?? '-' }}</dd></div>
            <div><dt class="text-slate-500">Notes</dt><dd class="mt-1 font-medium">{{ $rule->notes ?: '-' }}</dd></div>
        </dl>
    </div>
</div>
@endsection