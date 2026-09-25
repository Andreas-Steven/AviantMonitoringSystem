@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Period Obligations"
        subtitle="Monitoring kewajiban periodik per employee berdasarkan rule work pattern."
        :breadcrumbs="[
            ['label' => 'Summary'],
            ['label' => 'Period Obligations'],
        ]"
    />

    <x-ui.page-section
        title="Obligation Filters"
        subtitle="Filter berdasarkan payroll period, obligation type, fulfillment, dan employee."
    >
        <form method="GET" action="{{ route('summary.obligations.index') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.field label="Employee">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari employee"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
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

            <x-ui.field label="Obligation Type">
                <select name="obligation_type_code" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                    <option value="">Semua type</option>
                    @foreach($obligationTypes as $type)
                        <option value="{{ $type }}" @selected(request('obligation_type_code') === $type)>
                            {{ $type }}
                        </option>
                    @endforeach
                </select>
            </x-ui.field>

            <div class="flex flex-col justify-end gap-2">
                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="fulfilled_only" value="1" @checked(request()->boolean('fulfilled_only')) class="rounded border-slate-300">
                    Fulfilled only
                </label>
                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="unfulfilled_only" value="1" @checked(request()->boolean('unfulfilled_only')) class="rounded border-slate-300">
                    Unfulfilled only
                </label>
            </div>

            <div class="flex items-end gap-3 xl:col-span-4">
                <x-ui.button type="submit">Search</x-ui.button>
                <x-ui.button type="button" variant="ghost" onclick="window.location='{{ route('summary.obligations.index') }}'">Reset</x-ui.button>
            </div>
        </form>
    </x-ui.page-section>

    <x-ui.table-shell>
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50/80">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Employee</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Period</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Rule</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Required / Actual</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Fulfillment</th>
                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse($rows as $row)
                    <tr class="transition hover:bg-slate-50">
                        <td class="px-5 py-4">
                            <div class="font-medium text-slate-900">{{ $row->employee->full_name ?? '-' }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ $row->employee->emp_code ?? '-' }}</div>
                        </td>
                        <td class="px-5 py-4">
                            <div class="font-medium text-slate-900">{{ $row->payrollPeriod->period_code ?? '-' }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ $row->obligation_type_code }}</div>
                        </td>
                        <td class="px-5 py-4 text-slate-600">
                            {{ $row->workPatternRule->rule_code ?? '-' }}
                        </td>
                        <td class="px-5 py-4 text-slate-600">
                            <div>Required: <span class="font-medium text-slate-900">{{ $row->required_count }}</span></div>
                            <div class="mt-1">Actual: <span class="font-medium text-slate-900">{{ $row->actual_count }}</span></div>
                            <div class="mt-1">Excess: <span class="font-medium text-slate-900">{{ $row->excess_count }}</span></div>
                        </td>
                        <td class="px-5 py-4">
                            @if($row->fulfilled_flag)
                                <x-ui.status-badge label="FULFILLED" tone="success" />
                            @else
                                <x-ui.status-badge label="UNFULFILLED" tone="warning" />
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex justify-end gap-2">
                                <x-ui.button variant="ghost" onclick="window.location='{{ route('summary.obligations.show', $row->employee_period_obligation_id) }}'">
                                    Detail
                                </x-ui.button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-14">
                            <x-ui.empty-state title="No obligations found" description="Belum ada data period obligation sesuai filter." />
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