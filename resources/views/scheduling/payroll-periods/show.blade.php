@extends('layouts.app')

@section('content')
<div class="mb-6 flex items-start justify-between">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight">{{ $payrollPeriod->period_code }}</h1>
        <p class="mt-1 text-sm text-slate-500">Detail payroll period</p>
    </div>

    @if(auth()->user()->hasPermission('payroll_period.manage'))
        <a href="{{ route('scheduling.payroll-periods.edit', $payrollPeriod->payroll_period_id) }}"
           class="rounded-2xl border border-slate-300 px-4 py-2.5 text-sm font-medium hover:bg-slate-50">
            Edit
        </a>
    @endif

    <div class="flex flex-wrap items-center justify-end gap-2">
        @if(auth()->user()->hasPermission('payroll_period.manage'))
            @if($payrollPeriod->isOpen())
                <form method="POST" action="{{ route('scheduling.payroll-periods.close', $payrollPeriod->payroll_period_id) }}">
                    @csrf
                    <button type="submit"
                        onclick="return confirm('Close payroll period ini?')"
                        class="rounded-2xl border border-slate-300 px-4 py-2.5 text-sm font-medium hover:bg-slate-50">
                        Close Period
                    </button>
                </form>

                <form method="POST" action="{{ route('scheduling.payroll-periods.create-next', $payrollPeriod->payroll_period_id) }}">
                    @csrf
                    <button type="submit"
                        onclick="return confirm('Buat next period? Period saat ini akan otomatis CLOSED.')"
                        class="rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-slate-800">
                        Create Next Period
                    </button>
                </form>
            @elseif($payrollPeriod->isClosed())
                <form method="POST" action="{{ route('scheduling.payroll-periods.reopen', $payrollPeriod->payroll_period_id) }}">
                    @csrf
                    <button type="submit"
                        onclick="return confirm('Reopen payroll period ini? Hasil summary bisa berubah jika diproses ulang.')"
                        class="rounded-2xl border border-slate-300 px-4 py-2.5 text-sm font-medium hover:bg-slate-50">
                        Reopen Period
                    </button>
                </form>

                <form method="POST" action="{{ route('scheduling.payroll-periods.lock', $payrollPeriod->payroll_period_id) }}">
                    @csrf
                    <button type="submit"
                        onclick="return confirm('Lock payroll period ini sebagai final?')"
                        class="rounded-2xl bg-amber-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-amber-700">
                        Lock Period
                    </button>
                </form>

                <form method="POST" action="{{ route('scheduling.payroll-periods.create-next', $payrollPeriod->payroll_period_id) }}">
                    @csrf
                    <button type="submit"
                        onclick="return confirm('Buat next period? Period saat ini akan tetap CLOSED.')"
                        class="rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-slate-800">
                        Create Next Period
                    </button>
                </form>
            @elseif($payrollPeriod->isLocked())
                <form method="POST" action="{{ route('scheduling.payroll-periods.unlock', $payrollPeriod->payroll_period_id) }}">
                    @csrf
                    <button type="submit"
                        onclick="return confirm('Unlock payroll period ini ke CLOSED?')"
                        class="rounded-2xl border border-amber-300 bg-amber-50 px-4 py-2.5 text-sm font-medium text-amber-700 hover:bg-amber-100">
                        Unlock Period
                    </button>
                </form>
            @endif
        @endif
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-2">
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold">Period Information</h2>

        <dl class="mt-4 space-y-4 text-sm">
            <div>
                <dt class="text-slate-500">Period Code</dt>
                <dd class="mt-1 font-medium">{{ $payrollPeriod->period_code }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Start Date</dt>
                <dd class="mt-1 font-medium">{{ optional($payrollPeriod->period_start_date)->format('Y-m-d') }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">End Date</dt>
                <dd class="mt-1 font-medium">{{ optional($payrollPeriod->period_end_date)->format('Y-m-d') }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Payroll Year</dt>
                <dd class="mt-1 font-medium">{{ $payrollPeriod->payroll_year }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Payroll Month</dt>
                <dd class="mt-1 font-medium">{{ $payrollPeriod->payroll_month }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Status</dt>
                <dd class="mt-1 font-medium">{{ $payrollPeriod->payroll_period_status_code }}</dd>
            </div>
        </dl>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold">Notes</h2>
        <div class="mt-4 text-sm text-slate-700">
            {{ $payrollPeriod->notes ?: '-' }}
        </div>
    </div>
</div>
@endsection