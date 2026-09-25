@extends('layouts.app')

@section('content')
@php
    $needsAssignment = $row['assignment_status'] === 'MISSING';
    $needsShift = $row['shift_status'] === 'MISSING';
    $needsWorkPattern = $row['work_pattern_status'] === 'MISSING';

    $willCreateAssignment = $needsAssignment && $canCreateAssignment;
    $willCreateShift = $needsShift && $canCreateShiftAssignment;
    $willCreateWorkPattern = $needsWorkPattern && $canCreateWorkPatternAssignment;

    $formatDate = function ($date) {
        if (!$date) return 'Now';
        if (is_string($date)) return \Carbon\Carbon::parse($date)->format('d M Y');
        return $date->format('d M Y');
    };

    $formatTime = function ($time) {
        if (!$time) return '—';
        if (is_string($time)) return substr($time, 0, 5);
        return $time->format('H:i');
    };

    $defaultStartDate = old('assignment_effective_start_date', $referenceDate);
@endphp

<div class="space-y-5">
    <x-ui.page-header
        title="Setup Employee Scheduling"
        subtitle="Lengkapi setup scheduling yang masih missing untuk satu employee."
        :breadcrumbs="[
            ['label' => 'Scheduling'],
            ['label' => 'Employee Scheduling Coverage', 'url' => route('scheduling.employee-scheduling-coverage.index', ['reference_date' => $referenceDate])],
            ['label' => 'Setup Missing'],
        ]"
    />

    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Employee</div>
                <div class="mt-1 text-xl font-semibold text-slate-900">{{ $employee->full_name }}</div>
                <div class="mt-1 text-sm text-slate-500">
                    {{ $employee->emp_code }}
                    @if($employee->biometric_code)
                        · Bio: {{ $employee->biometric_code }}
                    @endif
                    · Reference: {{ $referenceDate }}
                </div>
            </div>

            <div class="grid gap-2 sm:grid-cols-3">
                <div class="rounded-2xl border px-4 py-3 {{ $needsAssignment ? 'border-amber-200 bg-amber-50' : 'border-emerald-200 bg-emerald-50' }}">
                    <div class="text-xs font-medium {{ $needsAssignment ? 'text-amber-700' : 'text-emerald-700' }}">Organization</div>
                    <div class="mt-1 text-sm font-semibold {{ $needsAssignment ? 'text-amber-800' : 'text-emerald-800' }}">
                        {{ $needsAssignment ? 'Need Setup' : 'Connected' }}
                    </div>
                </div>

                <div class="rounded-2xl border px-4 py-3 {{ $needsShift ? 'border-amber-200 bg-amber-50' : 'border-emerald-200 bg-emerald-50' }}">
                    <div class="text-xs font-medium {{ $needsShift ? 'text-amber-700' : 'text-emerald-700' }}">Shift</div>
                    <div class="mt-1 text-sm font-semibold {{ $needsShift ? 'text-amber-800' : 'text-emerald-800' }}">
                        {{ $needsShift ? 'Need Setup' : 'Connected' }}
                    </div>
                </div>

                <div class="rounded-2xl border px-4 py-3 {{ $needsWorkPattern ? 'border-amber-200 bg-amber-50' : 'border-emerald-200 bg-emerald-50' }}">
                    <div class="text-xs font-medium {{ $needsWorkPattern ? 'text-amber-700' : 'text-emerald-700' }}">Work Pattern</div>
                    <div class="mt-1 text-sm font-semibold {{ $needsWorkPattern ? 'text-amber-800' : 'text-emerald-800' }}">
                        {{ $needsWorkPattern ? 'Need Setup' : 'Connected' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="rounded-3xl border border-indigo-200 bg-indigo-50 p-4">
        <div class="text-sm font-semibold text-indigo-900">Will Create</div>
        <div class="mt-2 flex flex-wrap gap-2">
            @if($willCreateAssignment)
                <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-indigo-700 ring-1 ring-indigo-200">
                    Organization Assignment
                </span>
            @endif

            @if($willCreateShift)
                <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-indigo-700 ring-1 ring-indigo-200">
                    Shift Assignment
                </span>
            @endif

            @if($willCreateWorkPattern)
                <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-indigo-700 ring-1 ring-indigo-200">
                    Work Pattern Assignment
                </span>
            @endif
        </div>

        <div class="mt-2 text-xs text-indigo-700">
            Existing setup yang sudah connected tidak akan diubah. Proses ini hanya membuat setup yang masih missing dan user berhak kelola.
        </div>
    </div>

    <form
        method="POST"
        action="{{ route('scheduling.employee-scheduling-coverage.setup.store') }}"
        x-data="{ submitting: false }"
        x-on:submit="submitting = true"
        class="space-y-5"
    >
        @csrf

        <input type="hidden" name="emp_id" value="{{ $employee->emp_id }}">
        <input type="hidden" name="reference_date" value="{{ $referenceDate }}">

        <input type="hidden" name="create_assignment" value="{{ $willCreateAssignment ? 1 : 0 }}">
        <input type="hidden" name="create_shift_assignment" value="{{ $willCreateShift ? 1 : 0 }}">
        <input type="hidden" name="create_work_pattern_assignment" value="{{ $willCreateWorkPattern ? 1 : 0 }}">

        <x-ui.section-card
            title="Existing Setup Context"
            subtitle="Setup yang sudah aktif tidak akan diubah oleh wizard ini."
        >
            <div class="grid gap-4 lg:grid-cols-3">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="text-xs font-semibold text-slate-500">Organization</div>

                    @if($row['assignment'])
                        <div class="mt-2 text-sm font-medium text-slate-900">
                            {{ $row['assignment']->branch->branch_name ?? '—' }}
                        </div>
                        <div class="mt-1 text-xs text-slate-500">
                            {{ $row['assignment']->department->dept_name ?? '—' }}
                            · {{ $row['assignment']->role->role_name ?? '—' }}
                            · {{ $row['assignment']->grade->grade_name ?? '—' }}
                        </div>
                        <div class="mt-2 text-xs text-slate-400">
                            {{ $formatDate($row['assignment']->effective_start_date) }}
                            →
                            {{ $formatDate($row['assignment']->effective_end_date) }}
                        </div>
                    @else
                        <div class="mt-2 text-sm text-amber-700">Missing organization assignment.</div>
                    @endif
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="text-xs font-semibold text-slate-500">Shift</div>

                    @if($row['shift_assignment'] && $row['shift_assignment']->shift)
                        <div class="mt-2 text-sm font-medium text-slate-900">
                            {{ $row['shift_assignment']->shift->shift_name }}
                        </div>
                        <div class="mt-1 text-xs text-slate-500">
                            {{ $row['shift_assignment']->shift->shift_code }}
                            · {{ $formatTime($row['shift_assignment']->shift->start_time) }}–{{ $formatTime($row['shift_assignment']->shift->end_time) }}
                            · Break {{ $row['shift_assignment']->shift->break_min ?? 0 }} min
                        </div>
                        <div class="mt-2 text-xs text-slate-400">
                            {{ $formatDate($row['shift_assignment']->effective_start_date) }}
                            →
                            {{ $formatDate($row['shift_assignment']->effective_end_date) }}
                        </div>
                    @else
                        <div class="mt-2 text-sm text-amber-700">Missing shift assignment.</div>
                    @endif
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="text-xs font-semibold text-slate-500">Work Pattern</div>

                    @if($row['work_pattern_assignment'] && $row['work_pattern_assignment']->workPattern)
                        <div class="mt-2 text-sm font-medium text-slate-900">
                            {{ $row['work_pattern_assignment']->workPattern->work_pattern_name }}
                        </div>
                        <div class="mt-1 text-xs text-slate-500">
                            {{ $row['work_pattern_assignment']->workPattern->work_pattern_code }}
                            · {{ $row['work_pattern_assignment']->workPattern->evaluation_mode_code ?? '—' }}
                        </div>
                        <div class="mt-2 text-xs text-slate-400">
                            {{ $formatDate($row['work_pattern_assignment']->effective_start_date) }}
                            →
                            {{ $formatDate($row['work_pattern_assignment']->effective_end_date) }}
                        </div>
                    @else
                        <div class="mt-2 text-sm text-amber-700">Missing work pattern assignment.</div>
                    @endif
                </div>
            </div>
        </x-ui.section-card>

        @if(($needsAssignment && !$canCreateAssignment) || ($needsShift && !$canCreateShiftAssignment) || ($needsWorkPattern && !$canCreateWorkPatternAssignment))
            <div class="rounded-3xl border border-amber-200 bg-amber-50 p-4">
                <div class="text-sm font-semibold text-amber-800">
                    Sebagian setup missing tidak bisa dibuat oleh user ini
                </div>
                <div class="mt-1 text-sm text-amber-700">
                    Section yang tidak muncul berarti user tidak memiliki permission manage untuk modul tersebut.
                </div>
            </div>
        @endif

        @if($willCreateAssignment)
            <x-ui.section-card
                title="Create Organization Assignment"
                subtitle="Section ini muncul karena employee belum punya assignment organisasi aktif pada tanggal referensi."
            >
                <div class="grid gap-4 md:grid-cols-2">
                    <x-ui.field label="Branch" :error="$errors->first('branch_id')">
                        <select name="branch_id" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                            <option value="">Select branch</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->branch_id }}" @selected((string) old('branch_id') === (string) $branch->branch_id)>
                                    {{ $branch->branch_name }} — {{ $branch->branch_code }}
                                </option>
                            @endforeach
                        </select>
                    </x-ui.field>

                    <x-ui.field label="Department" :error="$errors->first('dept_id')">
                        <select name="dept_id" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                            <option value="">Select department</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->dept_id }}" @selected((string) old('dept_id') === (string) $department->dept_id)>
                                    {{ $department->dept_name }}
                                </option>
                            @endforeach
                        </select>
                    </x-ui.field>

                    <x-ui.field label="Role" :error="$errors->first('role_id')">
                        <select name="role_id" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                            <option value="">Select role</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->role_id }}" @selected((string) old('role_id') === (string) $role->role_id)>
                                    {{ $role->role_name }}
                                </option>
                            @endforeach
                        </select>
                    </x-ui.field>

                    <x-ui.field label="Grade" :error="$errors->first('grade_id')">
                        <select name="grade_id" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                            <option value="">Select grade</option>
                            @foreach($grades as $grade)
                                <option value="{{ $grade->grade_id }}" @selected((string) old('grade_id') === (string) $grade->grade_id)>
                                    {{ $grade->grade_name }}
                                </option>
                            @endforeach
                        </select>
                    </x-ui.field>

                    <x-ui.field label="Effective Start Date" :error="$errors->first('assignment_effective_start_date')">
                        <input
                            type="date"
                            name="assignment_effective_start_date"
                            value="{{ old('assignment_effective_start_date', $defaultStartDate) }}"
                            class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                        >
                    </x-ui.field>

                    <x-ui.field label="Effective End Date" :error="$errors->first('assignment_effective_end_date')">
                        <input
                            type="date"
                            name="assignment_effective_end_date"
                            value="{{ old('assignment_effective_end_date') }}"
                            class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                        >
                    </x-ui.field>

                    <div class="md:col-span-2">
                        <x-ui.field label="Notes" :error="$errors->first('assignment_notes')">
                            <textarea
                                name="assignment_notes"
                                rows="3"
                                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                            >{{ old('assignment_notes', 'Created from Employee Scheduling Coverage Wizard.') }}</textarea>
                        </x-ui.field>
                    </div>
                </div>
            </x-ui.section-card>
        @endif

        @if($willCreateShift)
            <x-ui.section-card
                title="Create Shift Assignment"
                subtitle="Section ini muncul karena employee belum punya shift assignment aktif pada tanggal referensi."
            >
                <div class="grid gap-4 md:grid-cols-2">
                    <x-ui.field label="Shift" :error="$errors->first('shift_id')">
                        <select name="shift_id" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                            <option value="">Select shift</option>
                            @foreach($shifts as $shift)
                                <option value="{{ $shift->shift_id }}" @selected((string) old('shift_id') === (string) $shift->shift_id)>
                                    {{ $shift->shift_name }} — {{ $shift->shift_code }}
                                    ({{ $formatTime($shift->start_time) }}–{{ $formatTime($shift->end_time) }})
                                </option>
                            @endforeach
                        </select>
                    </x-ui.field>

                    <x-ui.field label="Assignment Type" :error="$errors->first('assignment_type_code')">
                        <select name="assignment_type_code" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                            <option value="">Select type</option>
                            @foreach($assignmentTypes as $type)
                                <option value="{{ $type->assignment_type_code }}" @selected(old('assignment_type_code', 'DEFAULT') === $type->assignment_type_code)>
                                    {{ $type->assignment_type_name }}
                                </option>
                            @endforeach
                        </select>
                    </x-ui.field>

                    <x-ui.field label="Effective Start Date" :error="$errors->first('shift_effective_start_date')">
                        <input
                            type="date"
                            name="shift_effective_start_date"
                            value="{{ old('shift_effective_start_date', $referenceDate) }}"
                            class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                        >
                    </x-ui.field>

                    <x-ui.field label="Effective End Date" :error="$errors->first('shift_effective_end_date')">
                        <input
                            type="date"
                            name="shift_effective_end_date"
                            value="{{ old('shift_effective_end_date') }}"
                            class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                        >
                    </x-ui.field>

                    <div class="md:col-span-2">
                        <x-ui.field label="Notes" :error="$errors->first('shift_notes')">
                            <textarea
                                name="shift_notes"
                                rows="3"
                                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                            >{{ old('shift_notes', 'Created from Employee Scheduling Coverage Wizard.') }}</textarea>
                        </x-ui.field>
                    </div>
                </div>
            </x-ui.section-card>
        @endif

        @if($willCreateWorkPattern)
            <x-ui.section-card
                title="Create Work Pattern Assignment"
                subtitle="Section ini muncul karena employee belum punya work pattern aktif pada tanggal referensi."
            >
                <div class="grid gap-4 md:grid-cols-2">
                    <x-ui.field label="Work Pattern" :error="$errors->first('work_pattern_id')">
                        <select name="work_pattern_id" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                            <option value="">Select work pattern</option>
                            @foreach($workPatterns as $workPattern)
                                <option value="{{ $workPattern->work_pattern_id }}" @selected((string) old('work_pattern_id') === (string) $workPattern->work_pattern_id)>
                                    {{ $workPattern->work_pattern_name }} — {{ $workPattern->work_pattern_code }}
                                </option>
                            @endforeach
                        </select>
                    </x-ui.field>

                    <div></div>

                    <x-ui.field label="Effective Start Date" :error="$errors->first('work_pattern_effective_start_date')">
                        <input
                            type="date"
                            name="work_pattern_effective_start_date"
                            value="{{ old('work_pattern_effective_start_date', $referenceDate) }}"
                            class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                        >
                    </x-ui.field>

                    <x-ui.field label="Effective End Date" :error="$errors->first('work_pattern_effective_end_date')">
                        <input
                            type="date"
                            name="work_pattern_effective_end_date"
                            value="{{ old('work_pattern_effective_end_date') }}"
                            class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                        >
                    </x-ui.field>

                    <div class="md:col-span-2">
                        <x-ui.field label="Notes" :error="$errors->first('work_pattern_notes')">
                            <textarea
                                name="work_pattern_notes"
                                rows="3"
                                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                            >{{ old('work_pattern_notes', 'Created from Employee Scheduling Coverage Wizard.') }}</textarea>
                        </x-ui.field>
                    </div>
                </div>
            </x-ui.section-card>
        @endif

        <div class="sticky bottom-4 z-10 rounded-3xl border border-slate-200 bg-white/95 p-4 shadow-lg backdrop-blur">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="text-sm font-semibold text-slate-900">
                        Save missing scheduling setup
                    </div>

                    <div class="mt-1 text-xs text-slate-500">
                        Akan dibuat:
                        @if($willCreateAssignment) organization assignment @endif
                        @if($willCreateShift){{ $willCreateAssignment ? ', ' : '' }}shift assignment @endif
                        @if($willCreateWorkPattern){{ ($willCreateAssignment || $willCreateShift) ? ', ' : '' }}work pattern assignment @endif
                        . Semua disimpan dalam satu transaksi.
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <x-ui.button
                        type="button"
                        variant="ghost"
                        onclick="window.location='{{ route('scheduling.employee-scheduling-coverage.index', ['reference_date' => $referenceDate]) }}'"
                        x-bind:disabled="submitting"
                    >
                        Cancel
                    </x-ui.button>

                    <x-ui.button
                        type="submit"
                        variant="primary"
                        x-bind:disabled="submitting"
                    >
                        <span x-show="!submitting">Create Missing Setup</span>
                        <span x-show="submitting" style="display:none;">Saving...</span>
                    </x-ui.button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection