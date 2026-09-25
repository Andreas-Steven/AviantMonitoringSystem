@extends('layouts.app')

@section('content')
@php
    $routeOrNull = function (string $name, array $params = []) {
        return \Illuminate\Support\Facades\Route::has($name) ? route($name, $params) : null;
    };

    $pendingLeave = $stats['pending_leave_requests'] ?? 0;
    $pendingOvertime = $stats['pending_overtime_requests'] ?? 0;
    $openReview = $stats['open_review_cases'] ?? 0;
    $todayAnomaliesCount = $stats['today_anomalies'] ?? 0;
    $activePeriod = $stats['active_payroll_period'] ?? null;
@endphp

<div class="space-y-6">
    <x-ui.page-header
        title="Dashboard"
        subtitle="Pusat kontrol operasional absensi, approval, review, dan payroll."
        :breadcrumbs="[
            ['label' => 'Dashboard'],
        ]"
    />

    {{-- Operational Signals --}}
    <div class="grid gap-3 md:grid-cols-3">
        @if($routeOrNull('scheduling.workspace'))
            <a href="{{ $routeOrNull('scheduling.workspace') }}"
               class="rounded-2xl border border-blue-200 bg-blue-50 p-4 transition hover:bg-blue-100">
                <div class="text-sm font-semibold text-blue-900">
                    Scheduling Setup
                </div>
                <div class="mt-1 text-xs text-blue-700">
                    Cek coverage, assignment, policy, calendar, dan payroll period.
                </div>
            </a>
        @endif

        @if($routeOrNull('attendance.workspace'))
            <a href="{{ $routeOrNull('attendance.workspace') }}"
               class="rounded-2xl border border-amber-200 bg-amber-50 p-4 transition hover:bg-amber-100">
                <div class="text-sm font-semibold text-amber-900">
                    Attendance Needs Review
                </div>
                <div class="mt-1 text-xs text-amber-700">
                    {{ $todayAnomaliesCount }} anomaly hari ini perlu dicek.
                </div>
            </a>
        @endif

        @if($routeOrNull('summary.workspace'))
            <a href="{{ $routeOrNull('summary.workspace') }}"
               class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 transition hover:bg-emerald-100">
                <div class="text-sm font-semibold text-emerald-900">
                    Payroll & Summary
                </div>
                <div class="mt-1 text-xs text-emerald-700">
                    Pastikan attendance sudah final sebelum payroll.
                </div>
            </a>
        @endif
    </div>

    {{-- Smart Suggestions --}}
    @if(!empty($smartSuggestions))
        <x-ui.section-card
            title="Smart Suggestions"
            subtitle="Rekomendasi tindakan berdasarkan kondisi sistem saat ini."
        >
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                @foreach($smartSuggestions as $suggestion)
                    @php
                        $type = $suggestion['type'] ?? 'info';

                        $style = match ($type) {
                            'danger' => 'border-red-200 bg-red-50 text-red-800 hover:bg-red-100',
                            'warning' => 'border-amber-200 bg-amber-50 text-amber-800 hover:bg-amber-100',
                            'success' => 'border-emerald-200 bg-emerald-50 text-emerald-800 hover:bg-emerald-100',
                            default => 'border-blue-200 bg-blue-50 text-blue-800 hover:bg-blue-100',
                        };

                        $descStyle = match ($type) {
                            'danger' => 'text-red-700',
                            'warning' => 'text-amber-700',
                            'success' => 'text-emerald-700',
                            default => 'text-blue-700',
                        };
                    @endphp

                    <a href="{{ $suggestion['route'] }}"
                    class="rounded-2xl border p-4 transition {{ $style }}">
                        <div class="text-sm font-semibold">
                            {{ $suggestion['title'] }}
                        </div>

                        <div class="mt-1 text-xs {{ $descStyle }}">
                            {{ $suggestion['description'] }}
                        </div>

                        <div class="mt-3 text-xs font-semibold underline underline-offset-2">
                            {{ $suggestion['action'] }} →
                        </div>
                    </a>
                @endforeach
            </div>
        </x-ui.section-card>
    @else
        <x-ui.section-card
            title="Smart Suggestions"
            subtitle="Rekomendasi tindakan berdasarkan kondisi sistem saat ini."
        >
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">
                <div class="font-medium">
                    Everything looks good
                </div>
                <div class="mt-1 text-emerald-600">
                    Tidak ada rekomendasi tindakan penting saat ini.
                </div>
            </div>
        </x-ui.section-card>
    @endif

    {{-- Work Queue --}}
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <a href="{{ route('requests.leave-requests.index', ['request_status_code' => 'PENDING']) }}"
           class="rounded-2xl border border-amber-200 bg-white p-5 shadow-sm transition hover:bg-amber-50">
            <div class="text-xs font-semibold uppercase tracking-wide text-amber-600">
                Pending Leave
            </div>
            <div class="mt-2 text-3xl font-semibold text-slate-900">
                {{ $pendingLeave }}
            </div>
            <div class="mt-1 text-sm text-slate-500">
                Request leave menunggu approval.
            </div>
        </a>

        <a href="{{ route('requests.overtime-requests.index', ['request_status_code' => 'PENDING']) }}"
           class="rounded-2xl border border-indigo-200 bg-white p-5 shadow-sm transition hover:bg-indigo-50">
            <div class="text-xs font-semibold uppercase tracking-wide text-indigo-600">
                Pending Overtime
            </div>
            <div class="mt-2 text-3xl font-semibold text-slate-900">
                {{ $pendingOvertime }}
            </div>
            <div class="mt-1 text-sm text-slate-500">
                Request overtime menunggu approval.
            </div>
        </a>

        <a href="{{ route('review.attendance-cases.index', ['review_status_code' => 'OPEN']) }}"
           class="rounded-2xl border border-rose-200 bg-white p-5 shadow-sm transition hover:bg-rose-50">
            <div class="text-xs font-semibold uppercase tracking-wide text-rose-600">
                Open Review Cases
            </div>
            <div class="mt-2 text-3xl font-semibold text-slate-900">
                {{ $openReview }}
            </div>
            <div class="mt-1 text-sm text-slate-500">
                Case attendance belum selesai.
            </div>
        </a>

        <a href="{{ route('attendance.daily.index', ['anomaly' => 1, 'work_date' => now()->format('Y-m-d')]) }}"
           class="rounded-2xl border border-red-200 bg-white p-5 shadow-sm transition hover:bg-red-50">
            <div class="text-xs font-semibold uppercase tracking-wide text-red-600">
                Today's Anomalies
            </div>
            <div class="mt-2 text-3xl font-semibold text-slate-900">
                {{ $todayAnomaliesCount }}
            </div>
            <div class="mt-1 text-sm text-slate-500">
                Anomali attendance hari ini.
            </div>
        </a>
    </div>

    {{-- Main Workspace + Period --}}
    <div class="grid gap-6 xl:grid-cols-3">
        <x-ui.section-card
            title="Operational Shortcuts"
            subtitle="Mulai dari area kerja utama sesuai workflow operasional."
        >
            <div class="grid gap-3">
                @if($routeOrNull('scheduling.workspace'))
                    <a href="{{ $routeOrNull('scheduling.workspace') }}"
                       class="rounded-2xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm font-medium text-blue-800 transition hover:bg-blue-100">
                        Scheduling Setup
                        <div class="mt-1 text-xs font-normal text-blue-700">
                            Cek coverage, assignment, policy, calendar, dan payroll period.
                        </div>
                    </a>
                @endif

                @if($routeOrNull('attendance.workspace'))
                    <a href="{{ $routeOrNull('attendance.workspace') }}"
                       class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                        Attendance Operations
                        <div class="mt-1 text-xs font-normal text-slate-500">
                            Import log, process data, dan verifikasi attendance daily.
                        </div>
                    </a>
                @endif

                @if($routeOrNull('summary.workspace'))
                    <a href="{{ $routeOrNull('summary.workspace') }}"
                       class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                        Payroll & Summary
                        <div class="mt-1 text-xs font-normal text-slate-500">
                            Review summary, payroll result, amount, deductions, dan debts.
                        </div>
                    </a>
                @endif
            </div>
        </x-ui.section-card>

        <x-ui.section-card
            title="Payroll Period Status"
            subtitle="Periode aktif untuk konteks absensi dan payroll."
        >
            @if($activePeriod)
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                    <div class="text-xs font-semibold uppercase tracking-wide text-emerald-700">
                        Open Payroll Period
                    </div>
                    <div class="mt-2 text-2xl font-semibold text-emerald-950">
                        {{ $activePeriod->period_code }}
                    </div>
                    <div class="mt-2 text-sm text-emerald-800">
                        {{ $activePeriod->period_start_date?->format('d M Y') }}
                        s/d
                        {{ $activePeriod->period_end_date?->format('d M Y') }}
                    </div>
                </div>

                @if($routeOrNull('summary.workspace'))
                    <a href="{{ $routeOrNull('summary.workspace') }}"
                       class="mt-4 inline-flex rounded-xl border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Open Payroll Workspace
                    </a>
                @endif
            @else
                <x-ui.empty-state
                    title="No open payroll period"
                    description="Belum ada payroll period berstatus OPEN."
                />
            @endif
        </x-ui.section-card>

        <x-ui.section-card
            title="System Snapshot"
            subtitle="Konteks master data aktif."
        >
            <div class="grid gap-3">
                <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="text-sm font-medium text-slate-700">Active Branches</div>
                    <div class="text-xl font-semibold text-slate-900">{{ $stats['active_branches'] }}</div>
                </div>

                <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="text-sm font-medium text-slate-700">Active Employees</div>
                    <div class="text-xl font-semibold text-slate-900">{{ $stats['active_employees'] }}</div>
                </div>

                <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="text-sm font-medium text-slate-700">Active Shifts</div>
                    <div class="text-xl font-semibold text-slate-900">{{ $stats['active_shifts'] }}</div>
                </div>
            </div>
        </x-ui.section-card>
    </div>

    {{-- Pending Lists --}}
    <div class="grid gap-6 xl:grid-cols-2">
        <x-ui.section-card
            title="Pending Leave Requests"
            subtitle="5 request leave teratas yang menunggu approval."
        >
            <div class="space-y-3">
                @forelse($pendingLeaveRequests as $leaveRequest)
                    <a
                        href="{{ route('requests.leave-requests.show', $leaveRequest->leave_request_id) }}"
                        class="block rounded-2xl border border-slate-200 px-4 py-3 transition hover:bg-slate-50"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="text-sm font-medium text-slate-900">
                                    {{ $leaveRequest->employee->full_name ?? '-' }}
                                </div>
                                <div class="mt-1 text-xs text-slate-500">
                                    {{ $leaveRequest->employee->emp_code ?? '-' }} ·
                                    {{ $leaveRequest->leaveType->leave_type_name ?? '-' }}
                                </div>
                                <div class="mt-2 text-xs text-slate-600">
                                    {{ optional($leaveRequest->start_date)->format('Y-m-d') }}
                                    —
                                    {{ optional($leaveRequest->end_date)->format('Y-m-d') }}
                                </div>
                            </div>

                            <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-700 ring-1 ring-amber-200">
                                {{ $leaveRequest->request_status_code }}
                            </span>
                        </div>
                    </a>
                @empty
                    <x-ui.empty-state
                        title="No pending leave requests"
                        description="Tidak ada leave request yang sedang menunggu approval."
                    />
                @endforelse
            </div>
        </x-ui.section-card>

        <x-ui.section-card
            title="Pending Overtime Requests"
            subtitle="5 request overtime teratas yang menunggu approval."
        >
            <div class="space-y-3">
                @forelse($pendingOvertimeRequests as $overtimeRequest)
                    <a
                        href="{{ route('requests.overtime-requests.show', $overtimeRequest->overtime_request_id) }}"
                        class="block rounded-2xl border border-slate-200 px-4 py-3 transition hover:bg-slate-50"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="text-sm font-medium text-slate-900">
                                    {{ $overtimeRequest->employee->full_name ?? '-' }}
                                </div>
                                <div class="mt-1 text-xs text-slate-500">
                                    {{ $overtimeRequest->employee->emp_code ?? '-' }}
                                </div>
                                <div class="mt-2 text-xs text-slate-600">
                                    Work date: {{ optional($overtimeRequest->work_date)->format('Y-m-d') }}
                                </div>
                            </div>

                            <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-700 ring-1 ring-amber-200">
                                {{ $overtimeRequest->request_status_code }}
                            </span>
                        </div>
                    </a>
                @empty
                    <x-ui.empty-state
                        title="No pending overtime requests"
                        description="Tidak ada overtime request yang sedang menunggu approval."
                    />
                @endforelse
            </div>
        </x-ui.section-card>
    </div>

    {{-- Review Lists --}}
    <div class="grid gap-6 xl:grid-cols-2">
        <x-ui.section-card
            title="Open Review Cases"
            subtitle="5 review case terbaru yang masih perlu ditindak."
        >
            <div class="space-y-3">
                @forelse($openReviewCases as $reviewCase)
                    <a
                        href="{{ route('review.attendance-cases.show', $reviewCase->review_case_id) }}"
                        class="block rounded-2xl border border-slate-200 px-4 py-3 transition hover:bg-slate-50"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="text-sm font-medium text-slate-900">
                                    {{ $reviewCase->employee->full_name ?? '-' }}
                                </div>
                                <div class="mt-1 text-xs text-slate-500">
                                    {{ $reviewCase->employee->emp_code ?? '-' }} ·
                                    {{ $reviewCase->case_type_code }}
                                </div>
                                <div class="mt-2 text-xs text-slate-600">
                                    {{ optional($reviewCase->detected_at)->format('Y-m-d H:i') }}
                                </div>
                            </div>

                            <span class="inline-flex items-center rounded-full bg-rose-100 px-2.5 py-1 text-xs font-medium text-rose-700 ring-1 ring-rose-200">
                                {{ $reviewCase->review_status_code }}
                            </span>
                        </div>
                    </a>
                @empty
                    <x-ui.empty-state
                        title="No open review cases"
                        description="Tidak ada review case yang sedang terbuka."
                    />
                @endforelse
            </div>
        </x-ui.section-card>

        <x-ui.section-card
            title="Today's Attendance Anomalies"
            subtitle="5 attendance anomaly hari ini untuk investigasi cepat."
        >
            <div class="space-y-3">
                @forelse($todayAnomalies as $daily)
                    <a
                        href="{{ route('attendance.daily.show', $daily->attendance_daily_id) }}"
                        class="block rounded-2xl border border-slate-200 px-4 py-3 transition hover:bg-slate-50"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="text-sm font-medium text-slate-900">
                                    {{ $daily->employee->full_name ?? '-' }}
                                </div>
                                <div class="mt-1 text-xs text-slate-500">
                                    {{ $daily->employee->emp_code ?? '-' }} ·
                                    {{ $daily->branch->branch_name ?? '-' }}
                                </div>
                                <div class="mt-2 text-xs text-slate-600">
                                    {{ optional($daily->work_date)->format('Y-m-d') }}
                                    · {{ $daily->attendance_status_code }}
                                    @if($daily->review_reason_code)
                                        · {{ $daily->review_reason_code }}
                                    @endif
                                </div>
                            </div>

                            <span class="inline-flex items-center rounded-full bg-rose-100 px-2.5 py-1 text-xs font-medium text-rose-700 ring-1 ring-rose-200">
                                Review
                            </span>
                        </div>
                    </a>
                @empty
                    <x-ui.empty-state
                        title="No anomalies today"
                        description="Tidak ada attendance anomaly untuk hari ini."
                    />
                @endforelse
            </div>
        </x-ui.section-card>
    </div>
</div>
@endsection