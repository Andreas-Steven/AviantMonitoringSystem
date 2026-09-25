@extends('layouts.app')

@section('title', 'Laporan Periode Payroll')

@section('content')
    @php
        $otherObligationRows = $obligationRows->reject(fn ($item) => $item->obligation_type_code === 'SATURDAY_MIN')->values();
    @endphp

    <div class="space-y-6">
        <x-ui.page-header
            title="Laporan Periode Payroll"
            :subtitle="sprintf(
                '%s · %s · %s',
                $row->employee?->emp_code ?? '-',
                $row->employee?->full_name ?? '-',
                $row->payrollPeriod?->period_code ?? '-'
            )"
            :breadcrumbs="[
                ['label' => 'Summary'],
                ['label' => 'Period Summary', 'url' => route('summary.period.index')],
                ['label' => 'Laporan Payroll'],
            ]"
        >
            <div class="flex flex-wrap items-center gap-2">
                <a
                    href="{{ route('summary.period.index') }}"
                    class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
                >
                    Back
                </a>

                @if($row->payrollPeriod)
                    <a
                        href="{{ route('summary.monthly.index', [
                            'q' => $row->employee?->emp_code,
                            'period_year' => $row->payrollPeriod->payroll_year,
                            'period_month' => $row->payrollPeriod->payroll_month,
                        ]) }}"
                        class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
                    >
                        Open Monthly Summary
                    </a>
                @endif

                @if(auth()->user()?->hasPermission('attendance_daily.view') && $row->payrollPeriod)
                    <a
                        href="{{ route('attendance.daily.index', [
                            'q' => $row->employee?->emp_code,
                            'date_from' => $row->payrollPeriod->period_start_date,
                            'date_to' => $row->payrollPeriod->period_end_date,
                        ]) }}"
                        class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
                    >
                        Open Attendance Daily
                    </a>
                @endif
            </div>
        </x-ui.page-header>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.stat-card
                label="HEK"
                :value="number_format($reportMetrics['hek_count'], 2)"
                hint="Target / basis hari kerja pada period payroll"
            />

            <x-ui.stat-card
                label="Valid Present"
                :value="number_format($reportMetrics['valid_present_count'], 2)"
                hint="Present valid yang masuk basis period summary"
            />

            <x-ui.stat-card
                label="Deficit / Excess"
                :value="number_format($reportMetrics['deficit_count'], 2) . ' / ' . number_format($reportMetrics['excess_count'], 2)"
                hint="Gap dan surplus terhadap basis payroll"
            />

            <x-ui.stat-card
                label="OT / Deduction"
                :value="(int) $reportMetrics['overtime_min_total'] . ' min / ' . number_format($reportMetrics['deduction_day_count'], 2)"
                hint="Dampak payroll dari overtime dan deduction day"
            />
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
            <div class="space-y-6 xl:col-span-8">
                <x-ui.section-card
                    title="Payroll Period Snapshot"
                    :description="'Gabungan ringkasan employee, period, branch, pattern, dan basis summary.'"
                >
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Employee</div>
                            <div class="mt-1 text-base font-semibold text-slate-900">
                                {{ $row->employee?->emp_code ?? '-' }} · {{ $row->employee?->full_name ?? '-' }}
                            </div>
                            @if($row->employee?->biometric_code)
                                <div class="mt-1 text-sm text-slate-500">
                                    Bio: {{ $row->employee->biometric_code }}
                                </div>
                            @endif
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Branch</div>
                            <div class="mt-1 text-base font-medium text-slate-900">
                                {{ $row->branch?->branch_name ?? '-' }}
                            </div>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Payroll Period</div>
                            <div class="mt-1 text-base font-medium text-slate-900">
                                {{ $row->payrollPeriod?->period_code ?? '-' }}
                            </div>
                            @if($row->payrollPeriod)
                                <div class="mt-1 text-sm text-slate-500">
                                    {{ \Illuminate\Support\Carbon::parse($row->payrollPeriod->period_start_date)->format('d M Y') }}
                                    -
                                    {{ \Illuminate\Support\Carbon::parse($row->payrollPeriod->period_end_date)->format('d M Y') }}
                                </div>
                            @endif
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Work Pattern</div>
                            <div class="mt-1 text-base font-medium text-slate-900">
                                {{ $row->workPattern?->work_pattern_name ?? $row->workPattern?->work_pattern_code ?? '-' }}
                            </div>
                            <div class="mt-1 text-sm text-slate-500">
                                Basis: {{ $row->summary_basis_type_code ?? '-' }}
                            </div>
                        </div>
                    </div>

                    @if(!empty($row->notes))
                        <div class="mt-4 rounded-xl border border-slate-200 bg-white p-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Summary Notes</div>
                            <div class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700">
                                {{ $row->notes }}
                            </div>
                        </div>
                    @endif
                </x-ui.section-card>

                <x-ui.section-card
                    title="Payroll Summary Layer"
                    :description="'Ringkasan utama yang menggabungkan attendance_period_summaries dan attendance_monthly_summary.'"
                >
                    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                        <div class="overflow-hidden rounded-xl border border-slate-200">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Metric</th>
                                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Value</th>
                                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Notes</th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-slate-100 bg-white">
                                    <tr>
                                        <td class="px-4 py-3 font-medium text-slate-700">HEK</td>
                                        <td class="px-4 py-3 text-right font-semibold text-slate-900">
                                            {{ number_format($reportMetrics['hek_count'], 2) }}
                                        </td>
                                        <td class="px-4 py-3 text-slate-500">Target / basis hari kerja pada period payroll.</td>
                                    </tr>

                                    <tr>
                                        <td class="px-4 py-3 font-medium text-slate-700">Valid Present</td>
                                        <td class="px-4 py-3 text-right font-semibold text-slate-900">
                                            {{ number_format($reportMetrics['valid_present_count'], 2) }}
                                        </td>
                                        <td class="px-4 py-3 text-slate-500">Present valid yang masuk basis period summary.</td>
                                    </tr>

                                    <tr>
                                        <td class="px-4 py-3 font-medium text-amber-700">Deficit</td>
                                        <td class="px-4 py-3 text-right font-semibold text-amber-700">
                                            {{ number_format($reportMetrics['deficit_count'], 2) }}
                                        </td>
                                        <td class="px-4 py-3 text-slate-500">Gap terhadap target / obligation.</td>
                                    </tr>

                                    <tr>
                                        <td class="px-4 py-3 font-medium text-sky-700">Excess</td>
                                        <td class="px-4 py-3 text-right font-semibold text-sky-700">
                                            {{ number_format($reportMetrics['excess_count'], 2) }}
                                        </td>
                                        <td class="px-4 py-3 text-slate-500">Surplus / kompensasi attendance sesuai basis summary.</td>
                                    </tr>

                                    <tr>
                                        <td class="px-4 py-3 font-medium text-slate-700">Late</td>
                                        <td class="px-4 py-3 text-right font-semibold text-slate-900">
                                            {{ (int) $reportMetrics['late_count'] }}x · {{ (int) $reportMetrics['late_min_total'] }} min
                                        </td>
                                        <td class="px-4 py-3 text-slate-500">Jumlah dan total menit keterlambatan.</td>
                                    </tr>

                                    <tr>
                                        <td class="px-4 py-3 font-medium text-slate-700">Early Out</td>
                                        <td class="px-4 py-3 text-right font-semibold text-slate-900">
                                            {{ (int) $reportMetrics['early_out_count'] }}x · {{ (int) $reportMetrics['early_out_min_total'] }} min
                                        </td>
                                        <td class="px-4 py-3 text-slate-500">Jumlah dan total menit pulang cepat.</td>
                                    </tr>

                                    <tr>
                                        <td class="px-4 py-3 font-medium text-rose-700">Incomplete</td>
                                        <td class="px-4 py-3 text-right font-semibold text-rose-700">
                                            {{ (int) $reportMetrics['incomplete_count'] }}
                                        </td>
                                        <td class="px-4 py-3 text-slate-500">Daily row yang perlu perhatian / belum lengkap.</td>
                                    </tr>

                                    <tr>
                                        <td class="px-4 py-3 font-medium text-slate-700">OT Days / OT Minutes</td>
                                        <td class="px-4 py-3 text-right font-semibold text-slate-900">
                                            {{ number_format($reportMetrics['overtime_day_count'], 2) }}
                                            /
                                            {{ (int) $reportMetrics['overtime_min_total'] }} min
                                        </td>
                                        <td class="px-4 py-3 text-slate-500">Total overtime pada period payroll.</td>
                                    </tr>

                                    <tr>
                                        <td class="px-4 py-3 font-medium text-slate-700">Leave Used</td>
                                        <td class="px-4 py-3 text-right font-semibold text-slate-900">
                                            {{ number_format($reportMetrics['leave_quota_used_count'], 2) }}
                                        </td>
                                        <td class="px-4 py-3 text-slate-500">Cuti yang terpakai dalam summary period.</td>
                                    </tr>

                                    <tr>
                                        <td class="px-4 py-3 font-medium text-rose-700">Deduction Days</td>
                                        <td class="px-4 py-3 text-right font-semibold text-rose-700">
                                            {{ number_format($reportMetrics['deduction_day_count'], 2) }}
                                        </td>
                                        <td class="px-4 py-3 text-slate-500">Final deduction day yang berdampak ke payroll.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </x-ui.section-card>

                <x-ui.section-card
                    title="Kewajiban Sabtu"
                    :description="'Ringkasan employee_period_obligations khusus obligation type SATURDAY_MIN.'"
                >
                    @if(!$saturdayObligation['has_data'])
                        <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-sm text-slate-500">
                            Tidak ada kewajiban Sabtu pada payroll period ini.
                        </div>
                    @else
                        <div class="grid gap-4 md:grid-cols-4">
                            <div class="rounded-xl border border-slate-200 bg-white p-4">
                                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Required</div>
                                <div class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format($saturdayObligation['required_count'], 2) }}</div>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-white p-4">
                                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Actual</div>
                                <div class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format($saturdayObligation['actual_count'], 2) }}</div>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-white p-4">
                                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Excess</div>
                                <div class="mt-2 text-2xl font-semibold text-sky-700">{{ number_format($saturdayObligation['excess_count'], 2) }}</div>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-white p-4">
                                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Status</div>
                                <div class="mt-2">
                                    @if($saturdayObligation['fulfilled_flag'])
                                        <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-sm font-medium text-emerald-700">
                                            Fulfilled
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full border border-rose-200 bg-rose-50 px-2.5 py-1 text-sm font-medium text-rose-700">
                                            Unfulfilled
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if($saturdayObligation['rule_names']->isNotEmpty())
                            <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Rules</div>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach($saturdayObligation['rule_names'] as $ruleName)
                                        <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-700">
                                            {{ $ruleName }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endif
                </x-ui.section-card>

                @if($otherObligationRows->isNotEmpty())
                    <x-ui.section-card
                        title="Obligation Lainnya"
                        :description="'Obligation non-Saturday yang ikut tercatat di employee_period_obligations.'"
                    >
                        <div class="overflow-hidden rounded-2xl border border-slate-200">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-slate-200 text-sm">
                                    <thead class="bg-slate-50 text-slate-600">
                                        <tr>
                                            <th class="px-4 py-3 text-left font-semibold">Type</th>
                                            <th class="px-4 py-3 text-left font-semibold">Rule</th>
                                            <th class="px-4 py-3 text-left font-semibold">Required</th>
                                            <th class="px-4 py-3 text-left font-semibold">Actual</th>
                                            <th class="px-4 py-3 text-left font-semibold">Excess</th>
                                            <th class="px-4 py-3 text-left font-semibold">Fulfilled</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        @foreach($otherObligationRows as $obligation)
                                            <tr>
                                                <td class="px-4 py-3 text-slate-700">{{ $obligation->obligation_type_code }}</td>
                                                <td class="px-4 py-3 text-slate-700">
                                                    {{ $obligation->workPatternRule?->rule_name ?? $obligation->workPatternRule?->rule_code ?? '-' }}
                                                </td>
                                                <td class="px-4 py-3 text-slate-700">{{ number_format((float) $obligation->required_count, 2) }}</td>
                                                <td class="px-4 py-3 text-slate-700">{{ number_format((float) $obligation->actual_count, 2) }}</td>
                                                <td class="px-4 py-3 text-slate-700">{{ number_format((float) $obligation->excess_count, 2) }}</td>
                                                <td class="px-4 py-3">
                                                    @if($obligation->fulfilled_flag)
                                                        <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">Yes</span>
                                                    @else
                                                        <span class="inline-flex items-center rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700">No</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </x-ui.section-card>
                @endif

                <x-ui.section-card
                    title="Daily Detail"
                    :description="'Detail harian payroll period: shift, jam masuk/pulang, status, late, early out, overtime, dan note.'"
                >
                    @if($dailyRows->isEmpty())
                        <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                            Tidak ada attendance_daily pada payroll period ini.
                        </div>
                    @else
                        <div class="overflow-hidden rounded-2xl border border-slate-200">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-slate-200 text-sm">
                                    <thead class="bg-slate-50 text-slate-600">
                                        <tr>
                                            <th class="px-4 py-3 text-left font-semibold">Tanggal</th>
                                            <th class="px-4 py-3 text-left font-semibold">Shift</th>
                                            <th class="px-4 py-3 text-left font-semibold">Schedule</th>
                                            <th class="px-4 py-3 text-left font-semibold">Actual</th>
                                            <th class="px-4 py-3 text-left font-semibold">Status</th>
                                            <th class="px-4 py-3 text-left font-semibold">Late</th>
                                            <th class="px-4 py-3 text-left font-semibold">Early Out</th>
                                            <th class="px-4 py-3 text-left font-semibold">Work / OT</th>
                                            <th class="px-4 py-3 text-left font-semibold">Flags</th>
                                            <th class="px-4 py-3 text-left font-semibold">Notes</th>
                                            <th class="px-4 py-3 text-left font-semibold">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        @foreach($dailyRows as $daily)
                                            @php
                                                $rowClass = ((int) $daily->late_min > 0 || (int) $daily->early_out_min > 0 || !empty($daily->review_reason_code))
                                                    ? 'bg-amber-50/40'
                                                    : '';
                                            @endphp

                                            <tr class="{{ $rowClass }}">
                                                <td class="px-4 py-3 text-slate-700">
                                                    <div class="font-medium">{{ \Illuminate\Support\Carbon::parse($daily->work_date)->format('d M Y') }}</div>
                                                    <div class="text-xs text-slate-500">{{ \Illuminate\Support\Carbon::parse($daily->work_date)->translatedFormat('l') }}</div>
                                                </td>

                                                <td class="px-4 py-3 text-slate-700">
                                                    {{ $daily->shift_label ?? '-' }}
                                                </td>

                                                <td class="px-4 py-3 text-slate-700">
                                                    <div class="text-xs">
                                                        In:
                                                        {{ $daily->scheduled_in_datetime ? \Illuminate\Support\Carbon::parse($daily->scheduled_in_datetime)->format('H:i') : '-' }}
                                                    </div>
                                                    <div class="text-xs">
                                                        Out:
                                                        {{ $daily->scheduled_out_datetime ? \Illuminate\Support\Carbon::parse($daily->scheduled_out_datetime)->format('H:i') : '-' }}
                                                    </div>
                                                </td>

                                                <td class="px-4 py-3 text-slate-700">
                                                    <div class="text-xs">
                                                        In:
                                                        {{ $daily->actual_in_datetime ? \Illuminate\Support\Carbon::parse($daily->actual_in_datetime)->format('H:i') : '-' }}
                                                    </div>
                                                    <div class="text-xs">
                                                        Out:
                                                        {{ $daily->actual_out_datetime ? \Illuminate\Support\Carbon::parse($daily->actual_out_datetime)->format('H:i') : '-' }}
                                                    </div>
                                                </td>

                                                <td class="px-4 py-3 text-slate-700">
                                                    <div class="font-medium">{{ $daily->attendance_status_code ?? '-' }}</div>
                                                    <div class="text-xs text-slate-500">{{ $daily->presence_type_code ?? '-' }}</div>
                                                    @if($daily->review_reason_code)
                                                        <div class="mt-1 text-xs text-rose-600">{{ $daily->review_reason_code }}</div>
                                                    @endif
                                                </td>

                                                <td class="px-4 py-3 text-slate-700">
                                                    {{ (int) $daily->late_min }} min
                                                </td>

                                                <td class="px-4 py-3 text-slate-700">
                                                    {{ (int) $daily->early_out_min }} min
                                                </td>

                                                <td class="px-4 py-3 text-slate-700">
                                                    <div class="text-xs">Work: {{ (int) $daily->work_min }} min</div>
                                                    <div class="text-xs">OT: {{ (int) $daily->overtime_min }} min</div>
                                                </td>

                                                <td class="px-4 py-3">
                                                    <div class="flex flex-wrap gap-1">
                                                        @if($daily->anomaly_flag)
                                                            <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700">Anomaly</span>
                                                        @endif
                                                        @if($daily->exception_flag)
                                                            <span class="inline-flex items-center rounded-full border border-violet-200 bg-violet-50 px-2 py-0.5 text-xs font-medium text-violet-700">Exception</span>
                                                        @endif
                                                        @if($daily->leave_flag)
                                                            <span class="inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-2 py-0.5 text-xs font-medium text-sky-700">Leave</span>
                                                        @endif
                                                        @if(!$daily->anomaly_flag && !$daily->exception_flag && !$daily->leave_flag)
                                                            <span class="text-xs text-slate-400">-</span>
                                                        @endif
                                                    </div>
                                                </td>

                                                <td class="px-4 py-3 text-slate-600">
                                                    <div class="max-w-xs whitespace-pre-line text-xs leading-5">
                                                        {{ $daily->notes ?: '-' }}
                                                    </div>
                                                </td>

                                                <td class="px-4 py-3">
                                                    <a
                                                        href="{{ route('attendance.daily.show', $daily->attendance_daily_id) }}"
                                                        class="text-sm font-medium text-sky-700 hover:text-sky-800"
                                                    >
                                                        Open Daily
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </x-ui.section-card>
            </div>

            <div class="space-y-6 xl:col-span-4">
                <x-ui.section-card
                    title="Quick Stats"
                    :description="'Ringkasan cepat dari contributor attendance_daily dalam payroll period ini.'"
                >
                    <div class="grid grid-cols-1 gap-3">
                        <div class="overflow-hidden rounded-xl border border-slate-200">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    <tr>
                                        <th class="w-56 bg-slate-50 px-4 py-3 text-left font-medium text-slate-600">
                                            Daily Rows
                                        </th>
                                        <td class="px-4 py-3 text-right font-semibold text-slate-900">
                                            {{ $dailyStats['row_count'] }}
                                        </td>
                                    </tr>

                                    <tr>
                                        <th class="bg-slate-50 px-4 py-3 text-left font-medium text-slate-600">
                                            Present / Absent / Leave
                                        </th>
                                        <td class="px-4 py-3 text-right font-semibold text-slate-900">
                                            {{ $dailyStats['present_count'] }}
                                            /
                                            {{ $dailyStats['absent_count'] }}
                                            /
                                            {{ $dailyStats['leave_count'] }}
                                        </td>
                                    </tr>

                                    <tr>
                                        <th class="bg-slate-50 px-4 py-3 text-left font-medium text-slate-600">
                                            Late
                                        </th>
                                        <td class="px-4 py-3 text-right font-semibold text-slate-900">
                                            {{ $dailyStats['late_count'] }}x · {{ $dailyStats['late_min_total'] }} min
                                        </td>
                                    </tr>

                                    <tr>
                                        <th class="bg-slate-50 px-4 py-3 text-left font-medium text-slate-600">
                                            Early Out
                                        </th>
                                        <td class="px-4 py-3 text-right font-semibold text-slate-900">
                                            {{ $dailyStats['early_out_count'] }}x · {{ $dailyStats['early_out_min_total'] }} min
                                        </td>
                                    </tr>

                                    <tr>
                                        <th class="bg-slate-50 px-4 py-3 text-left font-medium text-slate-600">
                                            Incomplete / Reviewable
                                        </th>
                                        <td class="px-4 py-3 text-right font-semibold text-slate-900">
                                            {{ $dailyStats['incomplete_count'] }}
                                            /
                                            {{ $dailyStats['reviewable_count'] }}
                                        </td>
                                    </tr>

                                    <tr>
                                        <th class="bg-slate-50 px-4 py-3 text-left font-medium text-slate-600">
                                            OT Rows / OT Minutes
                                        </th>
                                        <td class="px-4 py-3 text-right font-semibold text-slate-900">
                                            {{ $dailyStats['overtime_count'] }}
                                            /
                                            {{ $dailyStats['overtime_min_total'] }} min
                                        </td>
                                    </tr>

                                    <tr>
                                        <th class="bg-slate-50 px-4 py-3 text-left font-medium text-slate-600">
                                            Anomaly / Exception
                                        </th>
                                        <td class="px-4 py-3 text-right font-semibold text-slate-900">
                                            {{ $dailyStats['anomaly_count'] }}
                                            /
                                            {{ $dailyStats['exception_count'] }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </x-ui.section-card>

                @if($monthlySummary)
                    <x-ui.section-card
                        title="Monthly Summary Source"
                        :description="'Row attendance_monthly_summary yang dipakai sebagai ringkasan perilaku bulanan.'"
                    >
                        <div class="space-y-3 text-sm text-slate-700">
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-slate-500">Period</span>
                                <span class="font-medium">{{ $monthlySummary->period_year }}-{{ str_pad((string) $monthlySummary->period_month, 2, '0', STR_PAD_LEFT) }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-slate-500">Present Days</span>
                                <span class="font-medium">{{ number_format((float) $monthlySummary->present_days, 2) }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-slate-500">Absent Days</span>
                                <span class="font-medium">{{ number_format((float) $monthlySummary->absent_days, 2) }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-slate-500">Leave Days</span>
                                <span class="font-medium">{{ number_format((float) $monthlySummary->leave_days, 2) }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-slate-500">Late / Early Out</span>
                                <span class="font-medium">{{ $monthlySummary->late_count }} / {{ $monthlySummary->early_out_count }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-slate-500">Incomplete</span>
                                <span class="font-medium">{{ $monthlySummary->incomplete_count }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-slate-500">Calculated At</span>
                                <span class="font-medium">{{ optional($monthlySummary->calculated_at)->format('Y-m-d H:i:s') ?: '-' }}</span>
                            </div>
                        </div>
                    </x-ui.section-card>
                @endif
            </div>
        </div>
    </div>
@endsection