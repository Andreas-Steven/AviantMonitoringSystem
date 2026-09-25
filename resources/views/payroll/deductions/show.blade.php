@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Payroll Deduction Detail"
        :subtitle="sprintf(
            '%s · %s',
            $row->employee?->emp_code ?? '-',
            $row->payrollPeriod?->period_code ?? '-'
        )"
        :breadcrumbs="[
            ['label' => 'Payroll'],
            ['label' => 'Deductions', 'url' => route('payroll.deductions.index')],
            ['label' => 'Detail'],
        ]"
    >
        <x-slot:actions>
            <x-ui.button variant="ghost" onclick="window.location='{{ route('payroll.deductions.index') }}'">
                Back
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat-card label="Amount" :value="number_format((float) $row->amount, 2)" hint="Nilai potongan final" />
        <x-ui.stat-card label="Qty" :value="number_format((float) $row->qty, 2)" hint="Kuantitas potongan" />
        <x-ui.stat-card label="Rate" :value="number_format((float) $row->rate_amount, 2)" hint="Rate per unit" />
        <x-ui.stat-card label="Debt Flag" :value="$row->debt_forming_flag ? 'YES' : 'NO'" hint="Apakah flagged debt-forming" />
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
        <div class="space-y-6 xl:col-span-8">
            <x-ui.page-section
                title="Deduction Snapshot"
                subtitle="Ringkasan employee, period, type, source, dan debt link."
            >
                <div class="grid gap-4 md:grid-cols-2">
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
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Payroll Period</div>
                        <div class="mt-1 text-base font-medium text-slate-900">
                            {{ $row->payrollPeriod?->period_code ?? '-' }}
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Deduction Type</div>
                        <div class="mt-1 text-base font-medium text-slate-900">
                            {{ $row->deductionType?->deduction_name ?? '-' }}
                        </div>
                        <div class="mt-1 text-sm text-slate-500">
                            {{ $row->deductionType?->deduction_code ?? '-' }} · {{ $row->deductionType?->category_code ?? '-' }}
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Debt Link</div>
                        @if($row->employeeDebt)
                            <div class="mt-1 text-base font-medium text-slate-900">
                                {{ $row->employeeDebt->debt_code }} · {{ $row->employeeDebt->debt_name }}
                            </div>
                            <div class="mt-1 text-sm text-slate-500">
                                Outstanding: {{ number_format((float) $row->employeeDebt->outstanding_amount, 2) }}
                            </div>
                            <div class="mt-3">
                                <a
                                    href="{{ route('payroll.debts.show', $row->employeeDebt->employee_debt_id) }}"
                                    class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
                                >
                                    Open Debt
                                </a>
                            </div>
                        @else
                            <div class="mt-1 text-base font-medium text-slate-500">Belum linked ke debt</div>
                        @endif
                    </div>
                </div>
            </x-ui.page-section>

            <x-ui.page-section
                title="Transaction Detail"
                subtitle="Field utama payroll deduction."
            >
                <div class="overflow-hidden rounded-2xl border border-slate-200">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <tr>
                                    <td class="px-5 py-4 font-medium text-slate-500">Description</td>
                                    <td class="px-5 py-4 text-slate-900">{{ $row->description ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="px-5 py-4 font-medium text-slate-500">Source</td>
                                    <td class="px-5 py-4 text-slate-900">
                                        {{ $row->sourceType?->source_type_name ?? $row->source_type_code ?? '-' }}
                                        @if($row->source_ref_id)
                                            · {{ $row->source_ref_id }}
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-5 py-4 font-medium text-slate-500">Qty / Rate / Amount</td>
                                    <td class="px-5 py-4 text-slate-900">
                                        {{ number_format((float) $row->qty, 2) }}
                                        /
                                        {{ number_format((float) $row->rate_amount, 2) }}
                                        /
                                        {{ number_format((float) $row->amount, 2) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-5 py-4 font-medium text-slate-500">Debt Forming</td>
                                    <td class="px-5 py-4 text-slate-900">{{ $row->debt_forming_flag ? 'YES' : 'NO' }}</td>
                                </tr>
                                <tr>
                                    <td class="px-5 py-4 font-medium text-slate-500">Notes</td>
                                    <td class="px-5 py-4 text-slate-900 whitespace-pre-line">{{ $row->notes ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="px-5 py-4 font-medium text-slate-500">Created At</td>
                                    <td class="px-5 py-4 text-slate-900">{{ optional($row->created_at)->format('Y-m-d H:i:s') ?: '-' }}</td>
                                </tr>
                                @if(!$row->is_cancelled)
                                <form method="POST" action="{{ route('payroll.deductions.cancel', $row->payroll_deduction_id) }}">
                                    @csrf
                                    <button
                                        onclick="return confirm('Batalkan deduction ini?')"
                                        class="text-xs text-rose-600 hover:underline"
                                    >
                                        Cancel
                                    </button>
                                </form>
                                @else
                                <span class="text-xs text-slate-400">Cancelled</span>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </x-ui.page-section>
        </div>

        <div class="space-y-6 xl:col-span-4">
            @if($row->employeeDebt)
                <x-ui.page-section
                    title="Debt Ledger Impact"
                    subtitle="Ledger transaction terbaru pada debt yang terkait dengan deduction ini."
                >
                    @php
                        $latestLinkedTx = $row->employeeDebt->transactions
                            ->where('payroll_deduction_id', $row->payroll_deduction_id)
                            ->sortByDesc('employee_debt_transaction_id')
                            ->first();
                    @endphp

                    @if($latestLinkedTx)
                        <div class="space-y-3">
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Transaction Type</div>
                                <div class="mt-1 text-base font-medium text-slate-900">
                                    {{ $latestLinkedTx->transactionType?->transaction_type_name ?? $latestLinkedTx->transaction_type_code }}
                                </div>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Transaction Date</div>
                                <div class="mt-1 text-base font-medium text-slate-900">
                                    {{ optional($latestLinkedTx->transaction_date)->format('Y-m-d') ?: '-' }}
                                </div>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Amount</div>
                                <div class="mt-1 text-base font-semibold text-emerald-700">
                                    -{{ number_format((float) $latestLinkedTx->amount, 2) }}
                                </div>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Outstanding After Recalc</div>
                                <div class="mt-1 text-base font-semibold text-slate-900">
                                    {{ number_format((float) $row->employeeDebt->outstanding_amount, 2) }}
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-sm text-slate-500">
                            Belum ada debt transaction yang tertaut langsung ke payroll deduction ini.
                        </div>
                    @endif
                </x-ui.page-section>
            @endif
        </div>
    </div>
</div>
@endsection