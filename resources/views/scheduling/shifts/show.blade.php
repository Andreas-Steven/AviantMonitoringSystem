@extends('layouts.app')

@section('content')
<div class="mb-6 flex items-start justify-between">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight">{{ $shift->shift_name }}</h1>
        <p class="mt-1 text-sm text-slate-500">Detail shift</p>
    </div>

    @if(auth()->user()->hasPermission('shift.manage'))
        <a href="{{ route('scheduling.shifts.edit', $shift->shift_id) }}"
           class="rounded-2xl border border-slate-300 px-4 py-2.5 text-sm font-medium hover:bg-slate-50">
            Edit
        </a>
    @endif
</div>

<div class="grid gap-6 lg:grid-cols-2">
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold">Informasi Shift</h2>

        <dl class="mt-4 space-y-4 text-sm">
            <div>
                <dt class="text-slate-500">Shift Code</dt>
                <dd class="mt-1 font-medium">{{ $shift->shift_code }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Shift Name</dt>
                <dd class="mt-1 font-medium">{{ $shift->shift_name }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Start Time</dt>
                <dd class="mt-1 font-medium">{{ substr($shift->start_time, 0, 5) }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">End Time</dt>
                <dd class="mt-1 font-medium">{{ substr($shift->end_time, 0, 5) }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Break Minutes</dt>
                <dd class="mt-1 font-medium">{{ $shift->break_min }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Default Work Minutes</dt>
                <dd class="mt-1 font-medium">{{ $shift->default_work_min }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Cross Day</dt>
                <dd class="mt-1 font-medium">{{ $shift->cross_day_flag ? 'Yes' : 'No' }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Status</dt>
                <dd class="mt-1 font-medium">{{ $shift->active ? 'Active' : 'Inactive' }}</dd>
            </div>
        </dl>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold">Notes</h2>
        <div class="mt-4 text-sm text-slate-700">
            {{ $shift->notes ?: '-' }}
        </div>
    </div>
</div>
@endsection