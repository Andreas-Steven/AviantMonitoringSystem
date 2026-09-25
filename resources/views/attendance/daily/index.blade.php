@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Attendance Daily"
        subtitle="Visual verification layer untuk hasil kalkulasi attendance harian, anomaly, exception, leave, dan overtime."
        :breadcrumbs="[
            ['label' => 'Attendance'],
            ['label' => 'Attendance Daily'],
        ]"
    >
        <x-slot:actions>
            <div class="flex flex-wrap items-center gap-2">
                <x-ui.button
                    variant="ghost"
                    onclick="window.location='{{ route('attendance.daily.index') }}'"
                >
                    Refresh
                </x-ui.button>

                @if(auth()->user()->hasPermission('attendance_daily.recalculate'))
                    <x-ui.button
                        variant="ghost"
                        onclick="window.location='{{ route('attendance.operations.index') }}'"
                    >
                        Open Operations
                    </x-ui.button>
                @endif
            </div>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.page-section
        title="Daily Worklist Filters"
        subtitle="Gunakan period, branch, employee, status, presence, dan flags untuk investigasi operasional."
    >
        <form method="GET" action="{{ route('attendance.daily.index') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.field label="Employee">
                <input
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Cari emp code / nama / biometric"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                >
            </x-ui.field>

            <x-ui.field label="Payroll Period">
                <select name="payroll_period_id" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                    <option value="">Semua period</option>
                    @foreach($payrollPeriods as $period)
                        <option value="{{ $period->payroll_period_id }}" @selected((string) request('payroll_period_id') === (string) $period->payroll_period_id)>
                            {{ $period->period_code }} ({{ optional($period->period_start_date)->format('Y-m-d') }} s/d {{ optional($period->period_end_date)->format('Y-m-d') }})
                        </option>
                    @endforeach
                </select>
            </x-ui.field>

            <x-ui.field label="Branch">
                <select name="branch_id" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                    <option value="">Semua branch</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->branch_id }}" @selected((string) request('branch_id') === (string) $branch->branch_id)>
                            {{ $branch->branch_name }}
                        </option>
                    @endforeach
                </select>
            </x-ui.field>

            <x-ui.field label="Attendance Status">
                <select name="attendance_status_code" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                    <option value="">Semua status</option>
                    @foreach($attendanceStatuses as $status)
                        <option value="{{ $status->code }}" @selected(request('attendance_status_code') === $status->code)>
                            {{ $status->name }}
                        </option>
                    @endforeach
                </select>
            </x-ui.field>

            <x-ui.field label="Presence Type">
                <select name="presence_type_code" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                    <option value="">Semua presence</option>
                    @foreach($presenceTypes as $presenceType)
                        <option value="{{ $presenceType->code }}" @selected(request('presence_type_code') === $presenceType->code)>
                            {{ $presenceType->name }}
                        </option>
                    @endforeach
                </select>
            </x-ui.field>

            <x-ui.field label="Review Reason">
                <select name="review_reason_code" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                    <option value="">Semua review reason</option>
                    @foreach($reviewReasons as $reviewReason)
                        <option value="{{ $reviewReason }}" @selected(request('review_reason_code') === $reviewReason)>
                            {{ $reviewReason }}
                        </option>
                    @endforeach
                </select>
            </x-ui.field>

            <x-ui.field label="Date From">
                <input
                    type="date"
                    name="date_from"
                    value="{{ request('date_from') }}"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                >
            </x-ui.field>

            <x-ui.field label="Date To">
                <input
                    type="date"
                    name="date_to"
                    value="{{ request('date_to') }}"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                >
            </x-ui.field>

            <div class="md:col-span-2 xl:col-span-4">
                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
                    <label class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 px-4 py-2.5 text-sm text-slate-700">
                        <input type="checkbox" name="anomaly_only" value="1" @checked(request()->boolean('anomaly_only')) class="rounded border-slate-300">
                        Anomaly only
                    </label>

                    <label class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 px-4 py-2.5 text-sm text-slate-700">
                        <input type="checkbox" name="exception_only" value="1" @checked(request()->boolean('exception_only')) class="rounded border-slate-300">
                        Exception only
                    </label>

                    <label class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 px-4 py-2.5 text-sm text-slate-700">
                        <input type="checkbox" name="leave_only" value="1" @checked(request()->boolean('leave_only')) class="rounded border-slate-300">
                        Leave only
                    </label>

                    <label class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 px-4 py-2.5 text-sm text-slate-700">
                        <input type="checkbox" name="late_only" value="1" @checked(request()->boolean('late_only')) class="rounded border-slate-300">
                        Late only
                    </label>

                    <label class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 px-4 py-2.5 text-sm text-slate-700">
                        <input type="checkbox" name="incomplete_only" value="1" @checked(request()->boolean('incomplete_only')) class="rounded border-slate-300">
                        Incomplete only
                    </label>
                </div>
            </div>

            <div class="flex items-end gap-3 xl:col-span-4">
                <x-ui.button type="submit">Search</x-ui.button>
                <x-ui.button type="button" variant="ghost" onclick="window.location='{{ route('attendance.daily.index') }}'">Reset</x-ui.button>
            </div>
        </form>
    </x-ui.page-section>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
        <x-ui.stat-card
            label="Filtered Rows"
            :value="$summaryStats['total_rows']"
            hint="Total row sesuai filter"
        />
        <x-ui.stat-card
            label="Present"
            :value="$summaryStats['present_rows']"
            hint="Row PRESENT"
        />
        <x-ui.stat-card
            label="Anomaly"
            :value="$summaryStats['anomaly_rows']"
            hint="Flag anomaly"
        />
        <x-ui.stat-card
            label="Late Rows"
            :value="$summaryStats['late_rows']"
            hint="late_min > 0"
        />
        <x-ui.stat-card
            label="OT Rows"
            :value="$summaryStats['overtime_rows']"
            hint="overtime_min > 0"
        />
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white px-4 py-4 shadow-sm">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
            <div>
                <h3 class="text-sm font-semibold text-slate-900">Compact Verification Summary</h3>
                <p class="mt-1 text-sm text-slate-500">
                    Ringkasan cepat hasil filter untuk investigasi harian.
                </p>
            </div>

            <div class="grid flex-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Incomplete</div>
                    <div class="mt-1 text-lg font-semibold text-slate-900">{{ $summaryStats['incomplete_rows'] }}</div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Exception</div>
                    <div class="mt-1 text-lg font-semibold text-slate-900">{{ $summaryStats['exception_rows'] }}</div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Leave</div>
                    <div class="mt-1 text-lg font-semibold text-slate-900">{{ $summaryStats['leave_rows'] }}</div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Late Min</div>
                    <div class="mt-1 text-lg font-semibold text-slate-900">{{ (int) ($metricTotals->total_late_min ?? 0) }}</div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">OT Total</div>
                    <div class="mt-1 text-lg font-semibold text-slate-900">{{ (int) ($metricTotals->total_overtime_min ?? 0) }} min</div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Early Out Rows</div>
                    <div class="mt-1 text-lg font-semibold text-slate-900">{{ $summaryStats['early_out_rows'] }}</div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Work Min</div>
                    <div class="mt-1 text-lg font-semibold text-slate-900">{{ (int) ($metricTotals->total_work_min ?? 0) }}</div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">OT Workday</div>
                    <div class="mt-1 text-lg font-semibold text-slate-900">{{ (int) ($metricTotals->total_overtime_workday_min ?? 0) }} min</div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">OT Holiday</div>
                    <div class="mt-1 text-lg font-semibold text-slate-900">{{ (int) ($metricTotals->total_overtime_holiday_min ?? 0) }} min</div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">OT Offday</div>
                    <div class="mt-1 text-lg font-semibold text-slate-900">{{ (int) ($metricTotals->total_overtime_offday_min ?? 0) }} min</div>
                </div>
            </div>
        </div>
    </div>

    <x-ui.table-shell>
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50/80">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Employee</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Date / Branch</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Shift / Schedule</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Actual In/Out</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Indicators</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse($rows as $row)
                    @php
                        $statusTone = match($row->attendance_status_code) {
                            'PRESENT' => 'success',
                            'ABSENT' => 'danger',
                            'INCOMPLETE', 'MANUAL_REVIEW' => 'warning',
                            'LEAVE', 'SICK', 'PERMISSION', 'HOLIDAY', 'OFF' => 'info',
                            default => 'neutral',
                        };

                        $presenceTone = match($row->presence_type_code) {
                            'FULL_DAY' => 'success',
                            'PARTIAL', 'HALF_DAY' => 'warning',
                            'OVERTIME_ONLY' => 'info',
                            default => 'neutral',
                        };
                    @endphp

                    <tr class="transition hover:bg-slate-50">
                        <td class="px-5 py-4">
                            <div class="font-medium text-slate-900">{{ $row->employee->full_name ?? '-' }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ $row->employee->emp_code ?? '-' }}</div>
                        </td>

                        <td class="px-5 py-4">
                            <div class="font-medium text-slate-900">{{ optional($row->work_date)->format('Y-m-d') }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ $row->branch->branch_name ?? '-' }}</div>
                        </td>

                        <td class="px-5 py-4 text-slate-600">
                            <div class="font-medium text-slate-900">{{ $row->shift->shift_code ?? '-' }}</div>
                            <div class="mt-1 text-xs text-slate-500">
                                {{ optional($row->scheduled_in_datetime)->format('H:i') ?: '--:--' }}
                                —
                                {{ optional($row->scheduled_out_datetime)->format('H:i') ?: '--:--' }}
                            </div>
                        </td>

                        <td class="px-5 py-4 text-slate-600">
                            <div>{{ optional($row->actual_in_datetime)->format('Y-m-d H:i') ?: '-' }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ optional($row->actual_out_datetime)->format('Y-m-d H:i') ?: '-' }}</div>
                        </td>

                        <td class="px-5 py-4">
                            <div class="flex flex-wrap gap-2">
                                <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-700">
                                    Work {{ (int) $row->work_min }}m
                                </span>

                                @if((int) $row->late_min > 0)
                                    <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700">
                                        Late {{ (int) $row->late_min }}m
                                    </span>
                                @endif

                                @if((int) $row->early_out_min > 0)
                                    <span class="inline-flex items-center rounded-full border border-rose-200 bg-rose-50 px-2.5 py-1 text-xs font-medium text-rose-700">
                                        Early {{ (int) $row->early_out_min }}m
                                    </span>
                                @endif

                                @if((int) $row->overtime_min > 0)
                                    <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                        OT {{ (int) $row->overtime_min }}m
                                    </span>
                                @endif
                            </div>

                            @if((int) $row->overtime_workday_min > 0 || (int) $row->overtime_holiday_min > 0 || (int) $row->overtime_offday_min > 0)
                                <div class="mt-2 flex flex-wrap gap-2 text-[11px]">
                                    @if((int) $row->overtime_workday_min > 0)
                                        <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-2 py-1 text-slate-600">
                                            WD {{ (int) $row->overtime_workday_min }}m
                                        </span>
                                    @endif
                                    @if((int) $row->overtime_holiday_min > 0)
                                        <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-2 py-1 text-slate-600">
                                            HOL {{ (int) $row->overtime_holiday_min }}m
                                        </span>
                                    @endif
                                    @if((int) $row->overtime_offday_min > 0)
                                        <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-2 py-1 text-slate-600">
                                            OFF {{ (int) $row->overtime_offday_min }}m
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </td>

                        <td class="px-5 py-4">
                            <div class="flex flex-wrap gap-2">
                                <x-ui.status-badge :label="$row->attendance_status_code" :tone="$statusTone" />

                                @if($row->presence_type_code)
                                    <x-ui.status-badge :label="$row->presence_type_code" :tone="$presenceTone" />
                                @endif

                                @if($row->anomaly_flag)
                                    <x-ui.status-badge label="ANOMALY" tone="warning" />
                                @endif

                                @if($row->exception_flag)
                                    <x-ui.status-badge label="EXCEPTION" tone="info" />
                                @endif

                                @if($row->leave_flag)
                                    <x-ui.status-badge label="LEAVE FLAG" tone="info" />
                                @endif

                                @if($row->pattern_flag)
                                    <x-ui.status-badge label="PATTERN" tone="neutral" />
                                @endif
                            </div>

                            @if($row->review_reason_code || $row->late_severity_code || !is_null($row->attendance_score))
                                <div class="mt-3 space-y-1 text-xs text-slate-500">
                                    @if($row->review_reason_code)
                                        <div>Review: <span class="font-medium text-slate-700">{{ $row->review_reason_code }}</span></div>
                                    @endif
                                    @if($row->late_severity_code)
                                        <div>Late Severity: <span class="font-medium text-slate-700">{{ $row->late_severity_code }}</span></div>
                                    @endif
                                    @if(!is_null($row->attendance_score))
                                        <div>Score: <span class="font-medium text-slate-700">{{ $row->attendance_score }}</span></div>
                                    @endif
                                </div>
                            @endif
                        </td>

                        <td class="px-5 py-4">
                            <div class="flex justify-end gap-2">
                                <x-ui.button
                                    variant="ghost"
                                    onclick="window.location='{{ route('attendance.daily.show', $row->attendance_daily_id) }}'"
                                >
                                    Detail
                                </x-ui.button>

                                <x-ui.row-actions :actions="[
                                    [
                                        'label' => 'Open Raw Logs',
                                        'url' => auth()->user()->hasPermission('attendance_raw.view')
                                            ? route('attendance.raw-logs.index', [
                                                'q' => $row->employee->emp_code ?? null,
                                                'date_from' => optional($row->work_date)->format('Y-m-d'),
                                                'date_to' => optional($row->work_date)->format('Y-m-d'),
                                            ])
                                            : null,
                                        'visible' => auth()->user()->hasPermission('attendance_raw.view'),
                                    ],
                                    [
                                        'label' => 'Open Normalized Logs',
                                        'url' => auth()->user()->hasPermission('attendance_normalized.view')
                                            ? route('attendance.normalized-logs.index', [
                                                'q' => $row->employee->emp_code ?? null,
                                                'date_from' => optional($row->work_date)->format('Y-m-d'),
                                                'date_to' => optional($row->work_date)->format('Y-m-d'),
                                            ])
                                            : null,
                                        'visible' => auth()->user()->hasPermission('attendance_normalized.view'),
                                    ],
                                    [
                                        'label' => 'Open Review Cases',
                                        'url' => auth()->user()->hasPermission('attendance_review.view')
                                            ? route('review.attendance-cases.index', [
                                                'q' => $row->employee->emp_code ?? null,
                                                'date_from' => optional($row->work_date)->format('Y-m-d'),
                                                'date_to' => optional($row->work_date)->format('Y-m-d'),
                                            ])
                                            : null,
                                        'visible' => auth()->user()->hasPermission('attendance_review.view'),
                                    ],
                                    [
                                        'label' => 'Create Exception',
                                        'url' => auth()->user()->hasPermission('attendance_exception.manage')
                                            ? route('attendance.attendance-exceptions.create', [
                                                'emp_id' => $row->emp_id,
                                                'work_date' => optional($row->work_date)->format('Y-m-d'),
                                                'source_type_code' => 'MANUAL',
                                                'source_ref_id' => 'attendance_daily:'.$row->attendance_daily_id,
                                            ])
                                            : null,
                                        'visible' => auth()->user()->hasPermission('attendance_exception.manage'),
                                    ],
                                    [
                                        'label' => 'Recalculate This Row',
                                        'type' => 'form',
                                        'url' => auth()->user()->hasPermission('attendance_daily.recalculate')
                                            ? route('attendance.daily.recalculate', $row->attendance_daily_id)
                                            : null,
                                        'visible' => auth()->user()->hasPermission('attendance_daily.recalculate'),
                                        'confirm' => 'Recalculate row ini?',
                                        'hint' => 'POST',
                                    ],
                                ]" />
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-14">
                            <x-ui.empty-state
                                title="No attendance daily rows found"
                                description="Belum ada data attendance daily sesuai filter."
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($rows->hasPages())
            <div class="border-t border-slate-200 bg-white px-5 py-4">
                {{ $rows->withQueryString()->links() }}
            </div>
        @endif
    </x-ui.table-shell>
</div>
@endsection