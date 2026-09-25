@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Attendance Exceptions"
        subtitle="Kelola override, dispensasi, dan koreksi exception absensi."
        :breadcrumbs="[
            ['label' => 'Attendance'],
            ['label' => 'Attendance Exceptions'],
        ]"
    >
        <x-slot:actions>
            @if(auth()->user()->hasPermission('attendance_exception.manage'))
                <x-ui.button
                    variant="primary"
                    onclick="window.location='{{ route('attendance.attendance-exceptions.create') }}'"
                >
                    Add Exception
                </x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.page-section
        title="Exception Directory"
        subtitle="Filter berdasarkan employee, type, status override, source, dan range tanggal kerja."
    >
        <form method="GET" class="grid gap-4 lg:grid-cols-[1.2fr_1fr_1fr_1fr_180px_180px_auto]">
            <x-ui.field label="Search">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari emp code / nama" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
            </x-ui.field>

            <x-ui.field label="Exception Type">
                <select name="exception_type_code" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                    <option value="">Semua type</option>
                    @foreach($exceptionTypes as $type)
                        <option value="{{ $type->exception_type_code }}" @selected(request('exception_type_code') === $type->exception_type_code)>
                            {{ $type->exception_type_name }}
                        </option>
                    @endforeach
                </select>
            </x-ui.field>

            <x-ui.field label="Status Override">
                <select name="status_value_code" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                    <option value="">Semua status</option>
                    @foreach($attendanceStatuses as $status)
                        <option value="{{ $status->attendance_status_code }}" @selected(request('status_value_code') === $status->attendance_status_code)>
                            {{ $status->attendance_status_name }}
                        </option>
                    @endforeach
                </select>
            </x-ui.field>

            <x-ui.field label="Source Type">
                <select name="source_type_code" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                    <option value="">Semua source</option>
                    @foreach($sourceTypes as $sourceType)
                        <option value="{{ $sourceType->source_type_code }}" @selected(request('source_type_code') === $sourceType->source_type_code)>
                            {{ $sourceType->source_type_name }}
                        </option>
                    @endforeach
                </select>
            </x-ui.field>

            <x-ui.field label="Date From">
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
            </x-ui.field>

            <x-ui.field label="Date To">
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
            </x-ui.field>

            <div class="flex items-end gap-3">
                <x-ui.button type="submit">Search</x-ui.button>
                <x-ui.button type="button" variant="ghost" onclick="window.location='{{ route('attendance.attendance-exceptions.index') }}'">Reset</x-ui.button>
            </div>
        </form>
    </x-ui.page-section>

    <x-ui.table-shell>
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <x-ui.stat-card label="Rows" :value="$summaryStats['total_rows']" hint="Total exception hasil filter" />
            <x-ui.stat-card label="Approved" :value="$summaryStats['approved_rows']" hint="approved_at terisi" />
            <x-ui.stat-card label="Manual In/Out" :value="$summaryStats['manual_rows']" hint="MANUAL_IN + MANUAL_OUT" />
            <x-ui.stat-card label="Forgot Approval" :value="$summaryStats['forgot_rows']" hint="Forgot checkin/checkout approval" />
            <x-ui.stat-card label="Overrides" :value="$summaryStats['override_rows']" hint="Force / shift / OT override" />
        </div>
                
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50/80">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Employee</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Work Date</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Exception</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Override Value</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Approval</th>
                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse($exceptions as $exception)
                    <tr class="transition hover:bg-slate-50">
                        <td class="px-5 py-4">
                            <div class="font-medium text-slate-900">{{ $exception->employee->full_name ?? '-' }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ $exception->employee->emp_code ?? '-' }}</div>
                        </td>

                        <td class="px-5 py-4 text-slate-600">
                            {{ optional($exception->work_date)->format('Y-m-d') }}
                        </td>

                        <td class="px-5 py-4">
                            @php
                                $badgeMap = [
                                    'LATE_DISPENSATION' => 'bg-amber-100 text-amber-800 ring-amber-200',
                                    'EARLY_OUT_DISPENSATION' => 'bg-orange-100 text-orange-800 ring-orange-200',
                                    'FORCE_PRESENT' => 'bg-emerald-100 text-emerald-800 ring-emerald-200',
                                    'FORCE_ABSENT' => 'bg-rose-100 text-rose-800 ring-rose-200',
                                    'SHIFT_OVERRIDE' => 'bg-sky-100 text-sky-800 ring-sky-200',
                                    'FORGOT_CHECKIN_APPROVAL' => 'bg-violet-100 text-violet-800 ring-violet-200',
                                    'FORGOT_CHECKOUT_APPROVAL' => 'bg-fuchsia-100 text-fuchsia-800 ring-fuchsia-200',
                                    'MANUAL_IN' => 'bg-cyan-100 text-cyan-800 ring-cyan-200',
                                    'MANUAL_OUT' => 'bg-indigo-100 text-indigo-800 ring-indigo-200',
                                    'OVERTIME_OVERRIDE' => 'bg-lime-100 text-lime-800 ring-lime-200',
                                ];

                                $badgeClass = $badgeMap[$exception->exception_type_code] ?? 'bg-slate-100 text-slate-700 ring-slate-200';
                            @endphp

                            <div>
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $badgeClass }}">
                                    {{ $exception->exception_type_code }}
                                </span>
                            </div>
                            <div class="mt-2 text-xs text-slate-500">{{ $exception->source_type_code ?: '-' }}</div>
                        </td>

                        <td class="px-5 py-4 text-slate-600">
                            <div class="flex flex-wrap gap-2">
                                @if(!is_null($exception->minutes_value))
                                    <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-200">
                                        Minutes: {{ $exception->minutes_value }}
                                    </span>
                                @endif

                                @if($exception->time_value)
                                    <span class="inline-flex items-center rounded-full bg-cyan-50 px-2.5 py-1 text-xs font-medium text-cyan-700 ring-1 ring-inset ring-cyan-200">
                                        Time: {{ optional($exception->time_value)->format('Y-m-d H:i') }}
                                    </span>
                                @endif

                                @if($exception->shift)
                                    <span class="inline-flex items-center rounded-full bg-sky-50 px-2.5 py-1 text-xs font-medium text-sky-700 ring-1 ring-inset ring-sky-200">
                                        Shift: {{ $exception->shift->shift_code }}
                                    </span>
                                @endif

                                @if($exception->status_value_code)
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                        Status: {{ $exception->status_value_code }}
                                    </span>
                                @endif

                                @if(
                                    is_null($exception->minutes_value) &&
                                    !$exception->time_value &&
                                    !$exception->shift &&
                                    !$exception->status_value_code
                                )
                                    <span class="text-xs text-slate-400">No override value</span>
                                @endif
                            </div>
                        </td>

                        <td class="px-5 py-4 text-slate-600">
                            <div>{{ $exception->approver->full_name ?? '-' }}</div>
                            <div class="mt-1 text-xs text-slate-500">
                                {{ optional($exception->approved_at)->format('Y-m-d H:i') ?: 'Belum ada approval time' }}
                            </div>
                        </td>

                        <td class="px-5 py-4">
                            <div class="flex justify-end gap-2">
                                <x-ui.button
                                    
                                    variant="ghost"
                                    onclick="window.location='{{ route('attendance.attendance-exceptions.show', $exception->attendance_exception_id) }}'"
                                >
                                    Detail
                                </x-ui.button>

                                @if(auth()->user()->hasPermission('attendance_exception.manage'))
                                    <x-ui.button
                                        size="sm"
                                        onclick="window.location='{{ route('attendance.attendance-exceptions.edit', $exception->attendance_exception_id) }}'"
                                    >
                                        Edit
                                    </x-ui.button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-14">
                            <x-ui.empty-state
                                title="No attendance exceptions found"
                                description="Belum ada data attendance exception."
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($exceptions->hasPages())
            <div class="border-t border-slate-200 bg-white px-5 py-4">
                {{ $exceptions->withQueryString()->links() }}
            </div>
        @endif
    </x-ui.table-shell>
</div>
@endsection