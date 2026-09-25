@extends('layouts.app')

@section('title', 'Period Obligation Detail')

@section('content')
    <div class="space-y-6">
        <x-ui.page-header
            title="Period Obligation Detail"
            :subtitle="sprintf(
                '%s · %s',
                $row->employee?->emp_code ?? '-',
                $row->employee?->full_name ?? '-'
            )"
        >
            <div class="flex flex-wrap items-center gap-2">
                <a
                    href="{{ route('summary.obligations.index') }}"
                    class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
                >
                    Back to List
                </a>
            </div>
        </x-ui.page-header>

        @if(session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
            <div class="space-y-6 xl:col-span-8">
                <x-ui.section-card title="Obligation Snapshot" :description="'Ringkasan kewajiban periodik dan hasil aktualnya.'">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Employee</div>
                            <div class="mt-1 text-base font-semibold text-slate-900">
                                {{ $row->employee?->emp_code ?? '-' }} · {{ $row->employee?->full_name ?? '-' }}
                            </div>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Obligation Type</div>
                            <div class="mt-1 text-base font-medium text-slate-900">
                                {{ $row->obligation_type_code ?? '-' }}
                            </div>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Payroll Period</div>
                            <div class="mt-1 text-base font-medium text-slate-900">
                                {{ $row->payrollPeriod?->period_code ?? '-' }}
                            </div>
                            @if($row->payrollPeriod)
                                <div class="mt-1 text-sm text-slate-500">
                                    {{ \Illuminate\Support\Carbon::parse($row->payrollPeriod->period_start_date)->format('d M Y') }}
                                    -
                                    {{ \Illuminate\Support\Carbon::parse($row->payrollPeriod->period_end_date)->format('d M Y') }}
                                </div>
                            @endif
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Calculated At</div>
                            <div class="mt-1 text-base font-medium text-slate-900">
                                {{ optional($row->calculated_at)->format('d M Y H:i') ?? '-' }}
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Required</div>
                            <div class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format((float) $row->required_count, 2) }}</div>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Actual</div>
                            <div class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format((float) $row->actual_count, 2) }}</div>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Excess</div>
                            <div class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format((float) $row->excess_count, 2) }}</div>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Fulfilled</div>
                            <div class="mt-2 text-2xl font-semibold {{ $row->fulfilled_flag ? 'text-emerald-700' : 'text-rose-700' }}">
                                {{ $row->fulfilled_flag ? 'Yes' : 'No' }}
                            </div>
                        </div>
                    </div>

                    @if($row->workPatternRule)
                        <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-4">
                            <div class="text-sm font-semibold text-slate-900">Work Pattern Rule Context</div>

                            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Rule Code</div>
                                    <div class="mt-1 text-sm text-slate-900">{{ $row->workPatternRule->rule_code ?? '-' }}</div>
                                </div>

                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Rule Name</div>
                                    <div class="mt-1 text-sm text-slate-900">{{ $row->workPatternRule->rule_name ?? '-' }}</div>
                                </div>

                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Rule Type</div>
                                    <div class="mt-1 text-sm text-slate-900">{{ $row->workPatternRule->rule_type_code ?? '-' }}</div>
                                </div>

                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Day of Week</div>
                                    <div class="mt-1 text-sm text-slate-900">{{ $row->workPatternRule->day_of_week_code ?? '-' }}</div>
                                </div>

                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Shift ID</div>
                                    <div class="mt-1 text-sm text-slate-900">{{ $row->workPatternRule->shift_id ?? '-' }}</div>
                                </div>

                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Target Count / Period</div>
                                    <div class="mt-1 text-sm text-slate-900">{{ $row->workPatternRule->target_count_per_period ?? '-' }}</div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if(!empty($row->notes))
                        <div class="mt-4 rounded-xl border border-slate-200 bg-white p-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Notes</div>
                            <div class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700">
                                {{ $row->notes }}
                            </div>
                        </div>
                    @endif
                </x-ui.section-card>

                <x-ui.section-card title="Daily Contributors for Obligation" :description="'Candidate attendance_daily rows yang relevan dengan obligation ini dalam payroll period terkait.'">
                    @if($dailyRows->isEmpty())
                        <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                            Tidak ada attendance_daily contributor yang cocok dengan filter obligation ini.
                        </div>
                    @else
                        <div class="overflow-hidden rounded-2xl border border-slate-200">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-slate-200 text-sm">
                                    <thead class="bg-slate-50 text-slate-600">
                                        <tr>
                                            <th class="px-4 py-3 text-left font-semibold">Date</th>
                                            <th class="px-4 py-3 text-left font-semibold">Status</th>
                                            <th class="px-4 py-3 text-left font-semibold">Presence</th>
                                            <th class="px-4 py-3 text-left font-semibold">Review Reason</th>
                                            <th class="px-4 py-3 text-left font-semibold">Work</th>
                                            <th class="px-4 py-3 text-left font-semibold">Late</th>
                                            <th class="px-4 py-3 text-left font-semibold">OT</th>
                                            <th class="px-4 py-3 text-left font-semibold">Flags</th>
                                            <th class="px-4 py-3 text-left font-semibold">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        @foreach($dailyRows as $daily)
                                            <tr class="align-top">
                                                <td class="px-4 py-3 text-slate-700">
                                                    {{ \Illuminate\Support\Carbon::parse($daily->work_date)->format('d M Y') }}
                                                </td>
                                                <td class="px-4 py-3 text-slate-700">
                                                    {{ $daily->attendance_status_code ?? '-' }}
                                                </td>
                                                <td class="px-4 py-3 text-slate-700">
                                                    {{ $daily->presence_type_code ?? '-' }}
                                                </td>
                                                <td class="px-4 py-3 text-slate-700">
                                                    {{ $daily->review_reason_code ?? '-' }}
                                                </td>
                                                <td class="px-4 py-3 text-slate-700">
                                                    {{ (int) $daily->work_min }}
                                                </td>
                                                <td class="px-4 py-3 text-slate-700">
                                                    {{ (int) $daily->late_min }}
                                                </td>
                                                <td class="px-4 py-3 text-slate-700">
                                                    {{ (int) $daily->overtime_min }}
                                                </td>
                                                <td class="px-4 py-3">
                                                    <div class="flex flex-wrap gap-1">
                                                        @if($daily->anomaly_flag)
                                                            <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700">Anomaly</span>
                                                        @endif
                                                        @if($daily->exception_flag)
                                                            <span class="inline-flex items-center rounded-full border border-violet-200 bg-violet-50 px-2 py-0.5 text-xs font-medium text-violet-700">Exception</span>
                                                        @endif
                                                        @if($daily->leave_flag)
                                                            <span class="inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-2 py-0.5 text-xs font-medium text-sky-700">Leave</span>
                                                        @endif
                                                        @if(!$daily->anomaly_flag && !$daily->exception_flag && !$daily->leave_flag)
                                                            <span class="text-xs text-slate-400">-</span>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <a
                                                        href="{{ route('attendance.daily.show', $daily->attendance_daily_id) }}"
                                                        class="text-sm font-medium text-sky-700 hover:text-sky-800"
                                                    >
                                                        Open Daily
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </x-ui.section-card>
            </div>

            <div class="space-y-6 xl:col-span-4">
                <x-ui.section-card title="Quick Contributor Stats" :description="'Bacaan cepat untuk daily rows yang relevan ke obligation ini.'">
                    <div class="grid grid-cols-1 gap-4">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="font-semibold text-slate-900">Daily Rows</div>
                            <div class="mt-1 text-lg font-medium text-slate-800">{{ $dailyStats['row_count'] }}</div>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="font-semibold text-slate-900">Present Rows</div>
                            <div class="mt-1 text-lg font-medium text-slate-800">{{ $dailyStats['present_count'] }}</div>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="font-semibold text-slate-900">Anomaly Rows</div>
                            <div class="mt-1 text-lg font-medium text-slate-800">{{ $dailyStats['anomaly_count'] }}</div>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="font-semibold text-slate-900">Exception Rows</div>
                            <div class="mt-1 text-lg font-medium text-slate-800">{{ $dailyStats['exception_count'] }}</div>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="font-semibold text-slate-900">Leave Rows</div>
                            <div class="mt-1 text-lg font-medium text-slate-800">{{ $dailyStats['leave_count'] }}</div>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="font-semibold text-slate-900">Late Rows</div>
                            <div class="mt-1 text-lg font-medium text-slate-800">{{ $dailyStats['late_count'] }}</div>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="font-semibold text-slate-900">OT Rows</div>
                            <div class="mt-1 text-lg font-medium text-slate-800">{{ $dailyStats['overtime_count'] }}</div>
                        </div>
                    </div>
                </x-ui.section-card>
            </div>
        </div>
    </div>
@endsection