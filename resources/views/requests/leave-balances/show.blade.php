@extends('layouts.app')

@section('title', 'Leave Balance Detail')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Leave Balance Detail"
        :subtitle="sprintf(
            '%s · %s',
            $balance->employee?->full_name ?? '-',
            $balance->leaveType?->leave_type_name ?? '-'
        )"
        :breadcrumbs="[
            ['label' => 'Requests'],
            ['label' => 'Leave Balances', 'url' => route('requests.leave-balances.index')],
            ['label' => 'Detail'],
        ]"
    >
        <a
            href="{{ route('requests.leave-balances.index') }}"
            class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
        >
            Back to List
        </a>
    </x-ui.page-header>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            {{ $errors->first() }}
        </div>
    @endif

    @php
        $available = (float) $balance->available_balance;
        $isLowBalance = $available <= 1;
        $leaveTypeCode = $balance->leaveType?->leave_type_code ?? '-';

        $leaveTypeTone = match($leaveTypeCode) {
            'ANNUAL' => 'success',
            'SICK' => 'info',
            'PERMIT' => 'warning',
            'UNPAID' => 'danger',
            default => 'neutral',
        };
    @endphp

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Available</div>
            <div class="mt-2 text-lg font-semibold {{ $isLowBalance ? 'text-amber-700' : 'text-emerald-700' }}">
                {{ number_format($available, 2) }}
            </div>
            <div class="mt-1 text-xs text-slate-500">
                {{ $isLowBalance ? 'Low available balance' : 'Available balance still healthy' }}
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Opening</div>
            <div class="mt-2 text-lg font-semibold text-slate-900">
                {{ number_format((float) $balance->opening_balance, 2) }}
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Granted</div>
            <div class="mt-2 text-lg font-semibold text-slate-900">
                {{ number_format((float) $balance->granted_amount, 2) }}
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Used</div>
            <div class="mt-2 text-lg font-semibold text-slate-900">
                {{ number_format((float) $balance->used_amount, 2) }}
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Expired</div>
            <div class="mt-2 text-lg font-semibold {{ (float) $balance->expired_amount > 0 ? 'text-rose-700' : 'text-slate-900' }}">
                {{ number_format((float) $balance->expired_amount, 2) }}
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <x-ui.section-card title="Balance Summary" subtitle="Ringkasan bucket saldo cuti untuk employee dan periode ini.">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Employee</div>
                        <div class="mt-1 text-sm font-semibold text-slate-900">
                            {{ $balance->employee?->emp_code ?? '-' }} · {{ $balance->employee?->full_name ?? '-' }}
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Leave Type</div>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <x-ui.status-badge
                                :label="$balance->leaveType?->leave_type_name ?? '-'"
                                :tone="$leaveTypeTone"
                            />
                            @if($leaveTypeCode !== '-')
                                <x-ui.status-badge :label="$leaveTypeCode" tone="neutral" />
                            @endif
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Period</div>
                        <div class="mt-1 text-sm font-semibold text-slate-900">
                            {{ optional($balance->period_start_date)->format('Y-m-d') }}
                            —
                            {{ optional($balance->period_end_date)->format('Y-m-d') }}
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Status</div>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <x-ui.status-badge
                                :label="$balance->active ? 'ACTIVE' : 'INACTIVE'"
                                :tone="$balance->active ? 'success' : 'neutral'"
                            />
                            @if($isLowBalance)
                                <x-ui.status-badge label="LOW BALANCE" tone="warning" />
                            @endif
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Adjustment</div>
                        <div class="mt-1 text-sm font-semibold text-slate-900">
                            {{ number_format((float) $balance->adjustment_amount, 2) }}
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Closing</div>
                        <div class="mt-1 text-sm font-semibold text-slate-900">
                            {{ number_format((float) $balance->closing_balance, 2) }}
                        </div>
                    </div>
                </div>

                <div class="mt-4 rounded-xl border border-slate-200 bg-white p-4">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Notes</div>
                    <div class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700">
                        {{ $balance->notes ?: '-' }}
                    </div>
                </div>
            </x-ui.section-card>

            <x-ui.section-card title="Ledger / Transactions" subtitle="Riwayat mutasi saldo cuti untuk bucket ini.">
                <x-ui.table-shell>
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50/80">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Date</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Type</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Qty</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Source</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Notes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse($transactions as $tx)
                                @php
                                    $qty = (float) $tx->qty;
                                    $qtyClass = $qty >= 0 ? 'text-emerald-700' : 'text-rose-700';

                                    $typeTone = match($tx->transaction_type_code) {
                                        'OPENING', 'GRANT', 'ADJUST_PLUS', 'RESTORE_CANCELLED' => 'success',
                                        'USE_APPROVED_LEAVE', 'USE_AUTO_FORCE', 'ADJUST_MINUS', 'EXPIRE' => 'warning',
                                        default => 'neutral',
                                    };
                                @endphp

                                <tr class="transition hover:bg-slate-50">
                                    <td class="px-5 py-4">
                                        <div class="font-medium text-slate-900">
                                            {{ optional($tx->transaction_date)->format('Y-m-d') }}
                                        </div>
                                    </td>

                                    <td class="px-5 py-4">
                                        <div class="flex flex-wrap gap-2">
                                            <x-ui.status-badge
                                                :label="$tx->transactionType->transaction_type_name ?? $tx->transaction_type_code"
                                                :tone="$typeTone"
                                            />
                                            <x-ui.status-badge :label="$tx->transaction_type_code" tone="neutral" />
                                        </div>
                                    </td>

                                    <td class="px-5 py-4">
                                        <span class="font-semibold {{ $qtyClass }}">
                                            {{ number_format($qty, 2) }}
                                        </span>
                                    </td>

                                    <td class="px-5 py-4 text-slate-600">
                                        <div>{{ $tx->source_type_code ?: '-' }}</div>
                                        @if($tx->source_ref_id)
                                            <div class="mt-1 text-xs text-slate-500">{{ $tx->source_ref_id }}</div>
                                        @endif
                                    </td>

                                    <td class="px-5 py-4 text-slate-600">
                                        {{ $tx->notes ?: '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-12">
                                        <x-ui.empty-state
                                            title="No transactions found"
                                            description="Belum ada ledger transaction untuk bucket ini."
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
        </div>

        <x-ui.section-card title="Grant Leave Balance" subtitle="Tambahkan saldo cuti resmi ke bucket ini.">
            <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
                Grant akan masuk sebagai ledger <strong>GRANT</strong> dan otomatis memperbarui available balance.
            </div>

            <form method="POST" action="{{ route('requests.leave-balances.grant', $balance->employee_leave_balance_id) }}" class="space-y-4">
                @csrf

                <x-ui.field label="Grant Date" :error="$errors->first('transaction_date')">
                    <input
                        type="date"
                        name="transaction_date"
                        value="{{ old('transaction_date', optional($balance->period_start_date)->format('Y-m-d') ?? now()->toDateString()) }}"
                        class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                    >
                </x-ui.field>

                <x-ui.field label="Qty" :error="$errors->first('qty')">
                    <input
                        type="number"
                        step="0.01"
                        min="0.01"
                        name="qty"
                        value="{{ old('qty') }}"
                        placeholder="Contoh: 12.00"
                        class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                    >
                </x-ui.field>

                <x-ui.field label="Source Ref ID" :error="$errors->first('source_ref_id')">
                    <input
                        type="text"
                        name="source_ref_id"
                        value="{{ old('source_ref_id', 'HR-GRANT-' . optional($balance->period_start_date)->format('Y') . '-' . ($balance->employee?->emp_code ?? 'EMP')) }}"
                        class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                    >
                    <p class="mt-1 text-xs text-slate-500">
                        Wajib unik. Jika source ref sama dikirim ulang, sistem tidak akan membuat grant dobel.
                    </p>
                </x-ui.field>

                <x-ui.field label="Notes" :error="$errors->first('notes')">
                    <textarea
                        name="notes"
                        rows="4"
                        class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                        placeholder="Contoh: Annual leave grant tahun {{ optional($balance->period_start_date)->format('Y') }}"
                    >{{ old('notes') }}</textarea>
                </x-ui.field>

                <div class="flex items-center justify-end gap-3">
                    <x-ui.button type="submit">Save Grant</x-ui.button>
                </div>
            </form>
        </x-ui.section-card>

        <div class="space-y-6">
            <x-ui.section-card title="Manual Adjustment" subtitle="Tambahkan atau kurangi saldo cuti secara manual.">
                <div class="mb-4 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                    <div><span class="font-medium text-slate-900">PLUS</span> = menambah saldo bucket.</div>
                    <div class="mt-1"><span class="font-medium text-slate-900">MINUS</span> = mengurangi saldo bucket.</div>
                </div>

                <form method="POST" action="{{ route('requests.leave-balances.adjust', $balance->employee_leave_balance_id) }}" class="space-y-4">
                    @csrf

                    <x-ui.field label="Adjustment Mode" :error="$errors->first('adjustment_mode')">
                        <select name="adjustment_mode" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                            <option value="PLUS" @selected(old('adjustment_mode') === 'PLUS')>PLUS</option>
                            <option value="MINUS" @selected(old('adjustment_mode') === 'MINUS')>MINUS</option>
                        </select>
                    </x-ui.field>

                    <x-ui.field label="Transaction Date" :error="$errors->first('transaction_date')">
                        <input
                            type="date"
                            name="transaction_date"
                            value="{{ old('transaction_date', now()->toDateString()) }}"
                            class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                        >
                    </x-ui.field>

                    <x-ui.field label="Qty" :error="$errors->first('qty')">
                        <input
                            type="number"
                            step="0.01"
                            min="0.01"
                            name="qty"
                            value="{{ old('qty') }}"
                            placeholder="Contoh: 1.00"
                            class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                        >
                    </x-ui.field>

                    <x-ui.field label="Source Ref ID" :error="$errors->first('source_ref_id')">
                        <input
                            type="text"
                            name="source_ref_id"
                            value="{{ old('source_ref_id') }}"
                            placeholder="Contoh: HR-ADJ-{{ now()->format('Ymd') }}-{{ $balance->employee?->emp_code ?? 'EMP001' }}"
                            class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                        >
                    </x-ui.field>

                    <x-ui.field label="Notes" :error="$errors->first('notes')">
                        <textarea
                            name="notes"
                            rows="5"
                            class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                            placeholder="Catatan adjustment"
                        >{{ old('notes') }}</textarea>
                    </x-ui.field>

                    <div class="flex items-center justify-end gap-3">
                        <x-ui.button
                            type="button"
                            variant="ghost"
                            onclick="window.location='{{ route('requests.leave-balances.index') }}'"
                        >
                            Back
                        </x-ui.button>

                        <x-ui.button type="submit">Save Adjustment</x-ui.button>
                    </div>
                </form>
            </x-ui.section-card>
        </div>
    </div>
</div>
@endsection