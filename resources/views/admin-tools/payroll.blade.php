@extends('layouts.app')

@section('content')
<div class="space-y-5">

    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <h1 class="text-2xl font-semibold text-gray-900">
            Payroll Tools
        </h1>
        <p class="mt-1 text-sm text-gray-500">
            Tools teknis untuk summary, payroll result, amount, deduction, dan employee debt.
        </p>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <a href="{{ route('summary.payroll.dashboard') }}" class="rounded-2xl border border-blue-200 bg-blue-50 p-5 hover:bg-blue-100">
            <h3 class="text-base font-semibold text-blue-900">Payroll Dashboard</h3>
            <p class="mt-1 text-sm text-blue-700">Ringkasan payroll attendance dan trigger kalkulasi.</p>
        </a>

        <a href="{{ route('summary.payroll.reports.index') }}" class="rounded-2xl border border-blue-200 bg-blue-50 p-5 hover:bg-blue-100">
            <h3 class="text-base font-semibold text-blue-900">Payroll Reports</h3>
            <p class="mt-1 text-sm text-blue-700">Laporan utama payroll period.</p>
        </a>

        <a href="{{ route('summary.payroll.results.index') }}" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm hover:bg-gray-50">
            <h3 class="text-base font-semibold text-gray-900">Payroll Results</h3>
            <p class="mt-1 text-sm text-gray-500">Lihat hasil payable overtime dan deduction day.</p>
        </a>

        <a href="{{ route('summary.payroll.amounts.index') }}" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm hover:bg-gray-50">
            <h3 class="text-base font-semibold text-gray-900">Payroll Amounts</h3>
            <p class="mt-1 text-sm text-gray-500">Lihat nominal overtime, deduction, dan net attendance amount.</p>
        </a>

        <a href="{{ route('summary.period.index') }}" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm hover:bg-gray-50">
            <h3 class="text-base font-semibold text-gray-900">Period Summary</h3>
            <p class="mt-1 text-sm text-gray-500">Detail summary attendance per periode.</p>
        </a>

        <a href="{{ route('summary.monthly.index') }}" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm hover:bg-gray-50">
            <h3 class="text-base font-semibold text-gray-900">Monthly Summary</h3>
            <p class="mt-1 text-sm text-gray-500">Detail summary attendance bulanan.</p>
        </a>

        <a href="{{ route('summary.obligations.index') }}" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm hover:bg-gray-50">
            <h3 class="text-base font-semibold text-gray-900">Period Obligations</h3>
            <p class="mt-1 text-sm text-gray-500">Lihat obligation per employee dan period.</p>
        </a>

        <a href="{{ route('summary.rebuild.index') }}" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm hover:bg-gray-50">
            <h3 class="text-base font-semibold text-gray-900">Summary Rebuild</h3>
            <p class="mt-1 text-sm text-gray-500">Maintenance tool untuk rebuild summary.</p>
        </a>

        <a href="{{ route('payroll.deductions.index') }}" class="rounded-2xl border border-amber-200 bg-amber-50 p-5 hover:bg-amber-100">
            <h3 class="text-base font-semibold text-amber-900">Payroll Deductions</h3>
            <p class="mt-1 text-sm text-amber-700">Kelola potongan payroll per periode.</p>
        </a>

        <a href="{{ route('payroll.deduction-types.index') }}" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm hover:bg-gray-50">
            <h3 class="text-base font-semibold text-gray-900">Deduction Types</h3>
            <p class="mt-1 text-sm text-gray-500">Master jenis potongan.</p>
        </a>

        <a href="{{ route('payroll.debts.index') }}" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm hover:bg-gray-50">
            <h3 class="text-base font-semibold text-gray-900">Employee Debts</h3>
            <p class="mt-1 text-sm text-gray-500">Kelola saldo hutang dan cicilan karyawan.</p>
        </a>
    </div>

</div>
@endsection