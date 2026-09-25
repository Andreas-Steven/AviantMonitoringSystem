@extends('layouts.app')

@section('title', 'Employee Debt Detail')

@section('content')
<div
    class="space-y-6"
    x-data="debtTransactionForm({
        outstanding: @js((float) $row->outstanding_amount),
        old: {
            action_mode: @js(old('action_mode', 'payment')),
            amount: @js(old('amount')),
            payment_source_code: @js(old('payment_source_code', 'MANUAL')),
            payroll_period_id: @js(old('payroll_period_id')),
            payroll_deduction_id: @js(old('payroll_deduction_id')),
        }
    })"
>
    <x-ui.page-header
        title="Employee Debt Detail"
        :subtitle="sprintf(
            '%s · %s',
            $row->debt_code ?? '-',
            $row->employee?->full_name ?? '-'
        )"
        :breadcrumbs="[
            ['label' => 'Payroll'],
            ['label' => 'Employee Debts', 'url' => route('payroll.debts.index')],
            ['label' => 'Detail'],
        ]"
    >
        <x-slot:actions>
            <a
                href="{{ route('payroll.debts.index') }}"
                class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
            >
                Back
            </a>
        </x-slot:actions>
    </x-ui.page-header>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
        <x-ui.stat-card label="Original" :value="number_format((float) $row->original_amount, 2)" hint="Nilai awal hutang" />
        <x-ui.stat-card label="Outstanding" :value="number_format((float) $row->outstanding_amount, 2)" hint="Sisa hutang saat ini" />
        <x-ui.stat-card label="Paid / Reduced" :value="number_format((float) $summaryCards['installment_total'], 2)" hint="Total pembayaran / pengurangan hutang" />
        <x-ui.stat-card label="Increase Total" :value="number_format((float) $summaryCards['increase_total'], 2)" hint="Total pembentukan / penambahan hutang" />
        <x-ui.stat-card label="Linked Deduction Total" :value="number_format((float) $summaryCards['linked_deduction_total'], 2)" hint="Total payroll deductions yang terhubung" />
        <x-ui.stat-card label="Transactions" :value="$summaryCards['transaction_count']" hint="Jumlah ledger transaction" />
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
        <div class="space-y-6 xl:col-span-8">
            <x-ui.section-card title="Debt Summary" subtitle="Header hutang per kasus, employee, sumber, dan outstanding.">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Debt</div>
                        <div class="mt-1 text-base font-semibold text-slate-900">{{ $row->debt_code }}</div>
                        <div class="mt-1 text-sm text-slate-600">{{ $row->debt_name }}</div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Employee</div>
                        <div class="mt-1 text-base font-semibold text-slate-900">
                            {{ $row->employee?->emp_code ?? '-' }} · {{ $row->employee?->full_name ?? '-' }}
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Category</div>
                        <div class="mt-1 text-base font-medium text-slate-900">{{ $row->debt_category_code }}</div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Origin Date</div>
                        <div class="mt-1 text-base font-medium text-slate-900">
                            {{ optional($row->origin_date)->format('Y-m-d') ?: '-' }}
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Source</div>
                        <div class="mt-1 text-base font-medium text-slate-900">
                            {{ $row->sourceType?->source_type_name ?? $row->source_type_code ?? '-' }}
                        </div>
                        @if($row->source_ref_id)
                            <div class="mt-1 text-sm text-slate-500">{{ $row->source_ref_id }}</div>
                        @endif
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Status</div>
                        <div class="mt-2">
                            <x-ui.status-badge
                                :label="$row->status_code"
                                :tone="match($row->status_code) {
                                    'OPEN' => 'warning',
                                    'SETTLED' => 'success',
                                    'CANCELLED' => 'neutral',
                                    default => 'neutral'
                                }"
                            />
                        </div>
                    </div>
                </div>

                <div class="mt-4 rounded-xl border border-slate-200 bg-white p-4">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Notes</div>
                    <div class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700">
                        {{ $row->notes ?: '-' }}
                    </div>
                </div>
            </x-ui.section-card>

            <x-ui.section-card title="Debt Transactions" subtitle="Ledger mutasi hutang: create, installment, payment, settlement, dan adjustment.">
                <x-ui.table-shell>
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50/80">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Date</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Type</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Amount</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Payment Source</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Source</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Notes</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse($transactions as $tx)
                                @php
                                    $directionSign = (int) ($tx->transactionType->direction_sign ?? 0);
                                    $amountClass = $directionSign === 1 ? 'text-rose-700' : 'text-emerald-700';
                                    $displayAmount = ($directionSign === 1 ? '+' : '-') . number_format((float) $tx->amount, 2);
                                    $isReversed = str_contains((string) ($tx->notes ?? ''), '[REVERSED]');
                                    $linkedDeductionCancelled = (bool) optional($tx->payrollDeduction)->is_cancelled;
                                @endphp

                                <tr class="transition hover:bg-slate-50">
                                    <td class="px-5 py-4 text-slate-900">
                                        {{ optional($tx->transaction_date)->format('Y-m-d') ?: '-' }}
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="font-medium text-slate-900">
                                            {{ $tx->transactionType?->transaction_type_name ?? $tx->transaction_type_code }}
                                        </div>
                                        <div class="mt-1 text-xs text-slate-500">{{ $tx->transaction_type_code }}</div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="font-semibold {{ $amountClass }}">
                                            {{ $displayAmount }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-slate-700">
                                        {{ $tx->payment_source_code }}
                                        @if($tx->payrollPeriod)
                                            <div class="mt-1 text-xs text-slate-500">{{ $tx->payrollPeriod->period_code }}</div>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-slate-700">
                                        {{ $tx->sourceType?->source_type_name ?? $tx->source_type_code ?? '-' }}
                                        @if($tx->source_ref_id)
                                            <div class="mt-1 text-xs text-slate-500">{{ $tx->source_ref_id }}</div>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-slate-600">
                                        {{ $tx->notes ?: '-' }}
                                    </td>
                                    <td class="px-5 py-4">
                                        @if($tx->transaction_type_code === 'DEBT_CREATE')
                                            <span class="text-xs text-slate-400">Locked</span>
                                        @elseif($isReversed)
                                            <span class="text-xs text-slate-400">Reversed</span>
                                        @elseif($linkedDeductionCancelled)
                                            <span class="text-xs text-slate-400">Cancelled from Deduction</span>
                                        @else
                                            <form method="POST" action="{{ route('payroll.debts.transactions.reverse', [$row->employee_debt_id, $tx->employee_debt_transaction_id]) }}">
                                                @csrf
                                                <button
                                                    type="submit"
                                                    onclick="return confirm('Reverse transaction ini?')"
                                                    class="text-xs font-medium text-rose-600 hover:underline"
                                                >
                                                    Reverse
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-12">
                                        <x-ui.empty-state
                                            title="No transactions found"
                                            description="Belum ada debt transaction untuk hutang ini."
                                        />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    @if($transactions->hasPages())
                        <div class="border-t border-slate-200 bg-white px-5 py-4">
                            {{ $transactions->withQueryString()->links() }}
                        </div>
                    @endif
                </x-ui.table-shell>
            </x-ui.section-card>

            <x-ui.section-card title="Linked Payroll Deductions" subtitle="Payroll deduction yang sudah ditautkan ke hutang ini.">
                <x-ui.table-shell>
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50/80">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Period</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Type</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Amount</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Description</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse($linkedDeductions as $deduction)
                                <tr class="transition hover:bg-slate-50">
                                    <td class="px-5 py-4 text-slate-900">{{ $deduction->payrollPeriod?->period_code ?? '-' }}</td>
                                    <td class="px-5 py-4 text-slate-900">
                                        {{ $deduction->deductionType?->deduction_name ?? '-' }}
                                    </td>
                                    <td class="px-5 py-4 font-semibold text-slate-900">
                                        {{ number_format((float) $deduction->amount, 2) }}
                                    </td>
                                    <td class="px-5 py-4 text-slate-600">{{ $deduction->description ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-12">
                                        <x-ui.empty-state
                                            title="No linked deductions"
                                            description="Belum ada payroll deduction yang ditautkan ke hutang ini."
                                        />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </x-ui.table-shell>
            </x-ui.section-card>
        </div>

        <div class="space-y-6 xl:col-span-4">
            <x-ui.section-card title="Add Debt Transaction" subtitle="Pilih aksi yang sesuai agar input lebih mudah dan aman.">
                <form method="POST" action="{{ route('payroll.debts.transactions.store', $row->employee_debt_id) }}" class="space-y-5">
                    @csrf

                    <input type="hidden" name="action_mode" :value="actionMode">

                    <div class="grid gap-3">
                        <button
                            type="button"
                            @click="actionMode = 'payment'; paymentSourceCode = 'MANUAL'; payrollDeductionId = ''; payrollPeriodId = ''"
                            :class="actionMode === 'payment' ? 'border-sky-300 bg-sky-50 text-sky-800' : 'border-slate-200 bg-white text-slate-700'"
                            class="rounded-2xl border px-4 py-3 text-left transition"
                        >
                            <div class="font-semibold">Bayar / Cicilan</div>
                            <div class="mt-1 text-sm">Untuk pembayaran hutang sebagian, baik dari payroll maupun non-payroll.</div>
                        </button>

                        <button
                            type="button"
                            @click="actionMode = 'settlement'; paymentSourceCode = 'MANUAL'; amount = outstanding.toString(); payrollDeductionId = ''; payrollPeriodId = ''"
                            :class="actionMode === 'settlement' ? 'border-sky-300 bg-sky-50 text-sky-800' : 'border-slate-200 bg-white text-slate-700'"
                            class="rounded-2xl border px-4 py-3 text-left transition"
                        >
                            <div class="font-semibold">Pelunasan Penuh</div>
                            <div class="mt-1 text-sm">Untuk melunasi seluruh outstanding hutang sekaligus.</div>
                        </button>

                        <button
                            type="button"
                            @click="actionMode = 'increase'; paymentSourceCode = 'MANUAL'; payrollDeductionId = ''; payrollPeriodId = ''"
                            :class="actionMode === 'increase' ? 'border-sky-300 bg-sky-50 text-sky-800' : 'border-slate-200 bg-white text-slate-700'"
                            class="rounded-2xl border px-4 py-3 text-left transition"
                        >
                            <div class="font-semibold">Tambah Hutang</div>
                            <div class="mt-1 text-sm">Untuk menambahkan nominal hutang pada kasus ini.</div>
                        </button>

                        <button
                            type="button"
                            @click="actionMode = 'adjustment'; paymentSourceCode = 'MANUAL'; payrollDeductionId = ''; payrollPeriodId = ''"
                            :class="actionMode === 'adjustment' ? 'border-sky-300 bg-sky-50 text-sky-800' : 'border-slate-200 bg-white text-slate-700'"
                            class="rounded-2xl border px-4 py-3 text-left transition"
                        >
                            <div class="font-semibold">Koreksi</div>
                            <div class="mt-1 text-sm">Untuk koreksi manual, baik menambah maupun mengurangi outstanding.</div>
                        </button>
                    </div>

                    <input type="hidden" name="transaction_type_code" :value="transactionTypeCode">

                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                        <div class="font-semibold text-slate-900" x-text="actionLabel"></div>
                        <div class="mt-1" x-text="actionDescription"></div>
                    </div>

                    <x-ui.field label="Transaction Date" :error="$errors->first('transaction_date')">
                        <input
                            type="date"
                            name="transaction_date"
                            value="{{ old('transaction_date', now()->toDateString()) }}"
                            class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                        >
                    </x-ui.field>

                    <x-ui.field label="Amount" :error="$errors->first('amount')">
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            name="amount"
                            x-model="amount"
                            value="{{ old('amount') }}"
                            class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                        >
                    </x-ui.field>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                        <div>Outstanding sekarang:
                            <span class="font-semibold text-slate-900" x-text="formatMoney(outstanding)"></span>
                        </div>
                        <div class="mt-1">
                            Estimasi setelah submit:
                            <span class="font-semibold" :class="previewTone" x-text="formatMoney(previewOutstanding)"></span>
                        </div>
                    </div>

                    <div x-show="showPaymentSource" x-transition style="display: none;">
                        <x-ui.field label="Payment Source" :error="$errors->first('payment_source_code')">
                            <select name="payment_source_code" x-model="paymentSourceCode" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                                <option value="PAYROLL">PAYROLL</option>
                                <option value="NON_PAYROLL">NON_PAYROLL</option>
                                <option value="MANUAL">MANUAL</option>
                            </select>
                        </x-ui.field>
                    </div>

                    <div x-show="showPayrollFields" x-transition class="space-y-4" style="display: none;">
                        <x-ui.field label="Payroll Period" :error="$errors->first('payroll_period_id')">
                            <select name="payroll_period_id" x-model="payrollPeriodId" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                                <option value="">Pilih payroll period</option>
                                @foreach($payrollPeriods as $period)
                                    <option value="{{ $period->payroll_period_id }}" @selected((string) old('payroll_period_id') === (string) $period->payroll_period_id)>
                                        {{ $period->period_code }}
                                    </option>
                                @endforeach
                            </select>
                        </x-ui.field>

                        <x-ui.field label="Linked Payroll Deduction (optional)" :error="$errors->first('payroll_deduction_id')">
                            <select name="payroll_deduction_id" x-model="payrollDeductionId" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                                <option value="">Tanpa payroll deduction</option>
                                @foreach($linkedDeductions as $deduction)
                                    <option value="{{ $deduction->payroll_deduction_id }}" @selected((string) old('payroll_deduction_id') === (string) $deduction->payroll_deduction_id)>
                                        {{ $deduction->payrollPeriod?->period_code ?? '-' }} · {{ $deduction->deductionType?->deduction_name ?? '-' }} · {{ number_format((float) $deduction->amount, 2) }}
                                    </option>
                                @endforeach
                            </select>
                        </x-ui.field>
                    </div>

                    <div x-show="showAdjustmentDirection" x-transition style="display: none;">
                        <x-ui.field label="Adjustment Direction">
                            <select x-model="adjustmentDirection" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                                <option value="plus">Tambah hutang (+)</option>
                                <option value="minus">Kurangi hutang (-)</option>
                            </select>
                        </x-ui.field>
                    </div>

                    <x-ui.field label="Source Type (optional)" :error="$errors->first('source_type_code')">
                        <select name="source_type_code" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                            <option value="">Tanpa source type</option>
                            @foreach($sourceTypes as $sourceType)
                                <option value="{{ $sourceType->source_type_code }}" @selected(old('source_type_code') === $sourceType->source_type_code)>
                                    {{ $sourceType->source_type_name }} · {{ $sourceType->source_type_code }}
                                </option>
                            @endforeach
                        </select>
                    </x-ui.field>

                    <x-ui.field label="Source Ref ID (optional)" :error="$errors->first('source_ref_id')">
                        <input
                            type="text"
                            name="source_ref_id"
                            value="{{ old('source_ref_id') }}"
                            class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                        >
                    </x-ui.field>

                    <x-ui.field label="Notes" :error="$errors->first('notes')">
                        <textarea
                            name="notes"
                            rows="4"
                            class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                        >{{ old('notes') }}</textarea>
                    </x-ui.field>

                    <div x-show="showOverWarning" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" style="display: none;">
                        Nominal yang dimasukkan lebih besar dari outstanding saat ini.
                    </div>

                    <x-ui.button type="submit">
                        Add Transaction
                    </x-ui.button>
                </form>
            </x-ui.section-card>
        </div>
    </div>
</div>

<script>
    function debtTransactionForm(config) {
        return {
            outstanding: Number(config.outstanding || 0),
            actionMode: config.old.action_mode || 'payment',
            amount: config.old.amount || '',
            paymentSourceCode: config.old.payment_source_code || 'MANUAL',
            payrollPeriodId: config.old.payroll_period_id || '',
            payrollDeductionId: config.old.payroll_deduction_id || '',
            adjustmentDirection: 'plus',

            get numericAmount() {
                const value = Number(this.amount || 0);
                return Number.isNaN(value) ? 0 : value;
            },

            get transactionTypeCode() {
                if (this.actionMode === 'payment') {
                    return this.paymentSourceCode === 'PAYROLL'
                        ? 'PAYROLL_INSTALLMENT'
                        : 'NON_PAYROLL_PAYMENT';
                }

                if (this.actionMode === 'settlement') {
                    return 'FULL_SETTLEMENT';
                }

                if (this.actionMode === 'increase') {
                    return 'ADJUST_PLUS';
                }

                if (this.actionMode === 'adjustment') {
                    return this.adjustmentDirection === 'plus'
                        ? 'ADJUST_PLUS'
                        : 'ADJUST_MINUS';
                }

                return 'NON_PAYROLL_PAYMENT';
            },

            get actionLabel() {
                if (this.actionMode === 'payment') return 'Bayar / Cicilan';
                if (this.actionMode === 'settlement') return 'Pelunasan Penuh';
                if (this.actionMode === 'increase') return 'Tambah Hutang';
                if (this.actionMode === 'adjustment') return 'Koreksi';
                return 'Debt Transaction';
            },

            get actionDescription() {
                if (this.actionMode === 'payment') {
                    return this.paymentSourceCode === 'PAYROLL'
                        ? 'Aksi ini akan mengurangi outstanding hutang lewat payroll installment.'
                        : 'Aksi ini akan mengurangi outstanding hutang lewat pembayaran non-payroll/manual.';
                }

                if (this.actionMode === 'settlement') {
                    return 'Aksi ini ditujukan untuk melunasi seluruh outstanding hutang.';
                }

                if (this.actionMode === 'increase') {
                    return 'Aksi ini akan menambah outstanding hutang pada kasus yang sama.';
                }

                if (this.actionMode === 'adjustment') {
                    return this.adjustmentDirection === 'plus'
                        ? 'Koreksi ini akan menambah outstanding hutang.'
                        : 'Koreksi ini akan mengurangi outstanding hutang.';
                }

                return '';
            },

            get showPaymentSource() {
                return this.actionMode === 'payment';
            },

            get showPayrollFields() {
                return this.actionMode === 'payment' && this.paymentSourceCode === 'PAYROLL';
            },

            get showAdjustmentDirection() {
                return this.actionMode === 'adjustment';
            },

            get previewOutstanding() {
                if (this.actionMode === 'payment' || this.actionMode === 'settlement') {
                    return Math.max(this.outstanding - this.numericAmount, 0);
                }

                if (this.actionMode === 'increase') {
                    return this.outstanding + this.numericAmount;
                }

                if (this.actionMode === 'adjustment') {
                    return this.adjustmentDirection === 'plus'
                        ? this.outstanding + this.numericAmount
                        : Math.max(this.outstanding - this.numericAmount, 0);
                }

                return this.outstanding;
            },

            get previewTone() {
                return this.previewOutstanding > this.outstanding
                    ? 'text-rose-700'
                    : 'text-emerald-700';
            },

            get showOverWarning() {
                const reducingMode =
                    this.actionMode === 'payment' ||
                    this.actionMode === 'settlement' ||
                    (this.actionMode === 'adjustment' && this.adjustmentDirection === 'minus');

                return reducingMode && this.numericAmount > this.outstanding;
            },

            formatMoney(value) {
                const number = Number(value || 0);

                return new Intl.NumberFormat('id-ID', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                }).format(Number.isNaN(number) ? 0 : number);
            },
        }
    }
</script>
@endsection