@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Laporan Periode Payroll"
        subtitle="Daftar laporan payroll period per employee untuk drill-down ke HEK, deficit, excess, late, early out, incomplete, kewajiban Sabtu, dan detail harian."
        :breadcrumbs="[
            ['label' => 'Summary'],
            ['label' => 'Period Summary'],
        ]"
    />

    <x-ui.page-section
        title="Summary Filters"
        subtitle="Filter berdasarkan payroll period, basis summary, branch, employee, dan focus monitoring."
    >
        <form method="GET" action="{{ route('summary.period.index') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <x-ui.field label="Employee">
                <input
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Cari emp code / nama"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
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

            <x-ui.field label="Summary Basis">
                <select name="summary_basis_type_code" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                    <option value="">Semua basis</option>
                    @foreach($summaryBasisTypes as $basis)
                        <option value="{{ $basis }}" @selected((string) request('summary_basis_type_code') === (string) $basis)>
                            {{ $basis }}
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

            <x-ui.field label="Focus">
                <select name="focus" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                    <option value="">Semua focus</option>
                    <option value="needs_attention" @selected(request('focus') === 'needs_attention')>Needs Attention</option>
                    <option value="deficit" @selected(request('focus') === 'deficit')>Deficit</option>
                    <option value="excess" @selected(request('focus') === 'excess')>Excess</option>
                    <option value="deduction" @selected(request('focus') === 'deduction')>Deduction</option>
                    <option value="overtime" @selected(request('focus') === 'overtime')>Overtime</option>
                    <option value="late" @selected(request('focus') === 'late')>Late</option>
                    <option value="early_out" @selected(request('focus') === 'early_out')>Early Out</option>
                    <option value="incomplete" @selected(request('focus') === 'incomplete')>Incomplete / Review</option>
                </select>
            </x-ui.field>

            <div class="flex items-end gap-3 xl:col-span-5">
                <x-ui.button type="submit">Search</x-ui.button>
                <x-ui.button type="button" variant="ghost" onclick="window.location='{{ route('summary.period.index') }}'">
                    Reset
                </x-ui.button>
            </div>
        </form>
    </x-ui.page-section>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat-card
            label="Rows"
            :value="$rows->total()"
            hint="Total period summary hasil filter"
        />

        <x-ui.stat-card
            label="Needs Attention"
            :value="$rows->getCollection()->filter(fn($row) =>
                (float) $row->deficit_count > 0
                || (float) $row->deduction_day_count > 0
                || (int) $row->late_count > 0
                || (int) $row->early_out_count > 0
                || (int) $row->incomplete_count > 0
            )->count()"
            hint="Deficit, deduction, late, early out, atau incomplete"
        />

        <x-ui.stat-card
            label="Late"
            :value="$rows->getCollection()->sum(fn($row) => (int) $row->late_count)"
            hint="Jumlah hari terlambat pada halaman ini"
        />

        <x-ui.stat-card
            label="Early Out"
            :value="$rows->getCollection()->sum(fn($row) => (int) $row->early_out_count)"
            hint="Jumlah hari pulang cepat pada halaman ini"
        />
    </div>

    <x-ui.table-shell>
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50/80">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Employee</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Period / Basis</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Branch / Pattern</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Core Result</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Payroll Impact</th>
                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse($rows as $row)
                    @php
                        $needsAttention =
                            (float) $row->deficit_count > 0
                            || (float) $row->deduction_day_count > 0
                            || (int) $row->late_count > 0
                            || (int) $row->early_out_count > 0
                            || (int) $row->incomplete_count > 0;
                        $rowClass = $needsAttention
                            ? 'bg-amber-50/60 hover:bg-amber-50'
                            : 'hover:bg-slate-50';
                    @endphp

                    <tr class="transition {{ $rowClass }}">
                        <td class="px-5 py-4">
                            <div class="font-medium text-slate-900">{{ $row->employee->full_name ?? '-' }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ $row->employee->emp_code ?? '-' }}</div>
                        </td>

                        <td class="px-5 py-4">
                            <div class="font-medium text-slate-900">
                                {{ $row->payrollPeriod->period_code ?? '-' }}
                            </div>
                            <div class="mt-1 text-xs text-slate-500">
                                Basis: {{ $row->summary_basis_type_code ?? '-' }}
                            </div>
                            <div class="mt-1 text-xs text-slate-500">
                                Calculated: {{ optional($row->calculated_at)->format('Y-m-d H:i:s') ?: '-' }}
                            </div>
                        </td>

                        <td class="px-5 py-4 text-slate-600">
                            <div class="font-medium text-slate-900">
                                {{ $row->branch->branch_name ?? '-' }}
                            </div>
                            <div class="mt-1 text-xs text-slate-500">
                                {{ $row->workPattern->work_pattern_name ?? $row->workPattern->work_pattern_code ?? '-' }}
                            </div>
                        </td>

                        <td class="px-5 py-4 text-slate-600">
                            <div class="grid gap-1 text-xs">
                                <div>HEK: <span class="font-medium text-slate-900">{{ $row->hek_count }}</span></div>
                                <div>Valid Present: <span class="font-medium text-slate-900">{{ $row->valid_present_count }}</span></div>
                                <div>Deficit: <span class="font-medium text-slate-900">{{ $row->deficit_count }}</span></div>
                                <div>Excess: <span class="font-medium text-slate-900">{{ $row->excess_count }}</span></div>
                            </div>

                            <div class="mt-2 flex flex-wrap gap-1">
                                @if((float) $row->deficit_count > 0)
                                    <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-700">
                                        Deficit
                                    </span>
                                @endif
                                @if((float) $row->excess_count > 0)
                                    <span class="inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-2 py-0.5 text-[11px] font-medium text-sky-700">
                                        Excess
                                    </span>
                                @endif
                            </div>
                        </td>

                        <td class="px-5 py-4 text-slate-600">
                            <div class="grid gap-1 text-xs">
                                <div>OT Days: <span class="font-medium text-slate-900">{{ $row->overtime_day_count }}</span></div>
                                <div>OT Minutes: <span class="font-medium text-slate-900">{{ $row->overtime_min_total }}</span></div>
                                <div>Late: <span class="font-medium text-slate-900">{{ (int) $row->late_count }}x / {{ (int) $row->late_min_total }} min</span></div>
                                <div>Early Out: <span class="font-medium text-slate-900">{{ (int) $row->early_out_count }}x / {{ (int) $row->early_out_min_total }} min</span></div>
                                <div>Leave Used: <span class="font-medium text-slate-900">{{ $row->leave_quota_used_count }}</span></div>
                                <div>Deduction Days: <span class="font-medium text-slate-900">{{ $row->deduction_day_count }}</span></div>
                            </div>

                            <div class="mt-2 flex flex-wrap gap-1">
                                @if((float) $row->deduction_day_count > 0)
                                    <span class="inline-flex items-center rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-[11px] font-medium text-rose-700">
                                        Deduction
                                    </span>
                                @endif
                                @if((int) $row->overtime_min_total > 0)
                                    <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700">
                                        Overtime
                                    </span>
                                @endif
                                @if((int) $row->late_count > 0)
                                    <span class="inline-flex items-center rounded-full border border-orange-200 bg-orange-50 px-2 py-0.5 text-[11px] font-medium text-orange-700">
                                        Late
                                    </span>
                                @endif

                                @if((int) $row->early_out_count > 0)
                                    <span class="inline-flex items-center rounded-full border border-yellow-200 bg-yellow-50 px-2 py-0.5 text-[11px] font-medium text-yellow-700">
                                        Early Out
                                    </span>
                                @endif

                                @if((int) $row->incomplete_count > 0)
                                    <span class="inline-flex items-center rounded-full border border-slate-300 bg-slate-50 px-2 py-0.5 text-[11px] font-medium text-slate-700">
                                        Incomplete
                                    </span>
                                @endif
                            </div>
                        </td>

                        <td class="px-5 py-4">
                            <div class="flex justify-end gap-2">
                                <x-ui.button
                                    variant="ghost"
                                    onclick="window.location='{{ route('summary.period.show', $row->attendance_period_summary_id) }}'"
                                >
                                    Laporan
                                </x-ui.button>

                                <x-ui.row-actions :actions="[
                                    [
                                        'label' => 'Open Payroll Report',
                                        'url' => route('summary.period.show', $row->attendance_period_summary_id),
                                        'visible' => true,
                                    ],
                                    [
                                        'label' => 'Open Monthly Summary',
                                        'url' => route('summary.monthly.index', [
                                            'q' => $row->employee->emp_code ?? null,
                                            'period_year' => $row->payrollPeriod->payroll_year ?? null,
                                            'period_month' => $row->payrollPeriod->payroll_month ?? null,
                                        ]),
                                        'visible' => true,
                                    ],
                                    [
                                        'label' => 'Open Daily Attendance',
                                        'url' => auth()->user()->hasPermission('attendance_daily.view')
                                            ? route('attendance.daily.index', [
                                                'q' => $row->employee->emp_code ?? null,
                                                'date_from' => $row->payrollPeriod->period_start_date ?? null,
                                                'date_to' => $row->payrollPeriod->period_end_date ?? null,
                                            ])
                                            : null,
                                        'visible' => auth()->user()->hasPermission('attendance_daily.view'),
                                    ],
                                    [
                                        'label' => 'Open Review Cases',
                                        'url' => auth()->user()->hasPermission('attendance_review.view')
                                            ? route('review.attendance-cases.index', [
                                                'q' => $row->employee->emp_code ?? null,
                                                'date_from' => $row->payrollPeriod->period_start_date ?? null,
                                                'date_to' => $row->payrollPeriod->period_end_date ?? null,
                                            ])
                                            : null,
                                        'visible' => auth()->user()->hasPermission('attendance_review.view'),
                                    ],
                                ]" />
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-14">
                            <x-ui.empty-state
                                title="No period summary found"
                                description="Belum ada period summary sesuai filter."
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