@extends('layouts.app')

@section('content')
@php
    $routeOrNull = function (string $name) {
        return \Illuminate\Support\Facades\Route::has($name) ? route($name) : null;
    };
@endphp

<div class="space-y-5">

    {{-- Header --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">
                    Payroll & Summary
                </h1>
                <p class="mt-1 text-sm text-gray-500">
                    Pantau hasil absensi periode, payroll result, amount, potongan, dan hutang karyawan.
                </p>
            </div>

            @if($routeOrNull('summary.payroll.reports.index'))
                <a href="{{ $routeOrNull('summary.payroll.reports.index') }}"
                   class="inline-flex w-fit items-center rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-800 hover:bg-gray-50">
                    Open Period Report
                </a>
            @endif
        </div>
    </div>

    {{-- Quick Alert --}}
    @if(!($payrollReadiness['has_active_period'] ?? false))
        <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            <div class="font-medium">No active payroll period</div>
            <div class="mt-1 text-red-600">
                Belum ada payroll period aktif. Setup period dulu sebelum membaca summary/payroll.
            </div>
        </div>
    @elseif(($payrollReadiness['attendance_anomaly_count'] ?? 0) > 0)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-700">
            <div class="font-medium">Payroll needs attention</div>
            <div class="mt-1 text-amber-600">
                Ada {{ $payrollReadiness['attendance_anomaly_count'] }} attendance anomaly pada periode ini.
            </div>
        </div>
    @elseif(!($payrollReadiness['is_ready'] ?? false))
        <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-700">
            <div class="font-medium">Payroll data not complete yet</div>
            <div class="mt-1 text-blue-600">
                Summary/result/amount belum lengkap untuk periode aktif.
            </div>
        </div>
    @else
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">
            <div class="font-medium">Payroll looks ready</div>
            <div class="mt-1 text-emerald-600">
                Summary, result, dan amount sudah tersedia untuk periode aktif.
            </div>
        </div>
    @endif

    {{-- Flow --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-sm font-semibold text-gray-900">
                    Payroll Processing Flow
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Summary → result → amount → deduction.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2 text-sm">
                <span class="rounded-lg bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">1 Summary</span>
                <span class="text-gray-300">→</span>
                <span class="rounded-lg bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700">2 Result</span>
                <span class="text-gray-300">→</span>
                <span class="rounded-lg bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700">3 Amount</span>
                <span class="text-gray-300">→</span>
                <span class="rounded-lg bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700">4 Deduction</span>
            </div>
        </div>
    </div>

    {{-- Period Overview --}}
    <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
        <div class="mb-3 flex items-center justify-between gap-3">
            <div>
                <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                    Period Overview
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Snapshot ringkas periode payroll aktif.
                </p>
            </div>

            @if($routeOrNull('summary.payroll.dashboard'))
                <a href="{{ $routeOrNull('summary.payroll.dashboard') }}"
                   class="hidden rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 sm:inline-flex">
                    Open Dashboard
                </a>
            @endif
        </div>

        <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
            <a href="{{ route('summary.payroll.reports.index', ['payroll_period_id' => $activePeriod?->payroll_period_id]) }}"
            class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 hover:bg-gray-100">
                <div class="text-xl font-semibold text-gray-900">
                    {{ $summaryOverview['employees'] ?? 0 }}
                </div>
                <div class="text-xs text-gray-500">
                    Employees
                </div>
            </a>

            <a href="{{ route('summary.payroll.results.index', ['payroll_period_id' => $activePeriod?->payroll_period_id]) }}"
            class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 hover:bg-blue-100">
                <div class="text-xl font-semibold text-blue-700">
                    {{ $summaryOverview['results'] ?? 0 }}
                </div>
                <div class="text-xs text-blue-700">
                    Results
                </div>
            </a>

            <a href="{{ route('summary.payroll.amounts.index', ['payroll_period_id' => $activePeriod?->payroll_period_id]) }}"
            class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 hover:bg-emerald-100">
                <div class="text-xl font-semibold text-emerald-700">
                    {{ $summaryOverview['amounts'] ?? 0 }}
                </div>
                <div class="text-xs text-emerald-700">
                    Amounts
                </div>
            </a>

            <a href="{{ route('payroll.deductions.index', ['payroll_period_id' => $activePeriod?->payroll_period_id]) }}"
            class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 hover:bg-amber-100">
                <div class="text-xl font-semibold text-amber-700">
                    {{ $summaryOverview['deductions'] ?? 0 }}
                </div>
                <div class="text-xs text-amber-700">
                    Deductions
                </div>
            </a>
        </div>
    </section>

    {{-- Empty State / Guidance --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-4 text-sm text-gray-600 shadow-sm">
        Jika angka masih kosong, jalankan summary builder dan payroll attendance calculator terlebih dahulu.
    </div>

    {{-- Primary Area --}}
    <section class="grid grid-cols-1 gap-4 lg:grid-cols-3">

        {{-- Report --}}
        <div class="rounded-2xl border-2 border-blue-200 bg-white p-5 shadow-sm lg:col-span-2">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="space-y-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="text-base font-semibold text-gray-900">
                            Payroll Period Report
                        </h3>

                        <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">
                            Main report
                        </span>
                    </div>

                    <p class="text-sm text-gray-500">
                        Laporan utama per karyawan per periode, menggabungkan summary, obligation, result, dan amount.
                    </p>

                    <p class="text-xs font-medium text-blue-700">
                        Gunakan halaman ini untuk review final sebelum payroll diproses.
                    </p>
                </div>

                @if($routeOrNull('summary.payroll.reports.index'))
                    <a href="{{ $routeOrNull('summary.payroll.reports.index') }}"
                       class="inline-flex w-fit items-center rounded-xl bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">
                        Open
                    </a>
                @endif
            </div>
        </div>

        {{-- Dashboard --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="space-y-1">
                    <h3 class="text-base font-semibold text-gray-900">
                        Payroll Dashboard
                    </h3>
                    <p class="text-sm text-gray-500">
                        Ringkasan payroll attendance dan trigger kalkulasi.
                    </p>
                </div>

                @if($routeOrNull('summary.payroll.dashboard'))
                    <a href="{{ $routeOrNull('summary.payroll.dashboard') }}"
                       class="inline-flex items-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Open
                    </a>
                @endif
            </div>
        </div>
    </section>

    {{-- Results --}}
    <section class="space-y-3">
        <div>
            <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                Attendance Payroll Result
            </h2>
            <p class="text-sm text-gray-500">
                Detail hasil payable overtime, deduction day, dan nominal attendance payroll.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div class="space-y-1">
                        <h3 class="text-base font-semibold text-gray-900">
                            Payroll Results
                        </h3>
                        <p class="text-sm text-gray-500">
                            Overtime payable, deduction day, dan obligation result.
                        </p>
                    </div>

                    @if($routeOrNull('summary.payroll.results.index'))
                        <a href="{{ $routeOrNull('summary.payroll.results.index') }}"
                           class="inline-flex items-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                            View
                        </a>
                    @endif
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div class="space-y-1">
                        <h3 class="text-base font-semibold text-gray-900">
                            Payroll Amounts
                        </h3>
                        <p class="text-sm text-gray-500">
                            Nominal overtime, deduction, dan net attendance amount.
                        </p>
                    </div>

                    @if($routeOrNull('summary.payroll.amounts.index'))
                        <a href="{{ $routeOrNull('summary.payroll.amounts.index') }}"
                           class="inline-flex items-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                            View
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- Deductions & Debts --}}
    <section class="space-y-3">
        <div>
            <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                Deductions & Debts
            </h2>
            <p class="text-sm text-gray-500">
                Kelola potongan payroll dan kas/hutang karyawan.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="text-base font-semibold text-gray-900">
                    Payroll Deductions
                </h3>
                <p class="mt-1 text-sm text-gray-500">
                    Potongan payroll per periode.
                </p>

                @if($routeOrNull('payroll.deductions.index'))
                    <div class="mt-4">
                        <a href="{{ $routeOrNull('payroll.deductions.index') }}"
                           class="inline-flex items-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Open
                        </a>
                    </div>
                @endif
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="text-base font-semibold text-gray-900">
                    Deduction Types
                </h3>
                <p class="mt-1 text-sm text-gray-500">
                    Master jenis potongan.
                </p>

                @if($routeOrNull('payroll.deduction-types.index'))
                    <div class="mt-4">
                        <a href="{{ $routeOrNull('payroll.deduction-types.index') }}"
                           class="inline-flex items-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Open
                        </a>
                    </div>
                @endif
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="text-base font-semibold text-gray-900">
                    Employee Debts
                </h3>
                <p class="mt-1 text-sm text-gray-500">
                    Saldo hutang dan transaksi cicilan karyawan.
                </p>

                @if($routeOrNull('payroll.debts.index'))
                    <div class="mt-4">
                        <a href="{{ $routeOrNull('payroll.debts.index') }}"
                           class="inline-flex items-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Open
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- Summary Tools --}}
    <section class="space-y-3">
        <div>
            <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                Summary Tools
            </h2>
            <p class="text-sm text-gray-500">
                Detail summary dan tools maintenance.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-4">
            @if($routeOrNull('summary.period.index'))
                <a href="{{ $routeOrNull('summary.period.index') }}"
                   class="rounded-2xl border border-gray-200 bg-white p-4 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                    Period Summary
                </a>
            @endif

            @if($routeOrNull('summary.monthly.index'))
                <a href="{{ $routeOrNull('summary.monthly.index') }}"
                   class="rounded-2xl border border-gray-200 bg-white p-4 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                    Monthly Summary
                </a>
            @endif

            @if($routeOrNull('summary.obligations.index'))
                <a href="{{ $routeOrNull('summary.obligations.index') }}"
                   class="rounded-2xl border border-gray-200 bg-white p-4 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                    Period Obligations
                </a>
            @endif

            @if($routeOrNull('summary.rebuild.index'))
                <a href="{{ $routeOrNull('summary.rebuild.index') }}"
                   class="rounded-2xl border border-gray-200 bg-white p-4 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                    Summary Rebuild
                </a>
            @endif
        </div>
    </section>

</div>
@endsection