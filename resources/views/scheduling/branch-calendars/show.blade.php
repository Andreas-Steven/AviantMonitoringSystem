@extends('layouts.app')

@section('content')
<div class="mb-6 flex items-start justify-between">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight">Branch Calendar Detail</h1>
        <p class="mt-1 text-sm text-slate-500">Detail kalender kerja branch</p>
    </div>

    @if(auth()->user()->hasPermission('calendar.manage'))
        <a href="{{ route('scheduling.branch-calendars.edit', $calendar->branch_calendar_id) }}"
           class="rounded-2xl border border-slate-300 px-4 py-2.5 text-sm font-medium hover:bg-slate-50">
            Edit
        </a>
    @endif
</div>

<div class="grid gap-6 lg:grid-cols-2">
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold">Calendar Information</h2>

        <dl class="mt-4 space-y-4 text-sm">
            <div>
                <dt class="text-slate-500">Branch</dt>
                <dd class="mt-1 font-medium">{{ $calendar->branch->branch_name ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Work Date</dt>
                <dd class="mt-1 font-medium">{{ optional($calendar->work_date)->format('Y-m-d') }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Day Type</dt>
                <dd class="mt-1 font-medium">{{ $calendar->dayType->day_type_name ?? $calendar->day_type_code }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Day Name</dt>
                <dd class="mt-1 font-medium">{{ $calendar->day_name ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Is Workday</dt>
                <dd class="mt-1 font-medium">{{ $calendar->is_workday ? 'Yes' : 'No' }}</dd>
            </div>
        </dl>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold">Notes</h2>
        <div class="mt-4 text-sm text-slate-700">
            {{ $calendar->notes ?: '-' }}
        </div>
    </div>
</div>
@endsection