@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Payroll Period Reports"
        subtitle="Laporan per employee per payroll period yang menggabungkan period summary, monthly summary, daily attendance, dan obligations."
        :breadcrumbs="[
            ['label' => 'Summary'],
            ['label' => 'Payroll Reports'],
        ]"
    />

    <x-ui.page-section
        title="Report Filters"
        subtitle="Filter laporan payroll period berdasarkan period, branch, basis, employee, dan fokus audit."
    >
        <form method="GET" action="{{ route('summary.payroll.reports.index') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <x-ui.field label="Employee">
                <input
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Cari emp code / nama"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                >
            </x-ui.field>

            <x-ui.field label="Payroll Period">
                <select name="payroll_period_id" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                    <option value="">Semua period</option>
                    @foreach($payrollPeriods as $period)
                        <option value="{{ $period->payroll_period_id }}" @selected((string) request('payroll_period_id') === (string) $period->payroll_period_id)>
                            {{ $period->period_code }}
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

            <x-ui.field label="Basis">
                <select name="summary_basis_type_code" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                    <option value="">Semua basis</option>
                    @foreach($summaryBasisTypes as $basis)
                        <option value="{{ $basis }}" @selected(request('summary_basis_type_code') === $basis)>{{ $basis }}</option>
                    @endforeach
                </select>
            </x-ui.field>

            <x-ui.field label="Focus">
                <select name="focus" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                    <option value="">Semua</option>
                    @foreach($focusOptions as $key => $label)
                        <option value="{{ $key }}" @selected(request('focus') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </x-ui.field>

            <div class="flex items-end gap-3 xl:col-span-5">
                <x-ui.button type="submit">Search</x-ui.button>
                <x-ui.button type="button" variant="ghost" onclick="window.location='{{ route('summary.payroll.reports.index') }}'">Reset</x-ui.button>
            </div>
        </form>
    </x-ui.page-section>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat-card
            label="Rows"
            :value="$summaryStats['total_rows']"
            hint="Total report rows sesuai filter"
        />
        <x-ui.stat-card
            label="Needs Attention"
            :value="$summaryStats['deficit_rows'] + $summaryStats['deduction_rows'] + $summaryStats['unfulfilled_obligation_rows']"
            hint="Gabungan deficit, deduction, dan obligation belum terpenuhi"
        />
        <x-ui.stat-card
            label="OT Minutes"
            :value="$summaryStats['overtime_min_total']"
            hint="Akumulasi overtime minutes"
        />
        <x-ui.stat-card
            label="Deduction Days"
            :value="$summaryStats['deduction_day_total']"
            hint="Akumulasi deduction day count"
        />
    </div>

    <x-ui.table-shell>
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50/80">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Employee</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Period / Branch</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Basis / Pattern</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Core Summary</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Monthly / Daily Signals</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Obligation</th>
                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse($rows as $row)
                    @php
                        $needsAttention = (float) $row->deficit_count > 0
                            || (float) $row->deduction_day_count > 0
                            || (int) ($row->obligation_unfulfilled_count ?? 0) > 0
                            || (int) ($row->daily_anomaly_count ?? 0) > 0;

                        $rowClass = $needsAttention
                            ? 'bg-amber-50/50 hover:bg-amber-50'
                            : 'hover:bg-slate-50';
                    @endphp

                    <tr class="transition {{ $rowClass }}">
                        <td class="px-5 py-4 align-top">
                            <div class="font-medium text-slate-900">{{ $row->employee->full_name ?? '-' }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ $row->employee->emp_code ?? '-' }}</div>
                        </td>

                        <td class="px-5 py-4 align-top">
                            <div class="font-medium text-slate-900">{{ $row->payrollPeriod->period_code ?? '-' }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ $row->branch->branch_name ?? '-' }}</div>
                            @if($row->payrollPeriod)
                                <div class="mt-1 text-xs text-slate-400">
                                    {{ \Illuminate\Support\Carbon::parse($row->payrollPeriod->period_start_date)->format('d M Y') }}
                                    -
                                    {{ \Illuminate\Support\Carbon::parse($row->payrollPeriod->period_end_date)->format('d M Y') }}
                                </div>
                            @endif
                        </td>

                        <td class="px-5 py-4 align-top">
                            <div class="flex flex-wrap gap-2">
                                <x-ui.status-badge :label="$row->summary_basis_type_code" tone="info" />
                            </div>
                            <div class="mt-2 text-xs text-slate-500">
                                {{ $row->workPattern->work_pattern_name ?? $row->workPattern->work_pattern_code ?? '-' }}
                            </div>
                        </td>

                        <td class="px-5 py-4 align-top text-xs text-slate-600">
                            <div>HEK: <span class="font-medium text-slate-900">{{ $row->hek_count }}</span></div>
                            <div>Valid Present: <span class="font-medium text-slate-900">{{ $row->valid_present_count }}</span></div>
                            <div>Deficit: <span class="font-medium text-slate-900">{{ $row->deficit_count }}</span></div>
                            <div>Excess: <span class="font-medium text-slate-900">{{ $row->excess_count }}</span></div>
                            <div>Deduction: <span class="font-medium text-slate-900">{{ $row->deduction_day_count }}</span></div>
                            <div>OT Min: <span class="font-medium text-slate-900">{{ $row->overtime_min_total }}</span></div>
                        </td>

                        <td class="px-5 py-4 align-top text-xs text-slate-600">
                            <div>Daily late rows: <span class="font-medium text-slate-900">{{ (int) ($row->daily_late_count ?? 0) }}</span></div>
                            <div>Daily anomaly/review: <span class="font-medium text-slate-900">{{ (int) ($row->daily_anomaly_count ?? 0) }}</span></div>

                            <div class="mt-2 flex flex-wrap gap-1">
                                @if((float) $row->deficit_count > 0)
                                    <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-700">
                                        Deficit
                                    </span>
                                @endif
                                @if((float) $row->deduction_day_count > 0)
                                    <span class="inline-flex items-center rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-[11px] font-medium text-rose-700">
                                        Deduction
                                    </span>
                                @endif
                                @if((int) $row->overtime_min_total > 0)
                                    <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700">
                                        OT
                                    </span>
                                @endif
                            </div>
                        </td>

                        <td class="px-5 py-4 align-top text-xs text-slate-600">
                            <div>Total: <span class="font-medium text-slate-900">{{ (int) ($row->obligation_total_count ?? 0) }}</span></div>
                            <div>Unfulfilled: <span class="font-medium text-slate-900">{{ (int) ($row->obligation_unfulfilled_count ?? 0) }}</span></div>

                            @if((int) ($row->obligation_unfulfilled_count ?? 0) > 0)
                                <div class="mt-2">
                                    <span class="inline-flex items-center rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-[11px] font-medium text-rose-700">
                                        Needs follow-up
                                    </span>
                                </div>
                            @endif
                        </td>

                        <td class="px-5 py-4 align-top">
                            <div class="flex justify-end gap-2">
                                <x-ui.button
                                    variant="ghost"
                                    onclick="window.location='{{ route('summary.payroll.reports.show', $row->attendance_period_summary_id) }}'"
                                >
                                    View
                                </x-ui.button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-12">
                            <x-ui.empty-state
                                title="Belum ada report row"
                                description="Coba ubah filter atau pastikan period summary sudah terbentuk."
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-ui.table-shell>

    <div>
        {{ $rows->links() }}
    </div>
</div>
@endsection