@extends('layouts.app')

@section('content')
@php
    $selectedStatus = request('overall_status');

    $statusConfigMap = [
        'COMPLETE' => [
            'label' => 'Ready',
            'description' => 'Semua setup aktif sudah lengkap.',
            'class' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        ],
        'CONFLICT_DETECTED' => [
            'label' => 'Conflict',
            'description' => 'Ada lebih dari satu setup aktif.',
            'class' => 'bg-red-50 text-red-700 ring-red-200',
        ],
        'MISSING_ALL' => [
            'label' => 'Missing All',
            'description' => 'Org, shift, dan pattern belum lengkap.',
            'class' => 'bg-rose-50 text-rose-700 ring-rose-200',
        ],
        'MULTIPLE_MISSING' => [
            'label' => 'Multiple Missing',
            'description' => 'Lebih dari satu setup belum lengkap.',
            'class' => 'bg-amber-50 text-amber-700 ring-amber-200',
        ],
        'MISSING_ASSIGNMENT' => [
            'label' => 'Missing Org',
            'description' => 'Belum punya assignment organisasi aktif.',
            'class' => 'bg-amber-50 text-amber-700 ring-amber-200',
        ],
        'MISSING_SHIFT_ASSIGNMENT' => [
            'label' => 'Missing Shift',
            'description' => 'Belum punya shift assignment aktif.',
            'class' => 'bg-amber-50 text-amber-700 ring-amber-200',
        ],
        'MISSING_WORK_PATTERN' => [
            'label' => 'Missing Pattern',
            'description' => 'Belum punya work pattern aktif.',
            'class' => 'bg-amber-50 text-amber-700 ring-amber-200',
        ],
    ];

    $coverageState = function (string $status, int $count = 0) {
        return match ($status) {
            'OK' => [
                'label' => 'Connected',
                'short' => 'OK',
                'dot' => 'bg-emerald-500',
                'box' => 'border-emerald-200 bg-emerald-50',
                'text' => 'text-emerald-800',
                'muted' => 'text-emerald-600',
                'badge' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            ],
            'CONFLICT' => [
                'label' => 'Conflict',
                'short' => 'Conflict ' . $count,
                'dot' => 'bg-red-500',
                'box' => 'border-red-200 bg-red-50',
                'text' => 'text-red-800',
                'muted' => 'text-red-600',
                'badge' => 'bg-red-50 text-red-700 ring-red-200',
            ],
            default => [
                'label' => 'Missing',
                'short' => 'Missing',
                'dot' => 'bg-rose-500',
                'box' => 'border-rose-200 bg-rose-50',
                'text' => 'text-rose-800',
                'muted' => 'text-rose-600',
                'badge' => 'bg-rose-50 text-rose-700 ring-rose-200',
            ],
        };
    };

    $formatDate = function ($date) {
        if (!$date) {
            return 'Now';
        }

        if (is_string($date)) {
            return \Carbon\Carbon::parse($date)->format('d M Y');
        }

        return $date->format('d M Y');
    };

    $formatTime = function ($time) {
        if (!$time) {
            return '—';
        }

        if (is_string($time)) {
            return substr($time, 0, 5);
        }

        return $time->format('H:i');
    };

    $effectivePeriod = function ($item) use ($formatDate) {
        if (!$item) {
            return '—';
        }

        return $formatDate($item->effective_start_date) . ' → ' . $formatDate($item->effective_end_date);
    };

    $statusFilterItems = [
        '' => 'All',
        'MISSING_ALL' => 'Missing All',
        'MULTIPLE_MISSING' => 'Multiple Missing',
        'MISSING_ASSIGNMENT' => 'No Org',
        'MISSING_SHIFT_ASSIGNMENT' => 'No Shift',
        'MISSING_WORK_PATTERN' => 'No Pattern',
        'CONFLICT_DETECTED' => 'Conflict',
        'COMPLETE' => 'Ready',
    ];
@endphp

