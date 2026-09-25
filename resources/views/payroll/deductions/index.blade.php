@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Payroll Deductions"
        subtitle="Potongan payroll per employee per payroll period, termasuk debt-forming dan non-debt deduction."
        :breadcrumbs="[
            ['label' => 'Payroll'],
            ['label' => 'Deductions'],
        ]"
    >
        <x-slot:actions>
            @if(auth()->user()?->hasPermission('payroll_deduction.manage'))
                <x-ui.button onclick="window.location='{{ route('payroll.deductions.create') }}'">
                    Add Deduction
                </x-ui.button>
            @endif
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

    <x-ui.page-section
        title="Filters"
        subtitle="Cari berdasarkan employee, payroll period, deduction type, dan status debt-forming."
    >
        <form method="GET" action="{{ route('payroll.deductions.index') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <x-ui.field label="Employee">
                <input
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Cari emp code / nama / biometric"
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

            <x-ui.field label="Deduction Type">
                <select name="payroll_deduction_type_id" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                    <option value="">Semua type</option>
                    @foreach($deductionTypes as $type)
                        <option value="{{ $type->payroll_deduction_type_id }}" @selected((string) request('payroll_deduction_type_id') === (string) $type->payroll_deduction_type_id)>
                            {{ $type->deduction_name }}
                        </option>
                    @endforeach
                </select>
            </x-ui.field>

            <x-ui.field label="Debt Forming">
                <select name="debt_forming_flag" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                    <option value="">Semua</option>
                    <option value="1" @selected(request('debt_forming_flag') === '1')>Yes</option>
                    <option value="0" @selected(request('debt_forming_flag') === '0')>No</option>
                </select>
            </x-ui.field>

            <x-ui.field label="Linked Debt Only">
                <label class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 px-4 py-3">
                    <input type="checkbox" name="linked_debt_only" value="1" @checked(request('linked_debt_only'))>
                    <span class="text-sm text-slate-700">Hanya yang sudah linked debt</span>
                </label>
            </x-ui.field>

            <div class="flex items-end gap-3 xl:col-span-5">
                <x-ui.button type="submit">Search</x-ui.button>
                <x-ui.button type="button" variant="ghost" onclick="window.location='{{ route('payroll.deductions.index') }}'">Reset</x-ui.button>
            </div>
        </form>
    </x-ui.page-section>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat-card label="Rows" :value="$summaryStats['total_rows']" hint="Total payroll deduction hasil filter" />
        <x-ui.stat-card label="Debt Forming" :value="$summaryStats['debt_forming_rows']" hint="Baris potongan yang flagged membentuk hutang" />
        <x-ui.stat-card label="Linked Debt" :value="$summaryStats['linked_debt_rows']" hint="Baris potongan yang sudah linked ke debt" />
        <x-ui.stat-card label="Amount Total" :value="number_format($summaryStats['amount_total'], 2)" hint="Total nominal potongan hasil filter" />
    </div>

    <x-ui.table-shell>
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50/80">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Employee</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Period</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Type</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Amount</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Debt</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse($rows as $row)
                    @php
                        $hasReversedInstallment = \App\Domains\Payroll\Models\EmployeeDebtTransaction::query()
                            ->where('payroll_deduction_id', $row->payroll_deduction_id)
                            ->where('notes', 'ilike', '%[REVERSED]%')
                            ->exists();
                    @endphp

                    <tr class="transition {{ $row->is_cancelled ? 'bg-slate-50 opacity-60' : 'hover:bg-slate-50' }}">
                        <td class="px-5 py-4">
                            <div class="font-medium text-slate-900">{{ $row->employee->full_name ?? '-' }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ $row->employee->emp_code ?? '-' }}</div>
                        </td>

                        <td class="px-5 py-4">
                            <div class="font-medium text-slate-900">{{ $row->payrollPeriod->period_code ?? '-' }}</div>
                        </td>

                        <td class="px-5 py-4">
                            <div class="font-medium text-slate-900">{{ $row->deductionType->deduction_name ?? '-' }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ $row->deductionType->deduction_code ?? '-' }}</div>
                        </td>

                        <td class="px-5 py-4">
                            <div class="font-semibold text-slate-900">{{ number_format((float) $row->amount, 2) }}</div>
                            <div class="mt-1 text-xs text-slate-500">
                                Qty {{ number_format((float) $row->qty, 2) }}
                                @if((float) $row->rate_amount > 0)
                                    · Rate {{ number_format((float) $row->rate_amount, 2) }}
                                @endif
                            </div>
                        </td>

                        <td class="px-5 py-4">
                            <div class="flex flex-wrap gap-2">
                                <x-ui.status-badge
                                    :label="$row->debt_forming_flag ? 'DEBT-FORMING' : 'NON-DEBT'"
                                    :tone="$row->debt_forming_flag ? 'warning' : 'info'"
                                />

                                @if($row->employeeDebt)
                                    <x-ui.status-badge
                                        :label="'LINKED · '.$row->employeeDebt->debt_code"
                                        tone="success"
                                    />
                                @endif
                            </div>
                        </td>

                        <td class="px-5 py-4">
                            @if($row->is_cancelled)
                                <x-ui.status-badge label="CANCELLED" tone="neutral" />
                            @elseif($hasReversedInstallment)
                                <x-ui.status-badge label="EFFECT REVERSED" tone="warning" />
                            @else
                                <x-ui.status-badge label="ACTIVE" tone="success" />
                            @endif
                        </td>

                        <td class="px-5 py-4 text-right">
                            <div class="flex items-center justify-end gap-3">
                                <a
                                    href="{{ route('payroll.deductions.show', $row->payroll_deduction_id) }}"
                                    class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
                                >
                                    Detail
                                </a>

                                @if(!$row->is_cancelled && !$hasReversedInstallment && auth()->user()?->hasPermission('payroll_deduction.manage'))
                                    <form method="POST" action="{{ route('payroll.deductions.cancel', $row->payroll_deduction_id) }}">
                                        @csrf
                                        <button
                                            type="submit"
                                            onclick="return confirm('Batalkan payroll deduction ini?')"
                                            class="text-sm font-medium text-rose-600 hover:underline"
                                        >
                                            Cancel
                                        </button>
                                    </form>
                                @elseif($hasReversedInstallment)
                                    <span class="text-xs text-slate-400">Already Reversed</span>
                                @elseif($row->is_cancelled)
                                    <span class="text-xs text-slate-400">Cancelled</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-14">
                            <x-ui.empty-state
                                title="No payroll deductions found"
                                description="Belum ada payroll deduction sesuai filter."
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