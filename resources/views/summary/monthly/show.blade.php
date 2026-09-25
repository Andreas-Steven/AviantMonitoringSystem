@extends('layouts.app')

@section('title', 'Monthly Summary Detail')

@section('content')
    <div class="space-y-6">
        <x-ui.page-header
            title="Monthly Summary Detail"
            :subtitle="sprintf(
                '%s · %s · %02d/%d',
                $row->employee?->emp_code ?? '-',
                $row->employee?->full_name ?? '-',
                $row->period_month,
                $row->period_year
            )"
        />

        <div class="flex flex-wrap items-center gap-2">
            <a
                href="{{ route('summary.monthly.index') }}"
                class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
            >
                Back to List
            </a>

            @if(auth()->user()->hasPermission('attendance_daily.view'))
                <a
                    href="{{ route('attendance.daily.index', [
                        'q' => $row->employee?->emp_code ?? null,
                        'date_from' => sprintf('%04d-%02d-01', $row->period_year, $row->period_month),
                        'date_to' => \Carbon\Carbon::create($row->period_year, $row->period_month, 1)->endOfMonth()->format('Y-m-d'),
                    ]) }}"
                    class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
                >
                    Open Daily Attendance
                </a>
            @endif

            @if(auth()->user()->hasPermission('attendance_review.view'))
                <a
                    href="{{ route('review.attendance-cases.index', [
                        'q' => $row->employee?->emp_code ?? null,
                        'date_from' => sprintf('%04d-%02d-01', $row->period_year, $row->period_month),
                        'date_to' => \Carbon\Carbon::create($row->period_year, $row->period_month, 1)->endOfMonth()->format('Y-m-d'),
                    ]) }}"
                    class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
                >
                    Open Review Cases
                </a>
            @endif
        </div>

        @if(session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
            <div class="space-y-6 xl:col-span-8">
                <x-ui.section-card title="Monthly Summary Snapshot" :description="'Ringkasan bulanan utama untuk employee dan branch ini.'">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Employee</div>
                            <div class="mt-1 text-base font-semibold text-slate-900">
                                {{ $row->employee?->emp_code ?? '-' }} · {{ $row->employee?->full_name ?? '-' }}
                            </div>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Branch</div>
                            <div class="mt-1 text-base font-medium text-slate-900">
                                {{ $row->branch?->branch_name ?? '-' }}
                            </div>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Period</div>
                            <div class="mt-1 text-base font-medium text-slate-900">
                                {{ sprintf('%02d/%d', $row->period_month, $row->period_year) }}
                            </div>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Calculated At</div>
                            <div class="mt-1 text-base font-medium text-slate-900">
                                {{ optional($row->calculated_at)->format('d M Y H:i') ?? '-' }}
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-3">
                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Present</div>
                            <div class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format((float) $row->present_days, 2) }}</div>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Absent</div>
                            <div class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format((float) $row->absent_days, 2) }}</div>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Leave</div>
                            <div class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format((float) $row->leave_days, 2) }}</div>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Late Count</div>
                            <div class="mt-2 text-2xl font-semibold text-slate-900">{{ (int) $row->late_count }}</div>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Late Min</div>
                            <div class="mt-2 text-2xl font-semibold text-slate-900">{{ (int) $row->late_min_total }}</div>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Early Out Count</div>
                            <div class="mt-2 text-2xl font-semibold text-slate-900">{{ (int) $row->early_out_count }}</div>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Early Out Min</div>
                            <div class="mt-2 text-2xl font-semibold text-slate-900">{{ (int) $row->early_out_min_total }}</div>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">OT Min</div>
                            <div class="mt-2 text-2xl font-semibold text-slate-900">{{ (int) $row->overtime_min_total }}</div>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Incomplete</div>
                            <div class="mt-2 text-2xl font-semibold text-slate-900">{{ (int) $row->incomplete_count }}</div>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-white p-4 lg:col-span-3">
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Sick / Permission</div>
                            <div class="mt-2 text-xl font-semibold text-slate-900">
                                {{ number_format((float) $row->sick_days, 2) }} / {{ number_format((float) $row->permission_days, 2) }}
                            </div>
                        </div>
                    </div>
                </x-ui.section-card>

                <x-ui.section-card title="Daily Contributors" :description="'Data attendance_daily yang menjadi sumber ringkasan bulanan ini.'">
                    @if($dailyRows->isEmpty())
                        <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                            Tidak ada attendance_daily contributor pada periode bulan ini.
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
                <x-ui.section-card title="Quick Contributor Stats" :description="'Bacaan cepat untuk source daily pembentuk summary ini.'">
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
                            <div class="font-semibold text-slate-900">Incomplete / Reviewable</div>
                            <div class="mt-1 text-lg font-medium text-slate-800">{{ $dailyStats['incomplete_count'] }}</div>
                        </div>
                    </div>
                </x-ui.section-card>

                <x-ui.section-card title="Quick Reading Guide" :description="'Panduan singkat membaca summary bulanan ini.'">
                    <div class="space-y-3 text-sm text-slate-600">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                            Fokus utama monthly summary ada pada absent, incomplete, dan late count sebagai indikator operasional yang perlu tindak lanjut.
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                            Gunakan tombol <span class="font-medium text-slate-800">Open Daily Attendance</span> untuk menelusuri contributor harian, lalu lanjutkan ke review case bila ditemukan anomaly atau review reason.
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                            Overtime tinggi tanpa banyak anomaly biasanya aman, tetapi overtime tinggi bersamaan dengan incomplete atau absent perlu investigasi lebih dalam di level daily.
                        </div>
                    </div>
                </x-ui.section-card>
            </div>
        </div>
    </div>
@endsection