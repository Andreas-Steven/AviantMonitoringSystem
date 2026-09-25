@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Payroll Amount Detail"
        :subtitle="sprintf(
            '%s · %s · %s',
            $row->employee?->emp_code ?? '-',
            $row->employee?->full_name ?? '-',
            $row->payrollPeriod?->period_code ?? '-'
        )"
        :breadcrumbs="[
            ['label' => 'Summary'],
            ['label' => 'Payroll Amounts', 'url' => route('summary.payroll.amounts.index')],
            ['label' => 'Detail'],
        ]"
    >
        <x-slot:actions>
            <div class="flex flex-wrap items-center gap-2">
                @if($resultRow)
                    <x-ui.button
                        variant="ghost"
                        onclick="window.location='{{ route('summary.payroll.results.show', $resultRow->payroll_attendance_result_id) }}'"
                    >
                        Open Result
                    </x-ui.button>
                @endif

                <x-ui.button
                    variant="ghost"
                    onclick="window.location='{{ route('summary.payroll.amounts.index') }}'"
                >
                    Back
                </x-ui.button>
            </div>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat-card
            label="OT Amount"
            :value="number_format((float) $row->overtime_amount, 2)"
            hint="stored overtime amount"
        />
        <x-ui.stat-card
            label="Deduction Amount"
            :value="number_format((float) $row->deduction_amount, 2)"
            hint="stored deduction amount"
        />
        <x-ui.stat-card
            label="Net Amount"
            :value="number_format((float) $row->net_attendance_amount, 2)"
            hint="stored net amount"
        />
        <x-ui.stat-card
            label="Expected Net"
            :value="$expectedNetAmount !== null ? number_format((float) $expectedNetAmount, 2) : '-'"
            hint="recomputed from policy + result"
        />
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white px-4 py-4 shadow-sm">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
            <div class="xl:max-w-xs">
                <h3 class="text-sm font-semibold text-slate-900">Compact Amount Snapshot</h3>
                <p class="mt-1 text-sm text-slate-500">
                    Ringkasan money layer, rate policy, dan expected calculation.
                </p>
            </div>

            <div class="grid flex-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-4">
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-emerald-700">Stored OT Amount</div>
                    <div class="mt-1 text-lg font-semibold text-emerald-900">{{ number_format((float) $row->overtime_amount, 2) }}</div>
                </div>

                <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-rose-700">Stored Deduction</div>
                    <div class="mt-1 text-lg font-semibold text-rose-900">{{ number_format((float) $row->deduction_amount, 2) }}</div>
                </div>

                <div class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-sky-700">Stored Net</div>
                    <div class="mt-1 text-lg font-semibold text-sky-900">{{ number_format((float) $row->net_attendance_amount, 2) }}</div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Expected Net</div>
                    <div class="mt-1 text-lg font-semibold text-slate-900">
                        {{ $expectedNetAmount !== null ? number_format((float) $expectedNetAmount, 2) : '-' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
        <x-ui.section-card title="Core Amount Information" subtitle="Stored money layer dan audit perhitungan dasarnya.">
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
                    <div class="text-xs text-slate-500">Overtime Min Payable</div>
                    <div class="mt-1 font-medium text-slate-900">{{ $row->overtime_min_payable }}</div>
                </div>

                <div>
                    <div class="text-xs text-slate-500">Deduction Day Payable</div>
                    <div class="mt-1 font-medium text-slate-900">{{ $row->deduction_day_payable }}</div>
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

            @if($resultRow)
                <div class="mt-4 rounded-2xl border border-slate-200 bg-white px-4 py-4">
                    <div class="mb-3 text-sm font-semibold text-slate-900">Related Result Snapshot</div>

                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3 text-sm text-slate-700">
                        <div>Basis: <span class="font-medium text-slate-900">{{ $resultRow->summary_basis_type_code }}</span></div>
                        <div>OT Total: <span class="font-medium text-slate-900">{{ $resultRow->overtime_min_payable }}</span></div>
                        <div>Deduction Day: <span class="font-medium text-slate-900">{{ $resultRow->deduction_day_payable }}</span></div>
                        <div>OT Workday: <span class="font-medium text-slate-900">{{ $resultRow->overtime_workday_min_payable }}</span></div>
                        <div>OT Holiday: <span class="font-medium text-slate-900">{{ $resultRow->overtime_holiday_min_payable }}</span></div>
                        <div>OT Offday: <span class="font-medium text-slate-900">{{ $resultRow->overtime_offday_min_payable }}</span></div>
                    </div>
                </div>
            @else
                <div class="mt-4">
                    <x-ui.empty-state
                        title="Result not found"
                        description="Tidak ada payroll result yang terkait."
                    />
                </div>
            @endif
        </x-ui.section-card>

        <x-ui.section-card title="Rate Policy Audit" subtitle="Resolved payroll rate policy dan expected recomputation.">
            <div class="space-y-4">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                    <div class="mb-3 text-sm font-semibold text-slate-900">Resolved Policy</div>

                    @if($policy)
                        <div class="grid gap-2 text-sm text-slate-700">
                            <div>Policy: <span class="font-medium text-slate-900">{{ $policy->policy_code }}</span></div>
                            <div>OT Rate / Min: <span class="font-medium text-slate-900">{{ number_format((float) $policy->overtime_rate_per_min, 2) }}</span></div>
                            <div>Deduction Rate / Day: <span class="font-medium text-slate-900">{{ number_format((float) $policy->deduction_rate_per_day, 2) }}</span></div>
                            <div>Workday Multiplier: <span class="font-medium text-slate-900">{{ number_format((float) $policy->workday_ot_multiplier, 2) }}</span></div>
                            <div>Holiday Multiplier: <span class="font-medium text-slate-900">{{ number_format((float) $policy->holiday_ot_multiplier, 2) }}</span></div>
                            <div>Offday Multiplier: <span class="font-medium text-slate-900">{{ number_format((float) $policy->offday_ot_multiplier, 2) }}</span></div>
                        </div>
                    @else
                        <div class="text-sm text-slate-500">Payroll rate policy tidak berhasil di-resolve.</div>
                    @endif
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4">
                    <div class="mb-3 text-sm font-semibold text-slate-900">Expected Recalculation</div>

                    <div class="grid gap-2 text-sm text-slate-700">
                        <div>
                            Expected Overtime Amount:
                            <span class="font-medium text-slate-900">
                                {{ $expectedOvertimeAmount !== null ? number_format((float) $expectedOvertimeAmount, 2) : '-' }}
                            </span>
                        </div>
                        <div>
                            Expected Deduction Amount:
                            <span class="font-medium text-slate-900">
                                {{ $expectedDeductionAmount !== null ? number_format((float) $expectedDeductionAmount, 2) : '-' }}
                            </span>
                        </div>
                        <div>
                            Expected Net Amount:
                            <span class="font-medium text-slate-900">
                                {{ $expectedNetAmount !== null ? number_format((float) $expectedNetAmount, 2) : '-' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui.section-card>
    </div>

    <x-ui.section-card title="Overtime Contributors" subtitle="attendance_daily rows dengan overtime di payroll period ini.">
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
                    </div>
                </div>
            @empty
                <x-ui.empty-state
                    title="No overtime contributors"
                    description="Tidak ada attendance_daily overtime di period ini."
                />
            @endforelse
        </div>
    </x-ui.section-card>
</div>
@endsection