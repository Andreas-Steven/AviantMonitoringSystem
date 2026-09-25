@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Payroll Dashboard"
        subtitle="Audit dashboard v1 untuk quantity layer, money layer, dan coverage payroll attendance."
        :breadcrumbs="[
            ['label' => 'Summary'],
            ['label' => 'Payroll Dashboard'],
        ]"
    />

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    <x-ui.page-section
        title="Payroll Scope"
        subtitle="Pilih payroll period dan branch untuk audit coverage serta menjalankan calculation."
    >
        <form method="GET" action="{{ route('summary.payroll.dashboard') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.field label="Payroll Period">
                <select name="payroll_period_id" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                    @foreach($payrollPeriods as $period)
                        <option value="{{ $period->payroll_period_id }}" @selected((string) request('payroll_period_id', $selectedPeriod?->payroll_period_id) === (string) $period->payroll_period_id)>
                            {{ $period->period_code }} ({{ optional($period->period_start_date)->format('Y-m-d') }} s/d {{ optional($period->period_end_date)->format('Y-m-d') }})
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

            <div class="flex items-end gap-3 xl:col-span-2">
                <x-ui.button type="submit">Apply Scope</x-ui.button>
                <x-ui.button type="button" variant="ghost" onclick="window.location='{{ route('summary.payroll.dashboard') }}'">Reset</x-ui.button>
            </div>
        </form>
    </x-ui.page-section>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
        <x-ui.stat-card label="Summaries" :value="$stats['summary_count']" hint="period summary rows" />
        <x-ui.stat-card label="Results" :value="$stats['result_count']" hint="payroll result rows" />
        <x-ui.stat-card label="Amounts" :value="$stats['amount_count']" hint="payroll amount rows" />
        <x-ui.stat-card label="Missing Result" :value="$stats['missing_result_count']" hint="summary without result" />
        <x-ui.stat-card label="Missing Amount" :value="$stats['missing_amount_count']" hint="result without amount" />
        <x-ui.stat-card label="Orphan Amount" :value="$stats['orphan_amount_count']" hint="amount without result" />
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white px-4 py-4 shadow-sm">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
            <div class="xl:max-w-xs">
                <h3 class="text-sm font-semibold text-slate-900">Compact Payroll Audit Summary</h3>
                <p class="mt-1 text-sm text-slate-500">
                    Ringkasan cepat quantity layer dan money layer untuk scope aktif.
                </p>
            </div>

            <div class="grid flex-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">OT Payable</div>
                    <div class="mt-1 text-lg font-semibold text-slate-900">{{ $stats['overtime_min_payable_total'] }}</div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Deduction Day</div>
                    <div class="mt-1 text-lg font-semibold text-slate-900">{{ $stats['deduction_day_payable_total'] }}</div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">OT Amount</div>
                    <div class="mt-1 text-lg font-semibold text-slate-900">{{ number_format($stats['overtime_amount_total'], 2) }}</div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Deduction Amount</div>
                    <div class="mt-1 text-lg font-semibold text-slate-900">{{ number_format($stats['deduction_amount_total'], 2) }}</div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Net Amount</div>
                    <div class="mt-1 text-lg font-semibold text-slate-900">{{ number_format($stats['net_attendance_amount_total'], 2) }}</div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">OT Workday</div>
                    <div class="mt-1 text-lg font-semibold text-slate-900">{{ $stats['overtime_workday_min_total'] }} min</div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">OT Holiday</div>
                    <div class="mt-1 text-lg font-semibold text-slate-900">{{ $stats['overtime_holiday_min_total'] }} min</div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">OT Offday</div>
                    <div class="mt-1 text-lg font-semibold text-slate-900">{{ $stats['overtime_offday_min_total'] }} min</div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
        <x-ui.section-card title="Branch Coverage" subtitle="Coverage result dan amount per branch untuk scope aktif.">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Branch</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Results</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Amounts</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">OT Min</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Net Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($branchCoverage as $row)
                            <tr>
                                <td class="px-4 py-3 font-medium text-slate-900">{{ $row->branch_name }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $row->result_count }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $row->amount_count }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ (int) $row->overtime_min_payable_total }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ number_format((float) $row->net_attendance_amount_total, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">Belum ada coverage payroll.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.section-card>

        <x-ui.section-card title="Calculation Actions" subtitle="Jalankan quantity layer dan money layer untuk period aktif.">
            <div class="space-y-3">
                <form method="POST" action="{{ route('summary.payroll.calculate-results') }}">
                    @csrf
                    <input type="hidden" name="payroll_period_id" value="{{ $selectedPeriod?->payroll_period_id }}">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="text-sm font-semibold text-slate-900">Calculate Payroll Results</div>
                                <div class="mt-1 text-sm text-slate-500">
                                    Generate payable quantity layer dari summary, obligation, dan overtime bucket.
                                </div>
                            </div>
                            <div class="shrink-0">
                                <x-ui.button type="submit">Run</x-ui.button>
                            </div>
                        </div>
                    </div>
                </form>

                <form method="POST" action="{{ route('summary.payroll.calculate-amounts') }}">
                    @csrf
                    <input type="hidden" name="payroll_period_id" value="{{ $selectedPeriod?->payroll_period_id }}">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="text-sm font-semibold text-slate-900">Calculate Payroll Amounts</div>
                                <div class="mt-1 text-sm text-slate-500">
                                    Generate money layer dari payroll results dan payroll rate policy.
                                </div>
                            </div>
                            <div class="shrink-0">
                                <x-ui.button type="submit">Run</x-ui.button>
                            </div>
                        </div>
                    </div>
                </form>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3">
                        <div class="text-[11px] font-semibold uppercase tracking-wide text-amber-700">Missing Result</div>
                        <div class="mt-1 text-lg font-semibold text-amber-900">{{ $stats['missing_result_count'] }}</div>
                    </div>
                    <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3">
                        <div class="text-[11px] font-semibold uppercase tracking-wide text-rose-700">Missing Amount</div>
                        <div class="mt-1 text-lg font-semibold text-rose-900">{{ $stats['missing_amount_count'] }}</div>
                    </div>
                </div>
            </div>
        </x-ui.section-card>
    </div>

    <x-ui.section-card title="Recent Payroll Rows" subtitle="Preview row result terbaru untuk scope aktif.">
        <div class="space-y-3">
            @forelse($recentRows as $row)
                <div class="rounded-2xl border border-slate-200 px-4 py-3">
                    <div class="flex flex-col gap-3 xl:flex-row xl:items-start xl:justify-between">
                        <div>
                            <div class="text-sm font-medium text-slate-900">{{ $row->full_name }} · {{ $row->emp_code }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ $row->period_code }} · {{ $row->branch_name }} · {{ $row->summary_basis_type_code }}</div>
                            <div class="mt-2 flex flex-wrap gap-2 text-xs">
                                <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-slate-700">
                                    OT {{ $row->overtime_min_payable }} min
                                </span>
                                <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-slate-700">
                                    Deduction {{ $row->deduction_day_payable }}
                                </span>
                                @if($row->payroll_attendance_amount_id)
                                    <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-emerald-700">
                                        Net {{ number_format((float) $row->net_attendance_amount, 2) }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-amber-700">
                                        Amount Missing
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="flex gap-2">
                            <x-ui.button variant="ghost" onclick="window.location='{{ route('summary.payroll.results.show', $row->payroll_attendance_result_id) }}'">
                                Result
                            </x-ui.button>

                            @if($row->payroll_attendance_amount_id)
                                <x-ui.button variant="ghost" onclick="window.location='{{ route('summary.payroll.amounts.show', $row->payroll_attendance_amount_id) }}'">
                                    Amount
                                </x-ui.button>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <x-ui.empty-state title="No payroll rows yet" description="Belum ada payroll result untuk scope ini." />
            @endforelse
        </div>
    </x-ui.section-card>
</div>
@endsection