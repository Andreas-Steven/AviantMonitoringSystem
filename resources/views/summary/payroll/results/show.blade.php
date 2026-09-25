@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Payroll Result Detail"
        :subtitle="sprintf(
            '%s · %s · %s',
            $row->employee?->emp_code ?? '-',
            $row->employee?->full_name ?? '-',
            $row->payrollPeriod?->period_code ?? '-'
        )"
        :breadcrumbs="[
            ['label' => 'Summary'],
            ['label' => 'Payroll Results', 'url' => route('summary.payroll.results.index')],
            ['label' => 'Detail'],
        ]"
    >
        <x-slot:actions>
            <div class="flex flex-wrap items-center gap-2">
                @if($amountRow)
                    <x-ui.button
                        variant="ghost"
                        onclick="window.location='{{ route('summary.payroll.amounts.show', $amountRow->payroll_attendance_amount_id) }}'"
                    >
                        Open Amount
                    </x-ui.button>
                @endif

                <x-ui.button
                    variant="ghost"
                    onclick="window.location='{{ route('summary.payroll.results.index') }}'"
                >
                    Back
                </x-ui.button>
            </div>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
        <x-ui.stat-card
            label="OT Daily Total"
            :value="$auditStats['daily_overtime_total']"
            hint="attendance_daily total OT"
        />
        <x-ui.stat-card
            label="OT Result Total"
            :value="$auditStats['result_overtime_total']"
            hint="result total OT payable"
        />
        <x-ui.stat-card
            label="Deduction Day"
            :value="$auditStats['result_deduction_day']"
            hint="deduction_day_payable"
        />
        <x-ui.stat-card
            label="Amount Layer"
            :value="$auditStats['amount_exists'] ? 'READY' : 'MISSING'"
            hint="payroll_attendance_amount"
        />
        <x-ui.stat-card
            label="Obligations"
            :value="$obligations->count()"
            hint="related obligation rows"
        />
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white px-4 py-4 shadow-sm">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
            <div class="xl:max-w-xs">
                <h3 class="text-sm font-semibold text-slate-900">Compact Result Snapshot</h3>
                <p class="mt-1 text-sm text-slate-500">
                    Ringkasan quantity layer dan sumber summary untuk row ini.
                </p>
            </div>

            <div class="grid flex-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Basis</div>
                    <div class="mt-1 text-lg font-semibold text-slate-900">{{ $row->summary_basis_type_code }}</div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">OT Payable</div>
                    <div class="mt-1 text-lg font-semibold text-slate-900">{{ $row->overtime_min_payable }}</div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Deduction Day</div>
                    <div class="mt-1 text-lg font-semibold text-slate-900">{{ $row->deduction_day_payable }}</div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">OT Workday</div>
                    <div class="mt-1 text-lg font-semibold text-slate-900">{{ $row->overtime_workday_min_payable }}</div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">OT Holiday</div>
                    <div class="mt-1 text-lg font-semibold text-slate-900">{{ $row->overtime_holiday_min_payable }}</div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">OT Offday</div>
                    <div class="mt-1 text-lg font-semibold text-slate-900">{{ $row->overtime_offday_min_payable }}</div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Unfulfilled</div>
                    <div class="mt-1 text-lg font-semibold text-slate-900">{{ $row->obligation_unfulfilled_count }}</div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Excess</div>
                    <div class="mt-1 text-lg font-semibold text-slate-900">{{ $row->obligation_excess_count }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
        <x-ui.section-card title="Core Information" subtitle="Info utama result row dan source summary-nya.">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <div class="text-xs text-slate-500">Employee</div>
                    <div class="mt-1 font-medium text-slate-900">
                        {{ $row->employee?->emp_code ?? '-' }} · {{ $row->employee?->full_name ?? '-' }}
                    </div>
                </div>

                <div>
                    <div class="text-xs text-slate-500">Payroll Period</div>
                    <div class="mt-1 font-medium text-slate-900">{{ $row->payrollPeriod?->period_code ?? '-' }}</div>
                </div>

                <div>
                    <div class="text-xs text-slate-500">Branch</div>
                    <div class="mt-1 font-medium text-slate-900">{{ $row->branch?->branch_name ?? '-' }}</div>
                </div>

                <div>
                    <div class="text-xs text-slate-500">Work Pattern</div>
                    <div class="mt-1 font-medium text-slate-900">{{ $row->workPattern?->work_pattern_name ?? '-' }}</div>
                </div>

                <div>
                    <div class="text-xs text-slate-500">Calculated At</div>
                    <div class="mt-1 font-medium text-slate-900">
                        {{ optional($row->calculated_at)->format('Y-m-d H:i:s') ?: '-' }}
                    </div>
                </div>
            </div>

            <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Notes</div>
                <div class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $row->notes ?: '-' }}</div>
            </div>

            @if($periodSummary)
                <div class="mt-4 rounded-2xl border border-slate-200 bg-white px-4 py-4">
                    <div class="mb-3 text-sm font-semibold text-slate-900">Source Period Summary</div>

                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4 text-sm text-slate-700">
                        <div>HEK: <span class="font-medium text-slate-900">{{ $periodSummary->hek_count }}</span></div>
                        <div>Valid Present: <span class="font-medium text-slate-900">{{ $periodSummary->valid_present_count }}</span></div>
                        <div>Deficit: <span class="font-medium text-slate-900">{{ $periodSummary->deficit_count }}</span></div>
                        <div>Excess: <span class="font-medium text-slate-900">{{ $periodSummary->excess_count }}</span></div>
                        <div>OT Day Count: <span class="font-medium text-slate-900">{{ $periodSummary->overtime_day_count }}</span></div>
                        <div>OT Min Total: <span class="font-medium text-slate-900">{{ $periodSummary->overtime_min_total }}</span></div>
                        <div>Leave Used: <span class="font-medium text-slate-900">{{ $periodSummary->leave_quota_used_count }}</span></div>
                        <div>Deduction Day: <span class="font-medium text-slate-900">{{ $periodSummary->deduction_day_count }}</span></div>
                    </div>
                </div>
            @else
                <div class="mt-4">
                    <x-ui.empty-state
                        title="No source summary found"
                        description="Tidak ada attendance_period_summary yang cocok."
                    />
                </div>
            @endif
        </x-ui.section-card>

        <x-ui.section-card title="Related Amount / Obligation Audit" subtitle="Audit cepat untuk layer berikutnya dan obligation rows.">
            <div class="space-y-4">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                    <div class="mb-3 text-sm font-semibold text-slate-900">Amount Layer</div>

                    @if($amountRow)
                        <div class="grid gap-3 text-sm text-slate-700">
                            <div>Overtime Amount: <span class="font-medium text-slate-900">{{ number_format((float) $amountRow->overtime_amount, 2) }}</span></div>
                            <div>Deduction Amount: <span class="font-medium text-slate-900">{{ number_format((float) $amountRow->deduction_amount, 2) }}</span></div>
                            <div>Net Attendance Amount: <span class="font-medium text-slate-900">{{ number_format((float) $amountRow->net_attendance_amount, 2) }}</span></div>
                        </div>
                    @else
                        <div class="text-sm text-slate-500">Belum ada payroll_attendance_amount untuk result ini.</div>
                    @endif
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4">
                    <div class="mb-3 text-sm font-semibold text-slate-900">Obligation Rows</div>

                    <div class="space-y-3">
                        @forelse($obligations as $obligation)
                            <div class="rounded-2xl border border-slate-200 px-4 py-3">
                                <div class="flex flex-wrap gap-2">
                                    <x-ui.status-badge :label="$obligation->obligation_type_code" tone="info" />
                                    <x-ui.status-badge
                                        :label="$obligation->fulfilled_flag ? 'FULFILLED' : 'UNFULFILLED'"
                                        :tone="$obligation->fulfilled_flag ? 'success' : 'warning'"
                                    />
                                </div>

                                <div class="mt-3 grid gap-1 text-xs text-slate-600">
                                    <div>Required: <span class="font-medium text-slate-900">{{ $obligation->required_count }}</span></div>
                                    <div>Actual: <span class="font-medium text-slate-900">{{ $obligation->actual_count }}</span></div>
                                    <div>Excess: <span class="font-medium text-slate-900">{{ $obligation->excess_count }}</span></div>
                                </div>
                            </div>
                        @empty
                            <div class="text-sm text-slate-500">Tidak ada obligation terkait.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </x-ui.section-card>
    </div>

    <x-ui.section-card title="Daily Contributors" subtitle="attendance_daily dalam payroll period untuk investigasi overtime dan anomaly.">
        <div class="space-y-3">
            @forelse($dailyRows as $daily)
                <div class="rounded-2xl border border-slate-200 px-4 py-3">
                    <div class="flex flex-col gap-3 xl:flex-row xl:items-start xl:justify-between">
                        <div>
                            <div class="text-sm font-medium text-slate-900">
                                {{ \Carbon\Carbon::parse($daily->work_date)->format('Y-m-d') }}
                            </div>
                            <div class="mt-1 text-xs text-slate-500">
                                Status: {{ $daily->attendance_status_code }} · Presence: {{ $daily->presence_type_code ?: '-' }}
                            </div>

                            <div class="mt-2 flex flex-wrap gap-2 text-xs">
                                <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-slate-700">
                                    OT {{ (int) $daily->overtime_min }} min
                                </span>
                                @if((int) $daily->overtime_workday_min > 0)
                                    <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-2.5 py-1 text-slate-700">
                                        WD {{ (int) $daily->overtime_workday_min }}
                                    </span>
                                @endif
                                @if((int) $daily->overtime_holiday_min > 0)
                                    <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-2.5 py-1 text-slate-700">
                                        HOL {{ (int) $daily->overtime_holiday_min }}
                                    </span>
                                @endif
                                @if((int) $daily->overtime_offday_min > 0)
                                    <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-2.5 py-1 text-slate-700">
                                        OFF {{ (int) $daily->overtime_offday_min }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            @if($daily->anomaly_flag)
                                <x-ui.status-badge label="ANOMALY" tone="warning" />
                            @endif
                            @if($daily->exception_flag)
                                <x-ui.status-badge label="EXCEPTION" tone="info" />
                            @endif
                            @if($daily->leave_flag)
                                <x-ui.status-badge label="LEAVE" tone="info" />
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <x-ui.empty-state
                    title="No daily contributors"
                    description="Tidak ada attendance_daily di period ini."
                />
            @endforelse
        </div>
    </x-ui.section-card>
</div>
@endsection