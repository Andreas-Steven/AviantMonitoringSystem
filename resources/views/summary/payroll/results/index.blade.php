@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Payroll Results"
        subtitle="Quantity / payable layer hasil kalkulasi payroll attendance."
        :breadcrumbs="[
            ['label' => 'Summary'],
            ['label' => 'Payroll Results'],
        ]"
    />

    <x-ui.page-section title="Result Filters" subtitle="Filter payroll results berdasarkan period, branch, basis, dan employee.">
        <form method="GET" action="{{ route('summary.payroll.results.index') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
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

            <div class="md:col-span-2 xl:col-span-4">
                <label class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 px-4 py-2.5 text-sm text-slate-700">
                    <input
                        type="checkbox"
                        name="missing_amount_only"
                        value="1"
                        @checked(request()->boolean('missing_amount_only'))
                        class="rounded border-slate-300"
                    >
                    Missing amount only
                </label>
            </div>

            <div class="flex items-end gap-3 xl:col-span-4">
                <x-ui.button type="submit">Search</x-ui.button>
                <x-ui.button type="button" variant="ghost" onclick="window.location='{{ route('summary.payroll.results.index') }}'">Reset</x-ui.button>
            </div>
        </form>
    </x-ui.page-section>

    <div class="rounded-3xl border border-slate-200 bg-white px-4 py-4 shadow-sm">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
            <div class="xl:max-w-xs">
                <h3 class="text-sm font-semibold text-slate-900">Compact Result Summary</h3>
                <p class="mt-1 text-sm text-slate-500">
                    Ringkasan cepat quantity layer untuk hasil filter aktif.
                </p>
            </div>

            <div class="grid flex-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-4">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Rows</div>
                    <div class="mt-1 text-lg font-semibold text-slate-900">{{ $summaryStats['total_rows'] }}</div>
                </div>

                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-amber-700">Missing Amount</div>
                    <div class="mt-1 text-lg font-semibold text-amber-900">{{ $summaryStats['missing_amount_rows'] }}</div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">OT Payable</div>
                    <div class="mt-1 text-lg font-semibold text-slate-900">{{ $summaryStats['overtime_min_payable_total'] }}</div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Deduction Day</div>
                    <div class="mt-1 text-lg font-semibold text-slate-900">{{ $summaryStats['deduction_day_payable_total'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <x-ui.table-shell>
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50/80">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Employee</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Period / Branch</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Basis</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">OT Breakdown</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Deduction / Obligation</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Layer Status</th>
                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse($rows as $row)
                    @php
                        $hasAmount = \Illuminate\Support\Facades\DB::table('payroll_attendance_amounts')
                            ->where('emp_id', $row->emp_id)
                            ->where('payroll_period_id', $row->payroll_period_id)
                            ->exists();
                    @endphp

                    <tr class="transition hover:bg-slate-50">
                        <td class="px-5 py-4">
                            <div class="font-medium text-slate-900">{{ $row->employee->full_name ?? '-' }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ $row->employee->emp_code ?? '-' }}</div>
                        </td>

                        <td class="px-5 py-4">
                            <div class="font-medium text-slate-900">{{ $row->payrollPeriod->period_code ?? '-' }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ $row->branch->branch_name ?? '-' }}</div>
                        </td>

                        <td class="px-5 py-4">
                            <div class="flex flex-wrap gap-2">
                                <x-ui.status-badge :label="$row->summary_basis_type_code" tone="info" />
                            </div>

                            @if($row->workPattern)
                                <div class="mt-2 text-xs text-slate-500">
                                    {{ $row->workPattern->work_pattern_name }}
                                </div>
                            @endif
                        </td>

                        <td class="px-5 py-4">
                            <div class="flex flex-wrap gap-2">
                                <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-700">
                                    Total {{ $row->overtime_min_payable }}
                                </span>

                                @if((int) $row->overtime_workday_min_payable > 0)
                                    <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-700">
                                        WD {{ $row->overtime_workday_min_payable }}
                                    </span>
                                @endif

                                @if((int) $row->overtime_holiday_min_payable > 0)
                                    <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-700">
                                        HOL {{ $row->overtime_holiday_min_payable }}
                                    </span>
                                @endif

                                @if((int) $row->overtime_offday_min_payable > 0)
                                    <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-700">
                                        OFF {{ $row->overtime_offday_min_payable }}
                                    </span>
                                @endif
                            </div>
                        </td>

                        <td class="px-5 py-4">
                            <div class="space-y-1 text-xs text-slate-600">
                                <div>
                                    Deduction Day:
                                    <span class="font-medium text-slate-900">{{ $row->deduction_day_payable }}</span>
                                </div>
                                <div>
                                    Unfulfilled:
                                    <span class="font-medium text-slate-900">{{ $row->obligation_unfulfilled_count }}</span>
                                </div>
                                <div>
                                    Excess:
                                    <span class="font-medium text-slate-900">{{ $row->obligation_excess_count }}</span>
                                </div>
                            </div>
                        </td>

                        <td class="px-5 py-4">
                            <div class="flex flex-wrap gap-2">
                                <x-ui.status-badge label="RESULT READY" tone="success" />

                                @if($hasAmount)
                                    <x-ui.status-badge label="AMOUNT READY" tone="success" />
                                @else
                                    <x-ui.status-badge label="MISSING AMOUNT" tone="warning" />
                                @endif
                            </div>

                            <div class="mt-2 text-xs text-slate-500">
                                {{ optional($row->calculated_at)->format('Y-m-d H:i:s') ?: '-' }}
                            </div>
                        </td>

                        <td class="px-5 py-4">
                            <div class="flex justify-end gap-2">
                                <x-ui.button
                                    variant="ghost"
                                    onclick="window.location='{{ route('summary.payroll.results.show', $row->payroll_attendance_result_id) }}'"
                                >
                                    Detail
                                </x-ui.button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-14">
                            <x-ui.empty-state
                                title="No payroll results found"
                                description="Belum ada payroll result sesuai filter."
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