<div class="space-y-5">
    <x-ui.page-header
        title="Employee Scheduling Coverage"
        subtitle="Audit kelengkapan setup employee: organisasi, shift, dan work pattern dalam satu halaman."
        :breadcrumbs="[
            ['label' => 'Scheduling'],
            ['label' => 'Employee Scheduling Coverage'],
        ]"
    />

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-7">
        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
            <div class="text-xs font-medium text-slate-500">Total</div>
            <div class="mt-1 text-2xl font-semibold text-slate-900">{{ $summary['total'] }}</div>
        </div>

        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 shadow-sm">
            <div class="text-xs font-medium text-emerald-700">Ready</div>
            <div class="mt-1 text-2xl font-semibold text-emerald-800">{{ $summary['complete'] }}</div>
        </div>

        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 shadow-sm">
            <div class="text-xs font-medium text-amber-700">Need Setup</div>
            <div class="mt-1 text-2xl font-semibold text-amber-800">{{ $summary['missing_any'] }}</div>
        </div>

        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 shadow-sm">
            <div class="text-xs font-medium text-red-700">Conflict</div>
            <div class="mt-1 text-2xl font-semibold text-red-800">{{ $summary['conflict_detected'] }}</div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
            <div class="text-xs font-medium text-slate-500">No Org</div>
            <div class="mt-1 text-2xl font-semibold text-slate-900">{{ $summary['missing_assignment'] }}</div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
            <div class="text-xs font-medium text-slate-500">No Shift</div>
            <div class="mt-1 text-2xl font-semibold text-slate-900">{{ $summary['missing_shift_assignment'] }}</div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
            <div class="text-xs font-medium text-slate-500">No Pattern</div>
            <div class="mt-1 text-2xl font-semibold text-slate-900">{{ $summary['missing_work_pattern'] }}</div>
        </div>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
        <form
            method="GET"
            action="{{ route('scheduling.employee-scheduling-coverage.index') }}"
            class="grid gap-3 xl:grid-cols-[170px_minmax(0,1fr)_240px_auto]"
        >
            <x-ui.field label="Reference Date">
                <input
                    type="date"
                    name="reference_date"
                    value="{{ request('reference_date', $referenceDate) }}"
                    class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                >
            </x-ui.field>

            <x-ui.field label="Search Employee">
                <input
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Cari nama, emp code, atau biometric..."
                    class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                >
            </x-ui.field>

            <x-ui.field label="Status">
                <select
                    name="overall_status"
                    class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                >
                    <option value="">All Status</option>
                    <option value="COMPLETE" @selected($selectedStatus === 'COMPLETE')>Ready</option>
                    <option value="MISSING_ALL" @selected($selectedStatus === 'MISSING_ALL')>Missing All</option>
                    <option value="MULTIPLE_MISSING" @selected($selectedStatus === 'MULTIPLE_MISSING')>Multiple Missing</option>
                    <option value="MISSING_ASSIGNMENT" @selected($selectedStatus === 'MISSING_ASSIGNMENT')>Missing Org</option>
                    <option value="MISSING_SHIFT_ASSIGNMENT" @selected($selectedStatus === 'MISSING_SHIFT_ASSIGNMENT')>Missing Shift</option>
                    <option value="MISSING_WORK_PATTERN" @selected($selectedStatus === 'MISSING_WORK_PATTERN')>Missing Pattern</option>
                    <option value="CONFLICT_DETECTED" @selected($selectedStatus === 'CONFLICT_DETECTED')>Conflict</option>
                </select>
            </x-ui.field>

            <div class="flex items-end gap-2">
                <x-ui.button type="submit">
                    Apply
                </x-ui.button>

                <x-ui.button
                    type="button"
                    variant="ghost"
                    onclick="window.location='{{ route('scheduling.employee-scheduling-coverage.index') }}'"
                >
                    Reset
                </x-ui.button>
            </div>
        </form>

        <div class="mt-3 flex flex-wrap gap-2">
            @foreach($statusFilterItems as $statusValue => $statusLabel)
                <a
                    href="{{ route('scheduling.employee-scheduling-coverage.index', array_filter([
                        'reference_date' => request('reference_date', $referenceDate),
                        'q' => request('q'),
                        'overall_status' => $statusValue ?: null,
                    ])) }}"
                    class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-medium transition
                        {{ ($selectedStatus ?: '') === $statusValue
                            ? 'border-slate-900 bg-slate-900 text-white'
                            : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'
                        }}"
                >
                    {{ $statusLabel }}
                </a>
            @endforeach
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif
    <x-ui.table-shell>
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50/80">
                <tr>
                    <th class="w-[22%] px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Employee
                    </th>
                    <th class="w-[25%] px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Coverage Matrix
                    </th>
                    <th class="w-[33%] px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Active Setup
                    </th>
                    <th class="w-[10%] px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Status
                    </th>
                    <th class="w-[10%] px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Action
                    </th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse($rows as $row)
                    @php
                        $overall = $statusConfigMap[$row['overall_status']]
                            ?? [
                                'label' => $row['overall_status'],
                                'description' => 'Status tidak dikenali.',
                                'class' => 'bg-slate-50 text-slate-700 ring-slate-200',
                            ];

                        $assignmentState = $coverageState($row['assignment_status'], $row['assignment_count']);
                        $shiftState = $coverageState($row['shift_status'], $row['shift_count']);
                        $patternState = $coverageState($row['work_pattern_status'], $row['work_pattern_count']);

                        $assignment = $row['assignment'];
                        $shiftAssignment = $row['shift_assignment'];
                        $workPatternAssignment = $row['work_pattern_assignment'];

                        $shift = $shiftAssignment?->shift;
                        $workPattern = $workPatternAssignment?->workPattern;

                        $canUseSetupWizard =
                            !in_array($row['overall_status'], ['COMPLETE', 'CONFLICT_DETECTED'], true)
                            && (
                                ($row['assignment_status'] === 'MISSING' && auth()->user()->hasPermission('assignment.manage'))
                                || ($row['shift_status'] === 'MISSING' && auth()->user()->hasPermission('shift.manage'))
                                || ($row['work_pattern_status'] === 'MISSING' && auth()->user()->hasPermission('workpattern.manage'))
                            );
                    @endphp

                    <tr class="transition hover:bg-slate-50/80">
                        <td class="px-4 py-4 align-top">
                            <div class="flex items-start gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-sm font-semibold text-slate-700">
                                    {{ strtoupper(substr($row['employee']->full_name, 0, 1)) }}
                                </div>

                                <div class="min-w-0">
                                    <div class="truncate font-semibold text-slate-900">
                                        {{ $row['employee']->full_name }}
                                    </div>

                                    <div class="mt-1 text-xs text-slate-500">
                                        {{ $row['employee']->emp_code }}
                                        @if($row['employee']->biometric_code)
                                            · Bio: {{ $row['employee']->biometric_code }}
                                        @endif
                                    </div>

                                    <button
                                        type="button"
                                        onclick="window.location='{{ route('master.employees.show', $row['employee']->emp_id) }}'"
                                        class="mt-2 text-xs font-medium text-slate-600 hover:text-slate-900"
                                    >
                                        View employee →
                                    </button>
                                </div>
                            </div>
                        </td>

                        <td class="px-4 py-4 align-top">
                            <div class="space-y-2">
                                <div class="rounded-2xl border px-3 py-2 {{ $assignmentState['box'] }}">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="flex items-center gap-2">
                                            <span class="h-2.5 w-2.5 rounded-full {{ $assignmentState['dot'] }}"></span>
                                            <span class="text-xs font-semibold {{ $assignmentState['text'] }}">
                                                Org Assignment
                                            </span>
                                        </div>

                                        <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 {{ $assignmentState['badge'] }}">
                                            {{ $assignmentState['short'] }}
                                        </span>
                                    </div>

                                    <div class="mt-1 text-[11px] {{ $assignmentState['muted'] }}">
                                        @if($row['assignment_status'] === 'OK')
                                            Branch, department, role, grade aktif.
                                        @elseif($row['assignment_status'] === 'CONFLICT')
                                            {{ $row['assignment_count'] }} org assignment aktif.
                                        @else
                                            Belum ada assignment organisasi aktif.
                                        @endif
                                    </div>
                                </div>

                                <div class="rounded-2xl border px-3 py-2 {{ $shiftState['box'] }}">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="flex items-center gap-2">
                                            <span class="h-2.5 w-2.5 rounded-full {{ $shiftState['dot'] }}"></span>
                                            <span class="text-xs font-semibold {{ $shiftState['text'] }}">
                                                Shift Assignment
                                            </span>
                                        </div>

                                        <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 {{ $shiftState['badge'] }}">
                                            {{ $shiftState['short'] }}
                                        </span>
                                    </div>

                                    <div class="mt-1 text-[11px] {{ $shiftState['muted'] }}">
                                        @if($row['shift_status'] === 'OK')
                                            Shift aktif untuk tanggal referensi.
                                        @elseif($row['shift_status'] === 'CONFLICT')
                                            {{ $row['shift_count'] }} shift assignment aktif.
                                        @else
                                            Belum ada shift assignment aktif.
                                        @endif
                                    </div>
                                </div>

                                <div class="rounded-2xl border px-3 py-2 {{ $patternState['box'] }}">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="flex items-center gap-2">
                                            <span class="h-2.5 w-2.5 rounded-full {{ $patternState['dot'] }}"></span>
                                            <span class="text-xs font-semibold {{ $patternState['text'] }}">
                                                Work Pattern
                                            </span>
                                        </div>

                                        <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 {{ $patternState['badge'] }}">
                                            {{ $patternState['short'] }}
                                        </span>
                                    </div>

                                    <div class="mt-1 text-[11px] {{ $patternState['muted'] }}">
                                        @if($row['work_pattern_status'] === 'OK')
                                            Pattern aktif untuk summary/payroll.
                                        @elseif($row['work_pattern_status'] === 'CONFLICT')
                                            {{ $row['work_pattern_count'] }} pattern aktif.
                                        @else
                                            Belum ada work pattern aktif.
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>

                        <td class="px-4 py-4 align-top">
                            <div class="space-y-3">
                                <div class="rounded-2xl border border-slate-200 bg-white px-3 py-2">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="text-xs font-semibold text-slate-700">Organization</div>
                                        <div class="text-[11px] text-slate-400">{{ $effectivePeriod($assignment) }}</div>
                                    </div>

                                    @if($assignment)
                                        <div class="mt-1 text-xs font-medium text-slate-900">
                                            {{ $assignment->branch->branch_name ?? '—' }}
                                        </div>

                                        <div class="mt-1 text-[11px] text-slate-500">
                                            {{ $assignment->department->dept_name ?? '—' }}
                                            · {{ $assignment->role->role_name ?? '—' }}
                                            · {{ $assignment->grade->grade_name ?? '—' }}
                                        </div>
                                    @else
                                        <div class="mt-1 text-xs text-slate-400">
                                            No active organization assignment.
                                        </div>
                                    @endif
                                </div>

                                <div class="rounded-2xl border border-slate-200 bg-white px-3 py-2">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="text-xs font-semibold text-slate-700">Shift</div>
                                        <div class="text-[11px] text-slate-400">{{ $effectivePeriod($shiftAssignment) }}</div>
                                    </div>

                                    @if($shiftAssignment && $shift)
                                        <div class="mt-1 flex flex-wrap items-center gap-2">
                                            <span class="text-xs font-medium text-slate-900">
                                                {{ $shift->shift_name }}
                                            </span>

                                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">
                                                {{ $shift->shift_code }}
                                            </span>
                                        </div>

                                        <div class="mt-2 grid gap-2 sm:grid-cols-3">
                                            <div class="rounded-xl bg-slate-50 px-2 py-1.5">
                                                <div class="text-[10px] uppercase tracking-wide text-slate-400">Time</div>
                                                <div class="text-xs font-semibold text-slate-700">
                                                    {{ $formatTime($shift->start_time) }}–{{ $formatTime($shift->end_time) }}
                                                </div>
                                            </div>

                                            <div class="rounded-xl bg-slate-50 px-2 py-1.5">
                                                <div class="text-[10px] uppercase tracking-wide text-slate-400">Break</div>
                                                <div class="text-xs font-semibold text-slate-700">
                                                    {{ $shift->break_min ?? 0 }} min
                                                </div>
                                            </div>

                                            <div class="rounded-xl bg-slate-50 px-2 py-1.5">
                                                <div class="text-[10px] uppercase tracking-wide text-slate-400">Work</div>
                                                <div class="text-xs font-semibold text-slate-700">
                                                    {{ $shift->default_work_min ?? 0 }} min
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mt-1 text-[11px] text-slate-500">
                                            Type:
                                            {{ $shiftAssignment->assignmentType->assignment_type_name ?? $shiftAssignment->assignment_type_code ?? '—' }}
                                            @if($shift->cross_day_flag)
                                                · Cross-day shift
                                            @endif
                                        </div>
                                    @else
                                        <div class="mt-1 text-xs text-slate-400">
                                            No active shift assignment.
                                        </div>
                                    @endif
                                </div>

                                <div class="rounded-2xl border border-slate-200 bg-white px-3 py-2">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="text-xs font-semibold text-slate-700">Work Pattern</div>
                                        <div class="text-[11px] text-slate-400">{{ $effectivePeriod($workPatternAssignment) }}</div>
                                    </div>

                                    @if($workPatternAssignment && $workPattern)
                                        <div class="mt-1 flex flex-wrap items-center gap-2">
                                            <span class="text-xs font-medium text-slate-900">
                                                {{ $workPattern->work_pattern_name }}
                                            </span>

                                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">
                                                {{ $workPattern->work_pattern_code }}
                                            </span>
                                        </div>

                                        <div class="mt-1 text-[11px] text-slate-500">
                                            Evaluation:
                                            {{ $workPattern->evaluation_mode_code ?? '—' }}
                                        </div>
                                    @else
                                        <div class="mt-1 text-xs text-slate-400">
                                            No active work pattern assignment.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </td>

                        <td class="px-4 py-4 align-top">
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $overall['class'] }}">
                                {{ $overall['label'] }}
                            </span>

                            <div class="mt-2 max-w-[160px] text-xs text-slate-500">
                                {{ $overall['description'] }}
                            </div>

                            <div class="mt-2 text-[11px] text-slate-400">
                                Ref: {{ $referenceDate }}
                            </div>
                        </td>

                        <td class="px-4 py-4 align-top">
                            <div class="flex flex-col items-end gap-2">
                                @if($canUseSetupWizard)
                                    <x-ui.button
                                        type="button"
                                        size="sm"
                                        variant="primary"
                                        onclick="window.location='{{ route('scheduling.employee-scheduling-coverage.setup.create', ['emp_id' => $row['employee']->emp_id, 'reference_date' => $referenceDate]) }}'"
                                    >
                                        Setup Missing
                                    </x-ui.button>
                                @endif

                                @if($row['assignment_status'] === 'MISSING' && auth()->user()->hasPermission('assignment.manage'))
                                    <x-ui.button
                                        type="button"
                                        size="sm"
                                        variant="ghost"
                                        onclick="window.location='{{ route('master.employee-assignments.create', ['emp_id' => $row['employee']->emp_id]) }}'"
                                    >
                                        Add Org
                                    </x-ui.button>
                                @endif

                                @if($row['shift_status'] === 'MISSING' && auth()->user()->hasPermission('shift.manage'))
                                    <x-ui.button
                                        type="button"
                                        size="sm"
                                        variant="ghost"
                                        onclick="window.location='{{ route('scheduling.employee-shift-assignments.create', ['emp_id' => $row['employee']->emp_id]) }}'"
                                    >
                                        Add Shift
                                    </x-ui.button>
                                @endif

                                @if($row['work_pattern_status'] === 'MISSING' && auth()->user()->hasPermission('workpattern.manage'))
                                    <x-ui.button
                                        type="button"
                                        size="sm"
                                        variant="ghost"
                                        onclick="window.location='{{ route('scheduling.employee-work-pattern-assignments.create', ['emp_id' => $row['employee']->emp_id]) }}'"
                                    >
                                        Add Pattern
                                    </x-ui.button>
                                @endif

                                @if($row['overall_status'] === 'COMPLETE')
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200">
                                        Ready
                                    </span>
                                @endif

                                @if($row['overall_status'] === 'CONFLICT_DETECTED')
                                    <span class="inline-flex items-center rounded-full bg-red-50 px-3 py-1 text-xs font-medium text-red-700 ring-1 ring-red-200">
                                        Review
                                    </span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-14">
                            <x-ui.empty-state
                                title="No coverage result"
                                description="Tidak ada employee yang cocok dengan filter scheduling coverage."
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-ui.table-shell>
</div>
@endsection