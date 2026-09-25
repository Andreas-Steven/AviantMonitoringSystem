@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Attendance Exception Detail"
        subtitle="Review detail override dan approval exception absensi."
        :breadcrumbs="[
            ['label' => 'Attendance'],
            ['label' => 'Attendance Exceptions'],
            ['label' => 'Detail'],
        ]"
    >
        <x-slot:actions>
            <div class="flex flex-wrap items-center gap-2">
                @if($daily)
                    <x-ui.button
                        variant="ghost"
                        onclick="window.location='{{ route('attendance.daily.show', $daily->attendance_daily_id) }}'"
                    >
                        Open Daily
                    </x-ui.button>
                @endif

                @if(auth()->user()->hasPermission('attendance_exception.manage'))
                    <x-ui.button
                        variant="primary"
                        onclick="window.location='{{ route('attendance.attendance-exceptions.edit', $attendanceException->attendance_exception_id) }}'"
                    >
                        Edit
                    </x-ui.button>
                @endif
            </div>
        </x-slot:actions>
    </x-ui.page-header>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <x-ui.page-section title="Exception Detail" subtitle="Attendance exception snapshot.">
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <div class="text-xs text-slate-500">Employee</div>
                        <div class="mt-1 font-medium text-slate-900">{{ $attendanceException->employee->full_name ?? '-' }}</div>
                    </div>

                    <div>
                        <div class="text-xs text-slate-500">Employee Code</div>
                        <div class="mt-1 font-medium text-slate-900">{{ $attendanceException->employee->emp_code ?? '-' }}</div>
                    </div>

                    <div>
                        <div class="text-xs text-slate-500">Work Date</div>
                        <div class="mt-1 font-medium text-slate-900">{{ optional($attendanceException->work_date)->format('Y-m-d') }}</div>
                    </div>

                    <div>
                        <div class="text-xs text-slate-500">Exception Type</div>
                        <div class="mt-2">
                            <x-ui.status-badge :label="$attendanceException->exception_type_code" />
                        </div>
                    </div>

                    <div>
                        <div class="text-xs text-slate-500">Minutes Value</div>
                        <div class="mt-1 font-medium text-slate-900">{{ $attendanceException->minutes_value ?? '-' }}</div>
                    </div>

                    <div>
                        <div class="text-xs text-slate-500">Time Value</div>
                        <div class="mt-1 font-medium text-slate-900">{{ optional($attendanceException->time_value)->format('Y-m-d H:i') ?: '-' }}</div>
                    </div>

                    <div>
                        <div class="text-xs text-slate-500">Shift Override</div>
                        <div class="mt-1 font-medium text-slate-900">
                            {{ $attendanceException->shift?->shift_name
                                ? $attendanceException->shift->shift_name.' ('.$attendanceException->shift->shift_code.')'
                                : '-' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs text-slate-500">Status Override</div>
                        <div class="mt-1 font-medium text-slate-900">{{ $attendanceException->status_value_code ?: '-' }}</div>
                    </div>

                    <div>
                        <div class="text-xs text-slate-500">Source Type</div>
                        <div class="mt-1 font-medium text-slate-900">{{ $attendanceException->source_type_code ?: '-' }}</div>
                    </div>

                    <div>
                        <div class="text-xs text-slate-500">Source Ref ID</div>
                        <div class="mt-1 font-medium text-slate-900">{{ $attendanceException->source_ref_id ?: '-' }}</div>
                    </div>

                    <div>
                        <div class="text-xs text-slate-500">Approved By</div>
                        <div class="mt-1 font-medium text-slate-900">{{ $attendanceException->approver->full_name ?? '-' }}</div>
                    </div>

                    <div>
                        <div class="text-xs text-slate-500">Approved At</div>
                        <div class="mt-1 font-medium text-slate-900">{{ optional($attendanceException->approved_at)->format('Y-m-d H:i') ?: '-' }}</div>
                    </div>

                    <div class="md:col-span-2">
                        <div class="text-xs text-slate-500">Reason</div>
                        <div class="mt-1 font-medium text-slate-900">{{ $attendanceException->reason ?: '-' }}</div>
                    </div>

                    <div class="md:col-span-2">
                        <div class="text-xs text-slate-500">Notes</div>
                        <div class="mt-1 font-medium text-slate-900">{{ $attendanceException->notes ?: '-' }}</div>
                    </div>
                </div>
            </x-ui.page-section>

            <x-ui.page-section title="Related Daily Snapshot" subtitle="Kondisi attendance_daily pada tanggal yang sama.">
                @if($daily)
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Branch</div>
                            <div class="mt-1 text-sm font-medium text-slate-900">{{ $daily->branch?->branch_name ?? '-' }}</div>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Shift</div>
                            <div class="mt-1 text-sm font-medium text-slate-900">{{ $daily->shift?->shift_name ?? $daily->shift?->shift_code ?? '-' }}</div>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Status</div>
                            <div class="mt-2">
                                <x-ui.status-badge :label="$daily->attendance_status_code" />
                            </div>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Review Reason</div>
                            <div class="mt-1 text-sm font-medium text-slate-900">{{ $daily->review_reason_code ?: '-' }}</div>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Actual</div>
                            <div class="mt-1 text-sm font-medium text-slate-900">
                                {{ optional($daily->actual_in_datetime)->format('H:i') ?: '--:--' }}
                                —
                                {{ optional($daily->actual_out_datetime)->format('H:i') ?: '--:--' }}
                            </div>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Work / Late / OT</div>
                            <div class="mt-1 text-sm font-medium text-slate-900">
                                {{ (int) $daily->work_min }} / {{ (int) $daily->late_min }} / {{ (int) $daily->overtime_min }} min
                            </div>
                        </div>
                    </div>
                @else
                    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-sm text-slate-500">
                        Tidak ada attendance_daily terkait.
                    </div>
                @endif
            </x-ui.page-section>
        </div>

        <div class="space-y-6">
            <x-ui.page-section title="Related Review Cases" subtitle="Review case untuk employee dan tanggal yang sama.">
                @if($reviewCases->isEmpty())
                    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-sm text-slate-500">
                        Tidak ada review case terkait.
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($reviewCases as $case)
                            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                <div class="flex flex-wrap gap-2">
                                    <x-ui.status-badge :label="$case->case_type_code" />
                                    <x-ui.status-badge :label="$case->severity_code" :tone="match($case->severity_code) {
                                        'CRITICAL', 'HIGH' => 'danger',
                                        'MEDIUM' => 'warning',
                                        default => 'neutral',
                                    }" />
                                    <x-ui.status-badge :label="$case->review_status_code" :tone="in_array($case->review_status_code, ['OPEN', 'IN_REVIEW']) ? 'warning' : 'success'" />
                                </div>

                                <div class="mt-3 text-sm text-slate-600">
                                    {{ $case->notes ?: '-' }}
                                </div>

                                <div class="mt-3">
                                    <a
                                        href="{{ route('review.attendance-cases.show', $case->review_case_id) }}"
                                        class="text-sm font-medium text-sky-700 hover:text-sky-800"
                                    >
                                        Open case
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-ui.page-section>

            <x-ui.page-section title="Other Exceptions Same Date" subtitle="Exception lain untuk employee dan tanggal yang sama.">
                @if($sameDayExceptions->isEmpty())
                    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-sm text-slate-500">
                        Tidak ada exception lain pada tanggal ini.
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($sameDayExceptions as $item)
                            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="flex flex-wrap gap-2">
                                        <x-ui.status-badge :label="$item->exception_type_code" />
                                        @if($item->status_value_code)
                                            <x-ui.status-badge :label="$item->status_value_code" tone="info" />
                                        @endif
                                    </div>

                                    <a
                                        href="{{ route('attendance.attendance-exceptions.show', $item->attendance_exception_id) }}"
                                        class="text-sm font-medium text-sky-700 hover:text-sky-800"
                                    >
                                        Open
                                    </a>
                                </div>

                                <div class="mt-3 text-sm text-slate-600">
                                    {{ $item->reason ?: '-' }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-ui.page-section>
        </div>
    </div>
</div>
@endsection