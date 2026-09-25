@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Payroll Period Report"
        subtitle="Ringkasan payroll per employee per period dengan drill-down ke summary, obligations, dan attendance daily."
        :breadcrumbs="[
            ['label' => 'Summary'],
            ['label' => 'Payroll Reports', 'url' => route('summary.payroll.reports.index')],
            ['label' => $row->employee->emp_code ?? 'Detail'],
        ]"
    >
        <x-slot:actions>
            <div class="flex flex-wrap gap-2">
                <x-ui.button
                    variant="ghost"
                    onclick="window.location='{{ route('summary.payroll.reports.index') }}'"
                >
                    Back to Reports
                </x-ui.button>

                @if(Route::has('summary.period.show'))
                    <x-ui.button
                        variant="ghost"
                        onclick="window.location='{{ route('summary.period.show', $row->attendance_period_summary_id) }}'"
                    >
                        Open Period Summary
                    </x-ui.button>
                @endif
            </div>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="flex items-center gap-3">
        <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-medium
            {{ $row->summary_basis_type_code === 'HEK'
                ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                : 'border-sky-200 bg-sky-50 text-sky-700' }}">
            {{ $row->summary_basis_type_code }}
        </span>
    </div>

    <x-ui.page-section title="Report Header" subtitle="Identitas employee, payroll period, branch, pattern, dan basis report.">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Employee</div>
                <div class="mt-1 font-semibold text-slate-900">{{ $row->employee->full_name ?? '-' }}</div>
                <div class="mt-1 text-xs text-slate-500">{{ $row->employee->emp_code ?? '-' }}</div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Payroll Period</div>
                <div class="mt-1 font-semibold text-slate-900">{{ $row->payrollPeriod->period_code ?? '-' }}</div>
                @if($row->payrollPeriod)
                    <div class="mt-1 text-xs text-slate-500">
                        {{ \Illuminate\Support\Carbon::parse($row->payrollPeriod->period_start_date)->format('d M Y') }}
                        -
                        {{ \Illuminate\Support\Carbon::parse($row->payrollPeriod->period_end_date)->format('d M Y') }}
                    </div>
                @endif
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Branch / Basis</div>
                <div class="mt-1 font-semibold text-slate-900">{{ $row->branch->branch_name ?? '-' }}</div>
                <div class="mt-1 text-xs text-slate-500">{{ $row->summary_basis_type_code ?? '-' }}</div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Work Pattern</div>
                <div class="mt-1 font-semibold text-slate-900">
                    {{ $row->workPattern->work_pattern_name ?? $row->workPattern->work_pattern_code ?? '-' }}
                </div>
                <div class="mt-1 text-xs text-slate-500">
                    Calculated: {{ optional($row->calculated_at)->format('Y-m-d H:i:s') ?: '-' }}
                </div>
            </div>
        </div>
    </x-ui.page-section>

    @if($row->summary_basis_type_code === 'HEK')
        <x-ui.page-section title="Primary Metrics" subtitle="Basis utama untuk work pattern HEK.">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
                <x-ui.stat-card label="HEK" :value="$reportMetrics['hek_count']" />
                <x-ui.stat-card label="Valid Present" :value="$reportMetrics['valid_present_count']" />
                <x-ui.stat-card label="Deficit" :value="$reportMetrics['deficit_count']" />
                <x-ui.stat-card label="Excess" :value="$reportMetrics['excess_count']" />
                <x-ui.stat-card label="Deduction Days" :value="$row->deduction_day_count" />
                <x-ui.stat-card label="OT Minutes" :value="$reportMetrics['overtime_min_total']" />
            </div>
        </x-ui.page-section>

        <x-ui.page-section
            title="Payroll Amount"
            subtitle="Konversi dari attendance result ke nominal uang."
        >
            @if($amount)
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">

                    <x-ui.stat-card
                        label="Overtime Amount"
                        :value="number_format($amount->overtime_amount, 0)"
                    />

                    <x-ui.stat-card
                        label="Deduction Amount"
                        :value="number_format($amount->deduction_amount, 0)"
                    />

                    <x-ui.stat-card
                        label="Net Attendance Amount"
                        :value="number_format($amount->net_attendance_amount, 0)"
                    />

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <div class="text-xs uppercase tracking-wide text-slate-500">Calculated At</div>
                        <div class="mt-1 font-semibold text-slate-900">
                            {{ optional($amount->calculated_at)->format('Y-m-d H:i:s') }}
                        </div>
                    </div>

                </div>

                <div class="mt-4 text-xs text-slate-500">
                    {{ $amount->notes }}
                </div>

            @else
                <x-ui.empty-state
                    title="Payroll amount belum dihitung"
                    description="Jalankan PayrollAttendanceAmountCalculatorService terlebih dahulu."
                />
            @endif
        </x-ui.page-section>

        <x-ui.page-section title="Additional Snapshot" subtitle="Informasi tambahan untuk membantu pembacaan report.">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <x-ui.stat-card label="Present Days" :value="$reportMetrics['present_days']" />
                <x-ui.stat-card label="Late Count" :value="$reportMetrics['late_count']" />
                <x-ui.stat-card label="Incomplete" :value="$reportMetrics['incomplete_count']" />
                <x-ui.stat-card label="Leave Quota Used" :value="$row->leave_quota_used_count" />
            </div>
        </x-ui.page-section>
    @else
        <x-ui.page-section title="Primary Metrics" subtitle="Basis utama payroll untuk work pattern obligation.">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                <x-ui.stat-card label="Required" :value="$obligationSummary['required_total']" />
                <x-ui.stat-card label="Actual" :value="$obligationSummary['actual_total']" />
                <x-ui.stat-card label="Unfulfilled Rules" :value="$obligationSummary['unfulfilled_rows']" />
                <x-ui.stat-card label="Deduction Days" :value="$row->deduction_day_count" />
                <x-ui.stat-card label="OT Minutes" :value="$reportMetrics['overtime_min_total']" />
            </div>
        </x-ui.page-section>

        <x-ui.page-section title="Additional Snapshot" subtitle="Informasi tambahan untuk membantu pembacaan report.">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <x-ui.stat-card label="HEK (Info)" :value="$reportMetrics['hek_count']" />
                <x-ui.stat-card label="Valid Present (Info)" :value="$reportMetrics['valid_present_count']" />
                <x-ui.stat-card label="Present Days" :value="$reportMetrics['present_days']" />
                <x-ui.stat-card label="Late Count" :value="$reportMetrics['late_count']" />
            </div>
        </x-ui.page-section>

        <div class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">
            HEK dan Valid Present pada work pattern obligation ditampilkan sebagai informasi tambahan.
            Penilaian utama tetap mengacu ke obligation rule per bucket, terutama weekday dan Saturday obligation.
        </div>
    @endif

    <x-ui.page-section title="Attention Flags" subtitle="Indikator cepat untuk payroll review dan audit.">
        <div class="flex flex-wrap gap-2">
            @forelse($attentionFlags->where('active', true) as $flag)
                @php
                    $classes = match ($flag['tone']) {
                        'danger' => 'border-rose-200 bg-rose-50 text-rose-700',
                        'success' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                        default => 'border-amber-200 bg-amber-50 text-amber-700',
                    };
                @endphp
                <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-medium {{ $classes }}">
                    {{ $flag['label'] }}
                </span>
            @empty
                <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-medium text-slate-600">
                    No major attention flag
                </span>
            @endforelse
        </div>
    </x-ui.page-section>

    <div class="grid gap-6 xl:grid-cols-2">
        <x-ui.page-section
            :title="$row->summary_basis_type_code === 'HEK' ? 'HEK Summary Layer' : 'Summary Snapshot'"
            :subtitle="$row->summary_basis_type_code === 'HEK'
                ? 'Basis utama untuk work pattern HEK.'
                : 'Snapshot summary umum. Untuk basis obligation, keputusan utama tetap mengacu ke obligation rows.'"
        >
            <div class="grid gap-3 md:grid-cols-2 text-sm">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="text-xs uppercase tracking-wide text-slate-500">Overtime Day Count</div>
                    <div class="mt-1 font-semibold text-slate-900">{{ $reportMetrics['overtime_day_count'] }}</div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="text-xs uppercase tracking-wide text-slate-500">Leave Quota Used</div>
                    <div class="mt-1 font-semibold text-slate-900">{{ $row->leave_quota_used_count }}</div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="text-xs uppercase tracking-wide text-slate-500">Early Out Count</div>
                    <div class="mt-1 font-semibold text-slate-900">{{ $reportMetrics['early_out_count'] }}</div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="text-xs uppercase tracking-wide text-slate-500">Late Minutes</div>
                    <div class="mt-1 font-semibold text-slate-900">{{ $reportMetrics['late_min_total'] }}</div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 md:col-span-2">
                    <div class="text-xs uppercase tracking-wide text-slate-500">Notes</div>
                    <div class="mt-1 text-slate-700">{{ $row->notes ?: '-' }}</div>
                </div>
            </div>
        </x-ui.page-section>

        <x-ui.page-section title="Monthly Summary Layer" subtitle="Snapshot monthly summary untuk payroll year + month dari payroll period.">
            @if($monthlySummary)
                <div class="grid gap-3 md:grid-cols-2 text-sm">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <div class="text-xs uppercase tracking-wide text-slate-500">Present / Absent</div>
                        <div class="mt-1 font-semibold text-slate-900">{{ $monthlySummary->present_days }} / {{ $monthlySummary->absent_days }}</div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <div class="text-xs uppercase tracking-wide text-slate-500">Leave / Sick / Permission</div>
                        <div class="mt-1 font-semibold text-slate-900">{{ $monthlySummary->leave_days }} / {{ $monthlySummary->sick_days }} / {{ $monthlySummary->permission_days }}</div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <div class="text-xs uppercase tracking-wide text-slate-500">Late</div>
                        <div class="mt-1 font-semibold text-slate-900">{{ $monthlySummary->late_count }}x ({{ $monthlySummary->late_min_total }} min)</div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <div class="text-xs uppercase tracking-wide text-slate-500">Early Out / Incomplete</div>
                        <div class="mt-1 font-semibold text-slate-900">{{ $monthlySummary->early_out_count }}x / {{ $monthlySummary->incomplete_count }}</div>
                    </div>
                </div>
            @else
                <x-ui.empty-state
                    title="Monthly summary tidak ditemukan"
                    description="Report tetap bisa dibuka, tetapi layer monthly summary belum tersedia untuk period ini."
                />
            @endif
        </x-ui.page-section>
    </div>

    <x-ui.page-section
        :title="$row->summary_basis_type_code === 'OBLIGATION' ? 'Primary Obligation Layer' : 'Obligation Layer'"
        :subtitle="$row->summary_basis_type_code === 'OBLIGATION'
            ? 'Detail kewajiban per rule sebagai basis utama payroll.'
            : 'Informasi tambahan obligation bila tersedia.'"
    >
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4 mb-5">
            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                <div class="text-xs uppercase tracking-wide text-slate-500">Total Rows</div>
                <div class="mt-1 font-semibold text-slate-900">{{ $obligationSummary['total_rows'] }}</div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                <div class="text-xs uppercase tracking-wide text-slate-500">Fulfilled</div>
                <div class="mt-1 font-semibold text-slate-900">{{ $obligationSummary['fulfilled_rows'] }}</div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                <div class="text-xs uppercase tracking-wide text-slate-500">Unfulfilled</div>
                <div class="mt-1 font-semibold text-slate-900">{{ $obligationSummary['unfulfilled_rows'] }}</div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                <div class="text-xs uppercase tracking-wide text-slate-500">Required / Actual / Excess</div>
                <div class="mt-1 font-semibold text-slate-900">
                    {{ $obligationSummary['required_total'] }} / {{ $obligationSummary['actual_total'] }} / {{ $obligationSummary['excess_total'] }}
                </div>
            </div>
        </div>

        <x-ui.table-shell>
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50/80">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Type</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Rule</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Required</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Actual</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Excess</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($obligationRows as $item)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-4">{{ $item->obligation_type_code }}</td>
                            <td class="px-5 py-4">
                                {{ $item->workPatternRule?->rule_name ?? $item->workPatternRule?->rule_code ?? '-' }}
                            </td>
                            <td class="px-5 py-4">{{ $item->required_count }}</td>
                            <td class="px-5 py-4">{{ $item->actual_count }}</td>
                            <td class="px-5 py-4">{{ $item->excess_count }}</td>
                            <td class="px-5 py-4">
                                @if($item->fulfilled_flag)
                                    <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                        Fulfilled
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full border border-rose-200 bg-rose-50 px-2.5 py-1 text-xs font-medium text-rose-700">
                                        Unfulfilled
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10">
                                <x-ui.empty-state
                                    title="Tidak ada obligation row"
                                    description="Employee ini tidak memiliki obligation row untuk payroll period yang dipilih."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-ui.table-shell>
    </x-ui.page-section>

    <x-ui.page-section title="Attendance Daily Snapshot" subtitle="Data harian untuk audit dan verifikasi.">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5 mb-5">
            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                <div class="text-xs uppercase tracking-wide text-slate-500">Rows</div>
                <div class="mt-1 font-semibold text-slate-900">{{ $dailyStats['row_count'] }}</div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                <div class="text-xs uppercase tracking-wide text-slate-500">Present / Absent / Leave</div>
                <div class="mt-1 font-semibold text-slate-900">{{ $dailyStats['present_count'] }} / {{ $dailyStats['absent_count'] }} / {{ $dailyStats['leave_count'] }}</div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                <div class="text-xs uppercase tracking-wide text-slate-500">Late / Early Out</div>
                <div class="mt-1 font-semibold text-slate-900">{{ $dailyStats['late_count'] }} / {{ $dailyStats['early_out_count'] }}</div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                <div class="text-xs uppercase tracking-wide text-slate-500">OT WD / HOL / OFF</div>
                <div class="mt-1 font-semibold text-slate-900">
                    {{ $dailyStats['overtime_workday_min_total'] }} / {{ $dailyStats['overtime_holiday_min_total'] }} / {{ $dailyStats['overtime_offday_min_total'] }}
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                <div class="text-xs uppercase tracking-wide text-slate-500">Anomaly / Review</div>
                <div class="mt-1 font-semibold text-slate-900">
                    {{ $dailyStats['anomaly_count'] + $dailyStats['exception_count'] }} / {{ $dailyStats['review_count'] }}
                </div>
            </div>
        </div>

        <x-ui.table-shell>
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50/80">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Date</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status / Shift</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Actual In / Out</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Work / Late / EO</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">OT</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Flags</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($dailyRows as $item)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-4 align-top">
                                <div class="font-medium text-slate-900">
                                    {{ \Illuminate\Support\Carbon::parse($item->work_date)->format('d M Y') }}
                                </div>
                            </td>

                            <td class="px-5 py-4 align-top">
                                <div class="font-medium text-slate-900">{{ $item->attendance_status_code }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $item->shift_label ?: '-' }}</div>
                            </td>

                            <td class="px-5 py-4 align-top text-xs text-slate-600">
                                <div>In: <span class="font-medium text-slate-900">{{ $item->actual_in_datetime ? \Illuminate\Support\Carbon::parse($item->actual_in_datetime)->format('H:i:s') : '-' }}</span></div>
                                <div>Out: <span class="font-medium text-slate-900">{{ $item->actual_out_datetime ? \Illuminate\Support\Carbon::parse($item->actual_out_datetime)->format('H:i:s') : '-' }}</span></div>
                            </td>

                            <td class="px-5 py-4 align-top text-xs text-slate-600">
                                <div>Work: <span class="font-medium text-slate-900">{{ $item->work_min }}</span></div>
                                <div>Late: <span class="font-medium text-slate-900">{{ $item->late_min }}</span></div>
                                <div>EO: <span class="font-medium text-slate-900">{{ $item->early_out_min }}</span></div>
                            </td>

                            <td class="px-5 py-4 align-top text-xs text-slate-600">
                                <div>Total: <span class="font-medium text-slate-900">{{ $item->overtime_min }}</span></div>
                                <div>WD/HOL/OFF: <span class="font-medium text-slate-900">{{ $item->overtime_workday_min }}/{{ $item->overtime_holiday_min }}/{{ $item->overtime_offday_min }}</span></div>
                            </td>

                            <td class="px-5 py-4 align-top">
                                <div class="flex flex-wrap gap-1">
                                    @if($item->anomaly_flag)
                                        <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-700">Anomaly</span>
                                    @endif
                                    @if($item->exception_flag)
                                        <span class="inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-2 py-0.5 text-[11px] font-medium text-sky-700">Exception</span>
                                    @endif
                                    @if($item->leave_flag)
                                        <span class="inline-flex items-center rounded-full border border-indigo-200 bg-indigo-50 px-2 py-0.5 text-[11px] font-medium text-indigo-700">Leave</span>
                                    @endif
                                    @if($item->review_reason_code)
                                        <span class="inline-flex items-center rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-[11px] font-medium text-rose-700">{{ $item->review_reason_code }}</span>
                                    @endif
                                </div>
                            </td>

                            <td class="px-5 py-4 align-top text-xs text-slate-600">
                                {{ $item->notes ?: '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-10">
                                <x-ui.empty-state
                                    title="Tidak ada daily breakdown"
                                    description="Belum ada attendance_daily dalam rentang payroll period ini."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-ui.table-shell>
    </x-ui.page-section>
</div>
@endsection