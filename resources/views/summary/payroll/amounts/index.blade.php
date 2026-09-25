@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Payroll Amounts"
        subtitle="Money layer hasil kalkulasi payroll attendance."
        :breadcrumbs="[
            ['label' => 'Summary'],
            ['label' => 'Payroll Amounts'],
        ]"
    />

    <x-ui.page-section title="Amount Filters" subtitle="Filter payroll amount berdasarkan period, branch, dan employee.">
        <form method="GET" action="{{ route('summary.payroll.amounts.index') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
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

            <div class="md:col-span-2 xl:col-span-4">
                <label class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 px-4 py-2.5 text-sm text-slate-700">
                    <input
                        type="checkbox"
                        name="negative_net_only"
                        value="1"
                        @checked(request()->boolean('negative_net_only'))
                        class="rounded border-slate-300"
                    >
                    Negative net only
                </label>
            </div>

            <div class="flex items-end gap-3 xl:col-span-4">
                <x-ui.button type="submit">Search</x-ui.button>
                <x-ui.button type="button" variant="ghost" onclick="window.location='{{ route('summary.payroll.amounts.index') }}'">Reset</x-ui.button>
            </div>
        </form>
    </x-ui.page-section>

    <div class="rounded-3xl border border-slate-200 bg-white px-4 py-4 shadow-sm">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
            <div class="xl:max-w-xs">
                <h3 class="text-sm font-semibold text-slate-900">Compact Amount Summary</h3>
                <p class="mt-1 text-sm text-slate-500">
                    Ringkasan cepat money layer untuk hasil filter aktif.
                </p>
            </div>

            <div class="grid flex-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-4">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Rows</div>
                    <div class="mt-1 text-lg font-semibold text-slate-900">{{ $summaryStats['total_rows'] }}</div>
                </div>

                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-emerald-700">OT Amount</div>
                    <div class="mt-1 text-lg font-semibold text-emerald-900">{{ number_format($summaryStats['overtime_amount_total'], 2) }}</div>
                </div>

                <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-rose-700">Deduction Amount</div>
                    <div class="mt-1 text-lg font-semibold text-rose-900">{{ number_format($summaryStats['deduction_amount_total'], 2) }}</div>
                </div>

                <div class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-sky-700">Net Amount</div>
                    <div class="mt-1 text-lg font-semibold text-sky-900">{{ number_format($summaryStats['net_attendance_amount_total'], 2) }}</div>
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
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Stored Amounts</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Net</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Calculated</th>
                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse($rows as $row)
                    <tr class="transition hover:bg-slate-50">
                        <td class="px-5 py-4">
                            <div class="font-medium text-slate-900">{{ $row->full_name }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ $row->emp_code }}</div>
                        </td>

                        <td class="px-5 py-4">
                            <div class="font-medium text-slate-900">{{ $row->period_code }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ $row->branch_name ?: '-' }}</div>
                        </td>

                        <td class="px-5 py-4">
                            <x-ui.status-badge :label="$row->summary_basis_type_code ?: '-'" tone="info" />
                        </td>

                        <td class="px-5 py-4">
                            <div class="space-y-1 text-xs text-slate-600">
                                <div>
                                    OT Amount:
                                    <span class="font-medium text-slate-900">{{ number_format((float) $row->overtime_amount, 2) }}</span>
                                </div>
                                <div>
                                    Deduction:
                                    <span class="font-medium text-slate-900">{{ number_format((float) $row->deduction_amount, 2) }}</span>
                                </div>
                                <div>
                                    OT Min Payable:
                                    <span class="font-medium text-slate-900">{{ $row->overtime_min_payable }}</span>
                                </div>
                                <div>
                                    Deduction Day:
                                    <span class="font-medium text-slate-900">{{ $row->deduction_day_payable }}</span>
                                </div>
                            </div>
                        </td>

                        <td class="px-5 py-4">
                            <div class="flex flex-wrap gap-2">
                                <x-ui.status-badge
                                    :label="number_format((float) $row->net_attendance_amount, 2)"
                                    :tone="$row->net_attendance_amount >= 0 ? 'success' : 'danger'"
                                />
                            </div>
                        </td>

                        <td class="px-5 py-4">
                            <div class="text-xs text-slate-500">
                                {{ optional(\Carbon\Carbon::parse($row->calculated_at))->format('Y-m-d H:i:s') ?: '-' }}
                            </div>
                        </td>

                        <td class="px-5 py-4">
                            <div class="flex justify-end gap-2">
                                <x-ui.button
                                    variant="ghost"
                                    onclick="window.location='{{ route('summary.payroll.amounts.show', $row->payroll_attendance_amount_id) }}'"
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
                                title="No payroll amounts found"
                                description="Belum ada payroll amount sesuai filter."
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