@extends('layouts.app')

@section('content')
@php
    $periods = $periods ?? $payrollPeriods ?? collect();
    $statuses = $statuses ?? collect();

    $statusNameMap = collect($statuses)->mapWithKeys(function ($status) {
        $code = $status->payroll_period_status_code ?? null;
        $name = $status->payroll_period_status_name ?? $code;

        return $code ? [$code => $name] : [];
    });
@endphp

<div class="space-y-6">
    <x-ui.page-header
        title="Payroll Periods"
        subtitle="Kelola periode payroll yang menjadi dasar proses attendance, summary, dan cut-off operasional."
        :breadcrumbs="[
            ['label' => 'Scheduling'],
            ['label' => 'Payroll Periods'],
        ]"
    >
        <x-slot:actions>
            @if(auth()->user()->hasPermission('payroll_period.manage'))
                <x-ui.button
                    variant="primary"
                    onclick="window.location='{{ route('scheduling.payroll-periods.create') }}'"
                >
                    Add Payroll Period
                </x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    @if($activePeriod)
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wide text-emerald-700">
                        Active Payroll Period
                    </div>
                    <div class="mt-1 text-xl font-bold text-emerald-950">
                        {{ $activePeriod->period_code }}
                    </div>
                    <div class="mt-1 text-sm text-emerald-800">
                        {{ $activePeriod->period_start_date->format('d M Y') }}
                        -
                        {{ $activePeriod->period_end_date->format('d M Y') }}
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('scheduling.payroll-periods.show', $activePeriod->payroll_period_id) }}"
                    class="rounded-xl bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-800">
                        Open Period
                    </a>

                    @if(auth()->user()->hasPermission('payroll_period.manage'))
                        <form method="POST" action="{{ route('scheduling.payroll-periods.create-next', $activePeriod->payroll_period_id) }}">
                            @csrf
                            <button type="submit"
                                    onclick="return confirm('Buat next period? Period aktif saat ini akan otomatis CLOSED.')"
                                    class="rounded-xl border border-emerald-300 bg-white px-4 py-2 text-sm font-medium text-emerald-800 hover:bg-emerald-100">
                                Create Next Period
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    @endif

    @if($openCount > 1)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800">
            Warning: Ada {{ $openCount }} payroll period berstatus OPEN. Idealnya hanya 1 period aktif.
        </div>
    @endif

    <div class="grid gap-4 md:grid-cols-4">
        <x-ui.stat-card label="Open" :value="$openCount" hint="Period aktif / berjalan" />
        <x-ui.stat-card label="Closed" :value="$closedCount" hint="Selesai dihitung, belum final lock" />
        <x-ui.stat-card label="Locked" :value="$lockedCount" hint="Final dan tidak boleh berubah" />
        <x-ui.stat-card
            label="Latest Period"
            :value="$latestPeriod?->period_code ?? '-'"
            hint="Payroll period terbaru"
        />
    </div>

    <x-ui.page-section
        title="Payroll Period Directory"
        subtitle="Cari dan review periode payroll yang tersedia."
    >
        <form method="GET" action="{{ route('scheduling.payroll-periods.index') }}" class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_220px_auto]">
            <x-ui.field label="Search">
                <input
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Cari period code"
                    class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                >
            </x-ui.field>

            <x-ui.field label="Status">
                <select
                    name="payroll_period_status_code"
                    class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                >
                    <option value="">All</option>
                    @foreach($statuses as $status)
                        <option
                            value="{{ $status->payroll_period_status_code }}"
                            @selected((string) request('payroll_period_status_code') === (string) $status->payroll_period_status_code)
                        >
                            {{ $status->payroll_period_status_name }}
                        </option>
                    @endforeach
                </select>
            </x-ui.field>

            <div class="flex items-end gap-3">
                <x-ui.button type="submit">
                    Search
                </x-ui.button>

                <x-ui.button
                    type="button"
                    variant="ghost"
                    onclick="window.location='{{ route('scheduling.payroll-periods.index') }}'"
                >
                    Reset
                </x-ui.button>
            </div>
        </form>
    </x-ui.page-section>

    <x-ui.table-shell>
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50/80">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Period
                    </th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Date Range
                    </th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Year / Month
                    </th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Status
                    </th>
                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Action
                    </th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse($periods as $period)
                    @php
                        $periodId = $period->payroll_period_id;
                        $statusCode = $period->payroll_period_status_code;
                        $statusLabel = $statusNameMap[$statusCode] ?? $statusCode;

                        $statusToneClass = match ($statusCode) {
                            'OPEN' => 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200',
                            'CLOSED' => 'bg-slate-100 text-slate-700 ring-1 ring-slate-200',
                            'LOCKED' => 'bg-amber-100 text-amber-700 ring-1 ring-amber-200',
                            default => 'bg-sky-100 text-sky-700 ring-1 ring-sky-200',
                        };
                    @endphp

                    <tr class="transition hover:bg-slate-50">
                        <td class="px-5 py-4">
                            <div class="font-medium text-slate-900">
                                {{ $period->period_code }}
                            </div>
                            <div class="mt-1 text-xs text-slate-500">
                                Payroll period
                            </div>
                        </td>

                        <td class="px-5 py-4 text-slate-600">
                            {{ \Illuminate\Support\Carbon::parse($period->period_start_date)->format('Y-m-d') }}
                            —
                            {{ \Illuminate\Support\Carbon::parse($period->period_end_date)->format('Y-m-d') }}
                        </td>

                        <td class="px-5 py-4 text-slate-600">
                            {{ $period->payroll_year }} / {{ str_pad((string) $period->payroll_month, 2, '0', STR_PAD_LEFT) }}
                        </td>

                        <td class="px-5 py-4">
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium {{ $statusToneClass }}">
                                {{ $statusLabel }}
                            </span>
                        </td>

                        <td class="px-5 py-4">
                            <div class="flex justify-end gap-2">
                                <x-ui.button
                                    type="button"
                                    size="sm"
                                    variant="ghost"
                                    onclick="window.location='{{ route('scheduling.payroll-periods.show', $periodId) }}'"
                                >
                                    View
                                </x-ui.button>

                                @if(auth()->user()->hasPermission('payroll_period.manage'))
                                    <x-ui.button
                                        type="button"
                                        size="sm"
                                        onclick="window.location='{{ route('scheduling.payroll-periods.edit', $periodId) }}'"
                                    >
                                        Edit
                                    </x-ui.button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-14">
                            <x-ui.empty-state
                                title="No payroll periods found"
                                description="Belum ada data payroll period untuk filter yang sedang dipakai."
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if(method_exists($periods, 'hasPages') && $periods->hasPages())
            <div class="border-t border-slate-200 bg-white px-5 py-4">
                {{ $periods->withQueryString()->links() }}
            </div>
        @endif
    </x-ui.table-shell>
</div>
@endsection