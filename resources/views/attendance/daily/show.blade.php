@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <x-ui.page-header
            title="Attendance Daily Detail"
            subtitle="Detail hasil attendance_daily beserta konteks normalized logs, raw logs, exceptions, dan review cases dalam window investigasi row ini."
            :breadcrumbs="[
                ['label' => 'Attendance'],
                ['label' => 'Attendance Daily', 'url' => route('attendance.daily.index')],
                ['label' => 'Detail'],
            ]"
        />

        <div class="flex flex-wrap items-center gap-3">
            <a
                href="{{ route('attendance.daily.index') }}"
                class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 shadow-sm transition hover:border-slate-300 hover:text-slate-900"
            >
                Back to List
            </a>

            @if(auth()->user()->hasPermission('attendance_normalized.view'))
                <a
                    href="{{ route('attendance.normalized-logs.index', [
                        'q' => $row->employee->emp_code ?? null,
                        'date_from' => optional($windowStart)->format('Y-m-d'),
                        'date_to' => optional($windowEnd)->format('Y-m-d'),
                    ]) }}"
                    class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 shadow-sm transition hover:border-slate-300 hover:text-slate-900"
                >
                    Open Normalized
                </a>
            @endif

            @if(auth()->user()->hasPermission('attendance_raw.view'))
                <a
                    href="{{ route('attendance.raw-logs.index', [
                        'q' => $row->employee->emp_code ?? null,
                        'date_from' => optional($windowStart)->format('Y-m-d'),
                        'date_to' => optional($windowEnd)->format('Y-m-d'),
                    ]) }}"
                    class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 shadow-sm transition hover:border-slate-300 hover:text-slate-900"
                >
                    Open Raw Logs
                </a>
            @endif

            @if(auth()->user()->hasPermission('attendance_review.view'))
                <a
                    href="{{ route('review.attendance-cases.index', [
                        'q' => $row->employee->emp_code ?? null,
                        'date_from' => optional($windowStart)->format('Y-m-d'),
                        'date_to' => optional($windowEnd)->format('Y-m-d'),
                    ]) }}"
                    class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 shadow-sm transition hover:border-slate-300 hover:text-slate-900"
                >
                    Open Review Cases
                </a>
            @endif

            @if(auth()->user()->hasPermission('attendance_exception.manage'))
                <a
                    href="{{ route('attendance.attendance-exceptions.create', [
                        'emp_id' => $row->emp_id,
                        'work_date' => optional($row->work_date)->format('Y-m-d'),
                        'source_type_code' => 'MANUAL',
                        'source_ref_id' => 'attendance_daily:'.$row->attendance_daily_id,
                    ]) }}"
                    class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 shadow-sm transition hover:border-slate-300 hover:text-slate-900"
                >
                    Create Exception
                </a>
            @endif

            @if(
                auth()->user()->hasPermission('attendance_review.manage')
                && \Illuminate\Support\Facades\Route::has('review.attendance-cases.create')
            )
                <a
                    href="{{ route('review.attendance-cases.create', [
                        'emp_id' => $row->emp_id,
                        'work_date' => optional($row->work_date)->format('Y-m-d'),
                        'source_type_code' => 'ATTENDANCE_DAILY',
                        'source_ref_id' => 'attendance_daily:'.$row->attendance_daily_id,
                    ]) }}"
                    class="inline-flex items-center justify-center rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm font-medium text-amber-700 shadow-sm transition hover:border-amber-300 hover:bg-amber-100"
                >
                    Create Review Case
                </a>
            @endif

            @if(auth()->user()->hasPermission('attendance_daily.recalculate'))
                <form method="POST" action="{{ route('attendance.daily.recalculate', $row->attendance_daily_id) }}">
                    @csrf
                    <button
                        type="submit"
                        onclick="return confirm('Recalculate row ini?')"
                        class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-slate-800"
                    >
                        Recalculate This Row
                    </button>
                </form>
            @endif
        </div>

        @if(
            ($row->attendance_status_code === 'INCOMPLETE')
            || ($row->review_reason_code)
            || ($row->anomaly_flag)
            || ((int) ($summaryBox['open_review_case_count'] ?? 0) > 0)
        )
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">
                        Needs Review
                    </span>

                    @if($row->attendance_status_code === 'INCOMPLETE')
                        <span class="inline-flex items-center rounded-full border border-amber-200 bg-white px-3 py-1 text-xs font-medium text-slate-700">
                            Status: INCOMPLETE
                        </span>
                    @endif

                    @if($row->review_reason_code)
                        <span class="inline-flex items-center rounded-full border border-amber-200 bg-white px-3 py-1 text-xs font-medium text-slate-700">
                            Reason: {{ $row->review_reason_code }}
                        </span>
                    @endif

                    @if($row->anomaly_flag)
                        <span class="inline-flex items-center rounded-full border border-amber-200 bg-white px-3 py-1 text-xs font-medium text-slate-700">
                            Anomaly Flag
                        </span>
                    @endif

                    @if((int) ($summaryBox['open_review_case_count'] ?? 0) > 0)
                        <span class="inline-flex items-center rounded-full border border-amber-200 bg-white px-3 py-1 text-xs font-medium text-slate-700">
                            Open Cases: {{ $summaryBox['open_review_case_count'] }}
                        </span>
                    @endif
                </div>
            </div>
        @endif

        <div class="grid gap-6 xl:grid-cols-3">
            <div class="xl:col-span-2 space-y-6">
                <x-ui.section-card
                    title="Daily Snapshot"
                    subtitle="Ringkasan utama hasil attendance_daily untuk employee dan tanggal ini."
                >
                    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                        <div>
                            <div class="text-xs text-slate-500">Employee</div>
                            <div class="mt-1 text-lg font-semibold text-slate-900">
                                {{ $row->employee->full_name ?? '-' }}
                            </div>
                            <div class="mt-1 text-sm text-slate-500">
                                {{ $row->employee->emp_code ?? '-' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-xs text-slate-500">Work Date</div>
                            <div class="mt-1 font-medium text-slate-900">
                                {{ optional($row->work_date)->format('Y-m-d') ?: '-' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-xs text-slate-500">Branch</div>
                            <div class="mt-1 font-medium text-slate-900">
                                {{ $row->branch->branch_name ?? '-' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-xs text-slate-500">Shift</div>
                            <div class="mt-1 font-medium text-slate-900">
                                {{ $row->shift->shift_name ?? $row->shift->shift_code ?? '-' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-xs text-slate-500">Policy</div>
                            <div class="mt-1 font-medium text-slate-900">
                                {{ $row->policy->policy_name ?? $row->policy->policy_code ?? '-' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-xs text-slate-500">Attendance Status</div>
                            <div class="mt-1 font-medium text-slate-900">
                                {{ $row->attendance_status_code ?? '-' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-xs text-slate-500">Presence Type</div>
                            <div class="mt-1 font-medium text-slate-900">
                                {{ $row->presence_type_code ?? '-' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-xs text-slate-500">Review Reason</div>
                            <div class="mt-1 font-medium text-slate-900">
                                {{ $row->review_reason_code ?? '-' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-xs text-slate-500">Calculation Version</div>
                            <div class="mt-1 font-medium text-slate-900">
                                {{ $row->calculation_version ?? '-' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-xs text-slate-500">Scheduled In</div>
                            <div class="mt-1 font-medium text-slate-900">
                                {{ optional($row->scheduled_in_datetime)->format('Y-m-d H:i:s') ?: '-' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-xs text-slate-500">Scheduled Out</div>
                            <div class="mt-1 font-medium text-slate-900">
                                {{ optional($row->scheduled_out_datetime)->format('Y-m-d H:i:s') ?: '-' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-xs text-slate-500">Actual In</div>
                            <div class="mt-1 font-medium text-slate-900">
                                {{ optional($row->actual_in_datetime)->format('Y-m-d H:i:s') ?: '-' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-xs text-slate-500">Actual Out</div>
                            <div class="mt-1 font-medium text-slate-900">
                                {{ optional($row->actual_out_datetime)->format('Y-m-d H:i:s') ?: '-' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-xs text-slate-500">Work Min</div>
                            <div class="mt-1 font-medium text-slate-900">{{ number_format((int) ($row->work_min ?? 0)) }}</div>
                        </div>

                        <div>
                            <div class="text-xs text-slate-500">Late Min</div>
                            <div class="mt-1 font-medium text-slate-900">{{ number_format((int) ($row->late_min ?? 0)) }}</div>
                        </div>

                        <div>
                            <div class="text-xs text-slate-500">Early Out Min</div>
                            <div class="mt-1 font-medium text-slate-900">{{ number_format((int) ($row->early_out_min ?? 0)) }}</div>
                        </div>

                        <div>
                            <div class="text-xs text-slate-500">Overtime Min</div>
                            <div class="mt-1 font-medium text-slate-900">{{ number_format((int) ($row->overtime_min ?? 0)) }}</div>
                        </div>

                        <div>
                            <div class="text-xs text-slate-500">Workday OT</div>
                            <div class="mt-1 font-medium text-slate-900">{{ number_format((int) ($row->overtime_workday_min ?? 0)) }}</div>
                        </div>

                        <div>
                            <div class="text-xs text-slate-500">Holiday OT</div>
                            <div class="mt-1 font-medium text-slate-900">{{ number_format((int) ($row->overtime_holiday_min ?? 0)) }}</div>
                        </div>

                        <div>
                            <div class="text-xs text-slate-500">Offday OT</div>
                            <div class="mt-1 font-medium text-slate-900">{{ number_format((int) ($row->overtime_offday_min ?? 0)) }}</div>
                        </div>
                    </div>

                    <div class="mt-6 grid gap-4 md:grid-cols-3">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                            <div class="text-xs text-slate-500">Flags</div>
                            <div class="mt-2 space-y-1 text-sm text-slate-700">
                                <div>Anomaly: {{ $row->anomaly_flag ? 'Yes' : 'No' }}</div>
                                <div>Exception: {{ $row->exception_flag ? 'Yes' : 'No' }}</div>
                                <div>Leave: {{ $row->leave_flag ? 'Yes' : 'No' }}</div>
                                <div>Pattern: {{ $row->pattern_flag ? 'Yes' : 'No' }}</div>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                            <div class="text-xs text-slate-500">Scoring</div>
                            <div class="mt-2 space-y-1 text-sm text-slate-700">
                                <div>Attendance Score: {{ $row->attendance_score ?? 0 }}</div>
                                <div>Late Severity: {{ $row->late_severity_code ?? '-' }}</div>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                            <div class="text-xs text-slate-500">Calculated At</div>
                            <div class="mt-2 text-sm text-slate-700">
                                {{ optional($row->calculated_at)->format('Y-m-d H:i:s') ?: '-' }}
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <div class="text-xs text-slate-500">Daily Notes</div>
                        <div class="mt-2 whitespace-normal break-words text-sm leading-6 text-slate-700">
                            {{ $row->notes ?: '-' }}
                        </div>
                    </div>
                </x-ui.section-card>

                <x-ui.section-card
                    title="Review Cases on This Day"
                    subtitle="Kasus review untuk employee dan tanggal yang sama."
                >
                    @if ($reviewCases->count())
                        <div class="overflow-hidden rounded-2xl border border-slate-200">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-slate-200 bg-white">
                                    <thead class="bg-slate-50/80">
                                        <tr>
                                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Case Type</th>
                                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Severity</th>
                                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Resolution</th>
                                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Notes</th>
                                            <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach ($reviewCases as $case)
                                            @php
                                                $severityTone = match($case->severity_code) {
                                                    'LOW' => 'neutral',
                                                    'MEDIUM' => 'info',
                                                    'HIGH' => 'warning',
                                                    'CRITICAL' => 'danger',
                                                    default => 'neutral',
                                                };

                                                $statusTone = match($case->review_status_code) {
                                                    'OPEN' => 'info',
                                                    'IN_REVIEW' => 'warning',
                                                    'RESOLVED' => 'success',
                                                    'REJECTED' => 'danger',
                                                    'CLOSED' => 'neutral',
                                                    default => 'neutral',
                                                };
                                            @endphp

                                            <tr class="hover:bg-slate-50/70">
                                                <td class="px-5 py-4 text-sm font-medium text-slate-900">
                                                    {{ $case->case_type_code }}
                                                </td>
                                                <td class="px-5 py-4">
                                                    <x-ui.status-badge :label="$case->severity_code" :tone="$severityTone" />
                                                </td>
                                                <td class="px-5 py-4">
                                                    <x-ui.status-badge :label="$case->review_status_code" :tone="$statusTone" />
                                                </td>
                                                <td class="px-5 py-4 text-sm text-slate-700">
                                                    {{ $case->resolution_type_code ?: '-' }}
                                                </td>
                                                <td class="px-5 py-4 text-sm text-slate-600">
                                                    <div class="max-w-md whitespace-normal break-words line-clamp-3" title="{{ $case->notes }}">
                                                        {{ $case->notes ?: '-' }}
                                                    </div>
                                                </td>
                                                <td class="px-5 py-4">
                                                    <div class="flex justify-end">
                                                        <a
                                                            href="{{ route('review.attendance-cases.show', $case->review_case_id) }}"
                                                            class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-600 shadow-sm transition hover:border-slate-300 hover:text-slate-900"
                                                        >
                                                            Detail
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @else
                        <x-ui.empty-state
                            title="Tidak ada review cases"
                            description="Belum ada attendance review case untuk employee dalam window investigasi ini."
                        />
                    @endif
                </x-ui.section-card>

                <x-ui.section-card
                    title="Applied Exceptions"
                    subtitle="Exception yang terhubung ke employee dan window investigasi ini."
                >
                    @if ($exceptions->count())
                        <div class="overflow-hidden rounded-2xl border border-slate-200">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-slate-200 bg-white">
                                    <thead class="bg-slate-50/80">
                                        <tr>
                                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Work Date</th>
                                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Type</th>
                                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Minutes</th>
                                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Time Value</th>
                                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Source</th>
                                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Reason</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach ($exceptions as $exception)
                                            <tr class="hover:bg-slate-50/70">
                                                <td class="px-5 py-4 text-sm text-slate-700">
                                                    {{ \Illuminate\Support\Carbon::parse($exception->work_date)->format('Y-m-d') }}
                                                </td>
                                                <td class="px-5 py-4 text-sm font-medium text-slate-900">
                                                    {{ $exception->exception_type_code ?? '-' }}
                                                </td>
                                                <td class="px-5 py-4 text-sm text-slate-700">
                                                    {{ is_null($exception->minutes_value) ? '-' : (int) $exception->minutes_value }}
                                                </td>
                                                <td class="px-5 py-4 text-sm text-slate-700">
                                                    {{ $exception->time_value ? \Illuminate\Support\Carbon::parse($exception->time_value)->format('Y-m-d H:i:s') : '-' }}
                                                </td>
                                                <td class="px-5 py-4 text-sm text-slate-700">
                                                    {{ $exception->source_type_code ?: '-' }}
                                                    @if($exception->source_ref_id)
                                                        <div class="text-xs text-slate-500">{{ $exception->source_ref_id }}</div>
                                                    @endif
                                                </td>
                                                <td class="px-5 py-4 text-sm text-slate-600">
                                                    <div class="max-w-md whitespace-normal break-words line-clamp-3" title="{{ $exception->reason }}">
                                                        {{ $exception->reason ?: '-' }}
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @else
                        <x-ui.empty-state
                            title="Tidak ada exception"
                            description="Belum ada attendance exception pada employee dan window investigasi ini."
                        />
                    @endif
                </x-ui.section-card>

                <x-ui.section-card
                    title="Calculation Trace"
                    subtitle="Jejak langkah dari attendance_daily_details untuk membaca bagaimana row ini dibentuk."
                >
                    @if ($row->details->count())
                        <div class="space-y-3">
                            @foreach ($row->details as $detail)
                                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm">
                                    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                        <div>
                                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                {{ $detail->step_name }}
                                            </div>
                                            <div class="mt-1 text-sm font-medium text-slate-900">
                                                {{ $detail->step_result ?: '-' }}
                                            </div>
                                        </div>

                                        <div class="text-xs text-slate-500 md:text-right">
                                            <div>Source: {{ $detail->source_type_code ?: '-' }}</div>
                                            <div>Ref: {{ $detail->source_ref_id ?: '-' }}</div>
                                            <div>At: {{ optional($detail->created_at)->format('Y-m-d H:i:s') ?: '-' }}</div>
                                        </div>
                                    </div>

                                    @if($detail->notes)
                                        <div class="mt-3 rounded-xl border border-slate-100 bg-slate-50 px-3 py-2 text-sm text-slate-600">
                                            {{ $detail->notes }}
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <x-ui.empty-state
                            title="Trace belum tersedia"
                            description="Belum ada attendance_daily_details untuk row ini."
                        />
                    @endif
                </x-ui.section-card>

                <x-ui.section-card
                    title="Normalized Logs"
                    subtitle="Log hasil normalisasi pada employee dalam window investigasi ini."
                >
                    @if ($normalizedLogs->count())
                        <div class="overflow-hidden rounded-2xl border border-slate-200">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-slate-200 bg-white">
                                    <thead class="bg-slate-50/80">
                                        <tr>
                                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Datetime</th>
                                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Event</th>
                                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Duplicate</th>
                                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach ($normalizedLogs as $log)
                                            @php
                                                $normalizedTone = match($log->normalized_status_code) {
                                                    'VALID' => 'success',
                                                    'SUSPICIOUS' => 'warning',
                                                    'DUPLICATE' => 'neutral',
                                                    'INVALID' => 'danger',
                                                    'IGNORED' => 'neutral',
                                                    default => 'neutral',
                                                };
                                            @endphp

                                            <tr class="hover:bg-slate-50/70">
                                                <td class="px-5 py-4 text-sm text-slate-700">
                                                    {{ \Illuminate\Support\Carbon::parse($log->log_datetime)->format('Y-m-d H:i:s') }}
                                                </td>
                                                <td class="px-5 py-4 text-sm text-slate-700">
                                                    {{ $log->derived_event_type_code ?? '-' }}
                                                </td>
                                                <td class="px-5 py-4">
                                                    <x-ui.status-badge :label="$log->normalized_status_code ?? '-'" :tone="$normalizedTone" />
                                                </td>
                                                <td class="px-5 py-4 text-sm text-slate-700">
                                                    {{ $log->is_duplicate_candidate ? 'Yes' : 'No' }}
                                                </td>
                                                <td class="px-5 py-4 text-sm text-slate-600">
                                                    <div class="max-w-md whitespace-normal break-words line-clamp-3" title="{{ $log->notes }}">
                                                        {{ $log->notes ?: '-' }}
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @else
                        <x-ui.empty-state
                            title="Normalized logs tidak ditemukan"
                            description="Belum ada attendance_logs_normalized pada employee dalam window investigasi ini."
                        />
                    @endif
                </x-ui.section-card>

                <x-ui.section-card
                    title="Raw Logs"
                    subtitle="Log mentah attendance pada employee dalam window investigasi ini."
                >
                    @if ($rawLogs->count())
                        <div class="overflow-hidden rounded-2xl border border-slate-200">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-slate-200 bg-white">
                                    <thead class="bg-slate-50/80">
                                        <tr>
                                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Datetime</th>
                                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Source</th>
                                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Device</th>
                                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Device User</th>
                                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Mode</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach ($rawLogs as $log)
                                            <tr class="hover:bg-slate-50/70">
                                                <td class="px-5 py-4 text-sm text-slate-700">
                                                    {{ \Illuminate\Support\Carbon::parse($log->log_datetime)->format('Y-m-d H:i:s') }}
                                                </td>
                                                <td class="px-5 py-4 text-sm text-slate-700">
                                                    {{ $log->source_system ?? '-' }}
                                                </td>
                                                <td class="px-5 py-4 text-sm text-slate-700">
                                                    {{ $log->device_id ?? '-' }}
                                                </td>
                                                <td class="px-5 py-4 text-sm text-slate-700">
                                                    {{ $log->device_user_id ?? '-' }}
                                                </td>
                                                <td class="px-5 py-4 text-sm text-slate-700">
                                                    {{ $log->io_mode ?? '-' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @else
                        <x-ui.empty-state
                            title="Raw logs tidak ditemukan"
                            description="Belum ada attendance_logs_raw pada employee dalam window investigasi ini."
                        />
                    @endif
                </x-ui.section-card>
            </div>

            <div class="space-y-6">
                <x-ui.section-card
                    title="Investigation Snapshot"
                    subtitle="Ringkasan cepat konteks investigasi untuk row ini."
                >
                    <div class="grid gap-3">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Window</div>
                            <div class="mt-1 text-sm font-medium text-slate-900">
                                {{ optional($windowStart)->format('Y-m-d H:i:s') }} → {{ optional($windowEnd)->format('Y-m-d H:i:s') }}
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Review Cases</div>
                                <div class="mt-1 text-lg font-semibold text-slate-900">{{ $summaryBox['review_case_count'] ?? 0 }}</div>
                                <div class="text-xs text-slate-500">Open/In Review: {{ $summaryBox['open_review_case_count'] ?? 0 }}</div>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Exceptions</div>
                                <div class="mt-1 text-lg font-semibold text-slate-900">{{ $summaryBox['exception_count'] ?? 0 }}</div>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Normalized Logs</div>
                                <div class="mt-1 text-lg font-semibold text-slate-900">{{ $summaryBox['normalized_log_count'] ?? 0 }}</div>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Raw Logs</div>
                                <div class="mt-1 text-lg font-semibold text-slate-900">{{ $summaryBox['raw_log_count'] ?? 0 }}</div>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Trace Steps</div>
                                <div class="mt-1 text-lg font-semibold text-slate-900">{{ $summaryBox['detail_step_count'] ?? 0 }}</div>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Scheduled Minutes</div>
                                <div class="mt-1 text-lg font-semibold text-slate-900">{{ $summaryBox['scheduled_minutes'] ?? 0 }}</div>
                            </div>
                        </div>
                    </div>
                </x-ui.section-card>

                <x-ui.section-card
                    title="Quick Reading Guide"
                    subtitle="Ringkasan singkat untuk mempercepat investigasi attendance harian."
                >
                    <div class="space-y-3 text-sm text-slate-600">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                            Cek dulu attendance status, actual in/out, dan review reason untuk melihat apakah kasus ini masih perlu investigasi log lebih bawah.
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                            Jika normalized logs kosong, kemungkinan pipeline belum dijalankan atau raw logs pada periode investigasi ini tidak berhasil ternormalisasi.
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                            Jika review cases banyak, prioritaskan severity tertinggi dan status OPEN / IN_REVIEW terlebih dahulu.
                        </div>
                    </div>
                </x-ui.section-card>
            </div>
        </div>
    </div>
@endsection