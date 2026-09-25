@extends('layouts.app')

@section('content')
    @php
        $caseTypeOptions = collect([
            'DOUBLE_TAP',
            'MISSING_IN',
            'MISSING_OUT',
            'UNMATCHED_LOG',
            'SHIFT_MISMATCH',
            'OUTSIDE_BRANCH',
        ]);

        $severityOptions = collect([
            'LOW',
            'MEDIUM',
            'HIGH',
            'CRITICAL',
        ]);

        $reviewStatusOptions = collect([
            'OPEN',
            'IN_REVIEW',
            'RESOLVED',
            'REJECTED',
            'CLOSED',
        ]);

        $resolutionTypeOptions = collect([
            'NO_ACTION',
            'MANUAL_CORRECTION',
            'APPROVED_OVERRIDE',
            'REJECTED_CASE',
            'SYSTEM_ADJUSTMENT',
        ]);
    @endphp

    <div class="space-y-6">
        <x-ui.page-header
            title="Attendance Review Cases"
            subtitle="Worklist investigasi anomali attendance agar kasus terbuka, tingkat severity, dan progres review lebih mudah dipantau."
            :breadcrumbs="[
                ['label' => 'Review'],
                ['label' => 'Attendance Cases'],
            ]"
        />

        @if (session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Cases</div>
                <div class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">
                    {{ number_format($summary['total'] ?? 0) }}
                </div>
            </div>

            <div class="rounded-2xl border border-sky-200 bg-sky-50 px-5 py-4 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-wide text-sky-700">Open</div>
                <div class="mt-3 text-3xl font-semibold tracking-tight text-sky-900">
                    {{ number_format($summary['open'] ?? 0) }}
                </div>
            </div>

            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-wide text-amber-700">In Review</div>
                <div class="mt-3 text-3xl font-semibold tracking-tight text-amber-900">
                    {{ number_format($summary['in_review'] ?? 0) }}
                </div>
            </div>

            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Resolved</div>
                <div class="mt-3 text-3xl font-semibold tracking-tight text-emerald-900">
                    {{ number_format($summary['resolved'] ?? 0) }}
                </div>
            </div>
        </div>

        <x-ui.section-card
            title="Filter Worklist"
            subtitle="Gunakan filter untuk mempersempit kasus berdasarkan periode, severity, status, dan kata kunci employee."
        >
            <form method="GET" action="{{ route('review.attendance-cases.index') }}" class="space-y-4">
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Search</label>
                        <input
                            type="text"
                            name="q"
                            value="{{ request('q') }}"
                            placeholder="Emp code / nama / biometric"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                        >
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Date From</label>
                        <input
                            type="date"
                            name="date_from"
                            value="{{ old('date_from', $selectedDateFrom) }}"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                        >
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Date To</label>
                        <input
                            type="date"
                            name="date_to"
                            value="{{ old('date_to', $selectedDateTo) }}"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                        >
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Review Status</label>
                        <select
                            name="review_status_code"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                        >
                            <option value="">All Status</option>
                            @foreach ($reviewStatusOptions as $status)
                                <option value="{{ $status }}" @selected(request('review_status_code') === $status)>
                                    {{ $status }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Severity</label>
                        <select
                            name="severity_code"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                        >
                            <option value="">All Severity</option>
                            @foreach ($severityOptions as $severity)
                                <option value="{{ $severity }}" @selected(request('severity_code') === $severity)>
                                    {{ $severity }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Case Type</label>
                        <select
                            name="case_type_code"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                        >
                            <option value="">All Case Type</option>
                            @foreach ($caseTypeOptions as $caseType)
                                <option value="{{ $caseType }}" @selected(request('case_type_code') === $caseType)>
                                    {{ $caseType }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Resolution</label>
                        <select
                            name="resolution_type_code"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                        >
                            <option value="">All Resolution</option>
                            @foreach ($resolutionTypeOptions as $resolutionType)
                                <option value="{{ $resolutionType }}" @selected(request('resolution_type_code') === $resolutionType)>
                                    {{ $resolutionType }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-end">
                        <label class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 shadow-sm">
                            <input
                                type="checkbox"
                                name="open_only"
                                value="1"
                                @checked(request()->boolean('open_only'))
                                class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                            >
                            Open only
                        </label>
                    </div>

                    <div class="flex items-end">
                        <label class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 shadow-sm">
                            <input
                                type="checkbox"
                                name="unresolved_only"
                                value="1"
                                @checked(request()->boolean('unresolved_only'))
                                class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                            >
                            Unresolved only
                        </label>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Focus</label>
                        <select
                            name="focus"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                        >
                            <option value="">All Focus</option>
                            <option value="critical_open" @selected(request('focus') === 'critical_open')>Critical + Open</option>
                            <option value="missing_logs" @selected(request('focus') === 'missing_logs')>Missing In / Missing Out</option>
                            <option value="stale_open" @selected(request('focus') === 'stale_open')>Open > 3 days</option>
                        </select>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <x-ui.button type="submit" variant="secondary">
                        Apply Filter
                    </x-ui.button>

                    <a
                        href="{{ route('review.attendance-cases.index') }}"
                        class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 shadow-sm transition hover:border-slate-300 hover:text-slate-900"
                    >
                        Reset
                    </a>
                </div>
            </form>
        </x-ui.section-card>

        <x-ui.section-card
            title="Review Worklist"
            subtitle="Kasus dibuka dengan prioritas status terbuka dan severity tertinggi terlebih dahulu."
        >
            @if ($rows->count())
                <div class="overflow-hidden rounded-2xl border border-slate-200">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 bg-white">
                            <thead class="bg-slate-50/80">
                                <tr>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Employee</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Work Date</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Case Type</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Severity</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Resolution</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Notes</th>
                                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($rows as $row)
                                    @php
                                        $severityTone = match($row->severity_code) {
                                            'LOW' => 'neutral',
                                            'MEDIUM' => 'info',
                                            'HIGH' => 'warning',
                                            'CRITICAL' => 'danger',
                                            default => 'neutral',
                                        };

                                        $statusTone = match($row->review_status_code) {
                                            'OPEN' => 'info',
                                            'IN_REVIEW' => 'warning',
                                            'RESOLVED' => 'success',
                                            'REJECTED' => 'danger',
                                            'CLOSED' => 'neutral',
                                            default => 'neutral',
                                        };
                                    @endphp

                                    <tr class="hover:bg-slate-50/70">
                                        <td class="px-5 py-4">
                                            <div class="font-medium text-slate-900">{{ $row->employee->full_name ?? '-' }}</div>
                                            <div class="mt-1 text-xs text-slate-500">
                                                {{ $row->employee->emp_code ?? '-' }}
                                            </div>
                                        </td>

                                        <td class="px-5 py-4 text-sm text-slate-700">
                                            <div>{{ optional($row->work_date)->format('Y-m-d') ?: '-' }}</div>
                                            <div class="mt-1 text-xs text-slate-500">
                                                Detected: {{ optional($row->detected_at)->format('Y-m-d H:i') ?: '-' }}
                                            </div>
                                        </td>

                                        <td class="px-5 py-4 text-sm text-slate-700">
                                            <div class="font-medium text-slate-900">{{ $row->case_type_code }}</div>
                                        </td>

                                        <td class="px-5 py-4">
                                            <x-ui.status-badge :label="$row->severity_code" :tone="$severityTone" />
                                        </td>

                                        <td class="px-5 py-4">
                                            <x-ui.status-badge :label="$row->review_status_code" :tone="$statusTone" />
                                        </td>

                                        <td class="px-5 py-4 text-sm text-slate-700">
                                            {{ $row->resolution_type_code ?: '-' }}
                                        </td>

                                        <td class="px-5 py-4 text-sm text-slate-600">
                                            <div
                                                class="max-w-md whitespace-normal break-words line-clamp-3"
                                                title="{{ $row->notes }}"
                                            >
                                                {{ $row->notes ?: '-' }}
                                            </div>
                                        </td>

                                        <td class="px-5 py-4">
                                            <div class="flex justify-end gap-2">
                                                <a
                                                    href="{{ route('review.attendance-cases.show', $row->review_case_id) }}"
                                                    class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-600 shadow-sm transition hover:border-slate-300 hover:text-slate-900"
                                                >
                                                    Detail
                                                </a>

                                                @if(auth()->user()->hasPermission('attendance_daily.view'))
                                                    <a
                                                        href="{{ route('attendance.daily.index', [
                                                            'q' => $row->employee->emp_code ?? null,
                                                            'date_from' => optional($row->work_date)->format('Y-m-d'),
                                                            'date_to' => optional($row->work_date)->format('Y-m-d'),
                                                        ]) }}"
                                                        class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-600 shadow-sm transition hover:border-slate-300 hover:text-slate-900"
                                                    >
                                                        Daily
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="pt-2">
                    {{ $rows->links() }}
                </div>
            @else
                <x-ui.empty-state
                    title="Tidak ada review case"
                    description="Belum ada case yang cocok dengan filter saat ini."
                />
            @endif
        </x-ui.section-card>
    </div>
@endsection