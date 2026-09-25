@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Monthly Summary"
        subtitle="Ringkasan bulanan attendance untuk monitoring operasional dan shortcut investigasi."
        :breadcrumbs="[
            ['label' => 'Summary'],
            ['label' => 'Monthly Summary'],
        ]"
    />

    <x-ui.page-section
        title="Summary Filters"
        subtitle="Filter berdasarkan periode, branch, employee, dan focus monitoring."
    >
        <form method="GET" action="{{ route('summary.monthly.index') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <x-ui.field label="Employee">
                <input
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Cari emp code / nama / biometric"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                >
            </x-ui.field>

            <x-ui.field label="Year">
                <select name="period_year" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                    <option value="">Semua tahun</option>
                    @foreach($years as $year)
                        <option value="{{ $year }}" @selected((string) request('period_year') === (string) $year)>
                            {{ $year }}
                        </option>
                    @endforeach
                </select>
            </x-ui.field>

            <x-ui.field label="Month">
                <select name="period_month" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                    <option value="">Semua bulan</option>
                    @foreach($months as $month)
                        <option value="{{ $month }}" @selected((string) request('period_month') === (string) $month)>
                            {{ str_pad((string) $month, 2, '0', STR_PAD_LEFT) }}
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

            <x-ui.field label="Focus">
                <select name="focus" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                    <option value="">Semua focus</option>
                    <option value="needs_attention" @selected(request('focus') === 'needs_attention')>Needs Attention</option>
                    <option value="late" @selected(request('focus') === 'late')>Late</option>
                    <option value="incomplete" @selected(request('focus') === 'incomplete')>Incomplete</option>
                    <option value="absent" @selected(request('focus') === 'absent')>Absent</option>
                    <option value="overtime" @selected(request('focus') === 'overtime')>Overtime</option>
                </select>
            </x-ui.field>

            <div class="flex items-end gap-3 xl:col-span-5">
                <x-ui.button type="submit">Search</x-ui.button>
                <x-ui.button type="button" variant="ghost" onclick="window.location='{{ route('summary.monthly.index') }}'">
                    Reset
                </x-ui.button>
            </div>
        </form>
    </x-ui.page-section>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat-card
            title="Rows"
            :value="$rows->total()"
            description="Total summary rows hasil filter"
        />
        <x-ui.stat-card
            title="Present Days"
            :value="$rows->getCollection()->sum(fn($row) => (float) $row->present_days)"
            description="Akumulasi present pada halaman ini"
        />
        <x-ui.stat-card
            title="Absent Days"
            :value="$rows->getCollection()->sum(fn($row) => (float) $row->absent_days)"
            description="Akumulasi absent pada halaman ini"
        />
        <x-ui.stat-card
            title="Overtime Total"
            :value="$rows->getCollection()->sum('overtime_min_total')"
            description="Akumulasi overtime menit"
        />
    </div>

    <x-ui.table-shell>
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50/80">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Employee</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Period</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Branch</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Presence</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Discipline</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Overtime / Incomplete</th>
                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse($rows as $row)
                    @php
                        $needsAttention = (float) $row->absent_days > 0
                            || (int) $row->incomplete_count > 0
                            || (int) $row->late_count > 0;

                        $rowClass = $needsAttention
                            ? 'bg-amber-50/60 hover:bg-amber-50'
                            : 'hover:bg-slate-50';
                    @endphp

                    <tr class="transition {{ $rowClass }}">
                        <td class="px-5 py-4">
                            <div class="font-medium text-slate-900">{{ $row->employee->full_name ?? '-' }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ $row->employee->emp_code ?? '-' }}</div>
                        </td>

                        <td class="px-5 py-4">
                            <div class="font-medium text-slate-900">{{ $row->period_year }}-{{ str_pad((string) $row->period_month, 2, '0', STR_PAD_LEFT) }}</div>
                            <div class="mt-1 text-xs text-slate-500">Calculated: {{ optional($row->calculated_at)->format('Y-m-d H:i:s') ?: '-' }}</div>
                        </td>

                        <td class="px-5 py-4 text-slate-600">
                            {{ $row->branch->branch_name ?? '-' }}
                        </td>

                        <td class="px-5 py-4 text-slate-600">
                            <div class="grid gap-1 text-xs">
                                <div>Present: <span class="font-medium text-slate-900">{{ $row->present_days }}</span></div>
                                <div>Absent: <span class="font-medium text-slate-900">{{ $row->absent_days }}</span></div>
                                <div>Leave: <span class="font-medium text-slate-900">{{ $row->leave_days }}</span></div>
                                <div>Sick: <span class="font-medium text-slate-900">{{ $row->sick_days }}</span></div>
                                <div>Permission: <span class="font-medium text-slate-900">{{ $row->permission_days }}</span></div>
                            </div>
                        </td>

                        <td class="px-5 py-4 text-slate-600">
                            <div class="grid gap-1 text-xs">
                                <div>Late Count: <span class="font-medium text-slate-900">{{ $row->late_count }}</span></div>
                                <div>Late Total: <span class="font-medium text-slate-900">{{ $row->late_min_total }}</span></div>
                                <div>Early Out Count: <span class="font-medium text-slate-900">{{ $row->early_out_count }}</span></div>
                                <div>Early Out Total: <span class="font-medium text-slate-900">{{ $row->early_out_min_total }}</span></div>
                            </div>
                        </td>

                        <td class="px-5 py-4 text-slate-600">
                            <div class="grid gap-1 text-xs">
                                <div>Overtime: <span class="font-medium text-slate-900">{{ $row->overtime_min_total }}</span></div>
                                <div>Incomplete: <span class="font-medium text-slate-900">{{ $row->incomplete_count }}</span></div>
                            </div>

                            <div class="mt-2 flex flex-wrap gap-1">
                                @if((float) $row->absent_days > 0)
                                    <span class="inline-flex items-center rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-[11px] font-medium text-rose-700">
                                        Absent
                                    </span>
                                @endif
                                @if((int) $row->incomplete_count > 0)
                                    <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-700">
                                        Incomplete
                                    </span>
                                @endif
                                @if((int) $row->late_count > 0)
                                    <span class="inline-flex items-center rounded-full border border-orange-200 bg-orange-50 px-2 py-0.5 text-[11px] font-medium text-orange-700">
                                        Late
                                    </span>
                                @endif
                                @if((int) $row->overtime_min_total > 0)
                                    <span class="inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-2 py-0.5 text-[11px] font-medium text-sky-700">
                                        OT
                                    </span>
                                @endif
                            </div>
                        </td>

                        <td class="px-5 py-4">
                            <div class="flex justify-end gap-2">
                                <x-ui.button
                                    variant="ghost"
                                    onclick="window.location='{{ route('summary.monthly.show', $row->summary_id) }}'"
                                >
                                    Detail
                                </x-ui.button>

                                <x-ui.row-actions :actions="[
                                    [
                                        'label' => 'Open Monthly Detail',
                                        'url' => route('summary.monthly.show', $row->summary_id),
                                        'visible' => true,
                                    ],
                                    [
                                        'label' => 'Open Daily Attendance',
                                        'url' => auth()->user()->hasPermission('attendance_daily.view')
                                            ? route('attendance.daily.index', [
                                                'q' => $row->employee->emp_code ?? null,
                                                'date_from' => sprintf('%04d-%02d-01', $row->period_year, $row->period_month),
                                                'date_to' => \Carbon\Carbon::create($row->period_year, $row->period_month, 1)->endOfMonth()->format('Y-m-d'),
                                            ])
                                            : null,
                                        'visible' => auth()->user()->hasPermission('attendance_daily.view'),
                                    ],
                                    [
                                        'label' => 'Open Review Cases',
                                        'url' => auth()->user()->hasPermission('attendance_review.view')
                                            ? route('review.attendance-cases.index', [
                                                'q' => $row->employee->emp_code ?? null,
                                                'date_from' => sprintf('%04d-%02d-01', $row->period_year, $row->period_month),
                                                'date_to' => \Carbon\Carbon::create($row->period_year, $row->period_month, 1)->endOfMonth()->format('Y-m-d'),
                                            ])
                                            : null,
                                        'visible' => auth()->user()->hasPermission('attendance_review.view'),
                                    ],
                                ]" />
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-14">
                            <x-ui.empty-state
                                title="No monthly summary found"
                                description="Belum ada monthly summary sesuai filter."
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