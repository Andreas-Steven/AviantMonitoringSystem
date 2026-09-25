@extends('layouts.app')

@section('content')
<div class="mb-6 flex items-start justify-between">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight">{{ $workPattern->work_pattern_name }}</h1>
        <p class="mt-1 text-sm text-slate-500">Detail work pattern</p>
    </div>

    @if(auth()->user()->hasPermission('workpattern.manage'))
        <a href="{{ route('scheduling.work-patterns.edit', $workPattern->work_pattern_id) }}"
           class="rounded-2xl border border-slate-300 px-4 py-2.5 text-sm font-medium hover:bg-slate-50">
            Edit
        </a>
    @endif
</div>

<div class="grid gap-6 lg:grid-cols-2">
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold">Work Pattern Information</h2>

        <dl class="mt-4 space-y-4 text-sm">
            <div>
                <dt class="text-slate-500">Code</dt>
                <dd class="mt-1 font-medium">{{ $workPattern->work_pattern_code }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Name</dt>
                <dd class="mt-1 font-medium">{{ $workPattern->work_pattern_name }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Evaluation Mode</dt>
                <dd class="mt-1 font-medium">{{ $workPattern->evaluationMode->evaluation_mode_name ?? $workPattern->evaluation_mode_code }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Status</dt>
                <dd class="mt-1 font-medium">{{ $workPattern->active ? 'Active' : 'Inactive' }}</dd>
            </div>
        </dl>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold">Notes</h2>
        <div class="mt-4 text-sm text-slate-700">
            {{ $workPattern->notes ?: '-' }}
        </div>
    </div>
</div>
@endsection@extends('layouts.app')

@section('content')
<div class="mb-6 flex items-start justify-between">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight">{{ $workPattern->work_pattern_name }}</h1>
        <p class="mt-1 text-sm text-slate-500">Detail work pattern</p>
    </div>

    @if(auth()->user()->hasPermission('workpattern.manage'))
        <a href="{{ route('scheduling.work-patterns.edit', $workPattern->work_pattern_id) }}"
           class="rounded-2xl border border-slate-300 px-4 py-2.5 text-sm font-medium hover:bg-slate-50">
            Edit
        </a>
    @endif
</div>

<div class="grid gap-6 lg:grid-cols-2">
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold">Work Pattern Information</h2>

        <dl class="mt-4 space-y-4 text-sm">
            <div>
                <dt class="text-slate-500">Code</dt>
                <dd class="mt-1 font-medium">{{ $workPattern->work_pattern_code }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Name</dt>
                <dd class="mt-1 font-medium">{{ $workPattern->work_pattern_name }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Evaluation Mode</dt>
                <dd class="mt-1 font-medium">{{ $workPattern->evaluationMode->evaluation_mode_name ?? $workPattern->evaluation_mode_code }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Status</dt>
                <dd class="mt-1 font-medium">{{ $workPattern->active ? 'Active' : 'Inactive' }}</dd>
            </div>
        </dl>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold">Notes</h2>
        <div class="mt-4 text-sm text-slate-700">
            {{ $workPattern->notes ?: '-' }}
        </div>
    </div>
</div>
@endsection