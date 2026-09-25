@extends('layouts.app')

@section('content')
@php
    $bulkResult = session('bulk_assignment_result');

    $candidateStatus = request('candidate_status', 'ALL_ACTIVE_EMPLOYEES');
    $isAllActiveMode = $candidateStatus === 'ALL_ACTIVE_EMPLOYEES';

    $candidatePayload = $candidateRows->map(function ($row) {
        $latestAssignment = $row['latest_assignment'];

        return [
            'emp_id' => $row['employee']->emp_id,
            'emp_code' => $row['employee']->emp_code,
            'full_name' => $row['employee']->full_name,
            'employment_type_name' => $row['employment_type_name'],
            'coverage_status' => $row['coverage_status'],
            'action_mode' => $row['action_mode'],
            'coverage_note' => $row['coverage_note'],
            'grade_override_id' => null,
            'latest_assignment_summary' => $latestAssignment
                ? trim(
                    ($latestAssignment->branch->branch_name ?? '—')
                    . ' · ' .
                    ($latestAssignment->department->dept_name ?? '—')
                    . ' · ' .
                    ($latestAssignment->role->role_name ?? '—')
                )
                : null,
        ];
    })->values();

    $oldSelectedIds = collect(old('employee_ids', []))
        ->map(fn ($id) => (int) $id)
        ->filter(fn ($id) => $id > 0)
        ->values();

    $oldGradeOverrides = collect(old('employee_grade_overrides', []))
        ->mapWithKeys(fn ($gradeId, $empId) => [(int) $empId => filled($gradeId) ? (int) $gradeId : null]);

    $oldSelectedPayload = $candidatePayload
        ->filter(fn ($item) => $oldSelectedIds->contains($item['emp_id']))
        ->map(function ($item) use ($oldGradeOverrides) {
            $item['grade_override_id'] = $oldGradeOverrides->get($item['emp_id']);
            return $item;
        })
        ->values();

    $basketStorageKey = 'employee_assignment_bulk_basket_v1';

    $gradeOptions = $grades->map(fn ($grade) => [
        'id' => $grade->grade_id,
        'name' => $grade->grade_name,
    ])->values();

    $reasonMap = [
        'OVERLAP_DETECTED' => 'Tidak diproses karena terdeteksi lebih dari satu assignment aktif.',
        'OVERLAP_ASSIGNMENT' => 'Tidak diproses karena periode assignment bentrok.',
        'EMPLOYEE_INACTIVE' => 'Tidak diproses karena employee sudah tidak aktif.',
        'EMPLOYEE_NOT_FOUND' => 'Tidak diproses karena employee tidak ditemukan.',
        'INVALID_REPLACEMENT_RANGE' => 'Tidak diproses karena rentang replacement tidak valid.',
    ];
@endphp

<div
    class="space-y-3"
    x-data="bulkAssignmentBasket({
        storageKey: @js($basketStorageKey),
        visibleCandidates: {{ $candidatePayload->toJson() }},
        initialSelected: {{ $oldSelectedPayload->toJson() }},
        gradeOptions: {{ $gradeOptions->toJson() }},
        defaultGradeId: @js(old('grade_id')),
    })"
    x-init="init()"
>
    <x-ui.page-header
        title="Bulk Employee Assignment"
        subtitle="Kelola assignment massal untuk create baru, replace active, atau review conflict."
        :breadcrumbs="[
            ['label' => 'Master'],
            ['label' => 'Employee Assignments', 'url' => route('master.employee-assignments.index')],
            ['label' => 'Bulk Assignment'],
        ]"
    >
        <x-slot:actions>
            <div class="flex items-center gap-2">
                <x-ui.button
                    type="button"
                    variant="ghost"
                    onclick="window.location='{{ route('master.employee-assignments.coverage') }}'"
                >
                    Coverage
                </x-ui.button>

                <x-ui.button
                    type="button"
                    variant="ghost"
                    onclick="window.location='{{ route('master.employee-assignments.index') }}'"
                >
                    Back
                </x-ui.button>
            </div>
        </x-slot:actions>
    </x-ui.page-header>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm text-emerald-800">
            {{ session('success') }}
        </div>

        <script>
            try {
                sessionStorage.removeItem(@json($basketStorageKey));
            } catch (e) {
                // no-op
            }
        </script>
    @endif

    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm text-rose-800">
            {{ $errors->first() }}
        </div>
    @endif

    @if($isAllActiveMode)
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm text-amber-900">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="font-medium">All Active Employees mode</div>
                    <div class="mt-0.5 text-[11px] text-amber-800">
                        Create baru untuk yang tidak aktif, replace otomatis untuk satu active assignment, conflict akan skipped.
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-2.5 text-sm text-sky-900">
            <div>
                <div class="font-medium">Safe Candidates mode</div>
                <div class="mt-0.5 text-[11px] text-sky-800">
                    Hanya employee tanpa assignment aktif yang diproses.
                </div>
            </div>
        </div>
    @endif

    @if($bulkResult)
        <div class="rounded-2xl border border-slate-200 bg-white p-3.5 shadow-sm">
            <div class="mb-2 flex items-center justify-between gap-3">
                <div>
                    <div class="text-sm font-semibold text-slate-900">Last Bulk Result</div>
                    <div class="text-[11px] text-slate-500">Ringkasan hasil proses terakhir.</div>
                </div>
            </div>

            <div class="grid gap-3 md:grid-cols-4">
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5">
                    <div class="text-[10px] uppercase tracking-wide text-slate-500">Total Selected</div>
                    <div class="mt-0.5 text-base font-semibold text-slate-900">{{ $bulkResult['total_selected'] ?? 0 }}</div>
                </div>

                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2.5">
                    <div class="text-[10px] uppercase tracking-wide text-emerald-700">Created</div>
                    <div class="mt-0.5 text-base font-semibold text-emerald-800">{{ $bulkResult['created_count'] ?? ($bulkResult['inserted_count'] ?? 0) }}</div>
                </div>

                <div class="rounded-xl border border-violet-200 bg-violet-50 px-3 py-2.5">
                    <div class="text-[10px] uppercase tracking-wide text-violet-700">Replaced</div>
                    <div class="mt-0.5 text-base font-semibold text-violet-800">{{ $bulkResult['replaced_count'] ?? 0 }}</div>
                </div>

                <div class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2.5">
                    <div class="text-[10px] uppercase tracking-wide text-rose-700">Skipped</div>
                    <div class="mt-0.5 text-base font-semibold text-rose-800">{{ $bulkResult['skipped_count'] ?? 0 }}</div>
                </div>
            </div>

            @if(!empty($bulkResult['skipped_items']))
                <div class="mt-3">
                    <div class="mb-1.5 text-[11px] font-medium uppercase tracking-wide text-slate-500">Skipped Items</div>
                    <x-ui.table-shell>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="bg-slate-50/80">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                                            Employee
                                        </th>
                                        <th class="px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                                            Reason
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @foreach($bulkResult['skipped_items'] as $item)
                                        <tr>
                                            <td class="px-4 py-3">
                                                <div class="font-medium text-slate-900">
                                                    {{ $item['full_name'] ?? '-' }}
                                                </div>
                                                <div class="mt-1 text-xs text-slate-500">
                                                    {{ $item['emp_code'] ?? '-' }}
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-slate-600">
                                                {{ $reasonMap[$item['reason'] ?? ''] ?? ($item['reason'] ?? '-') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </x-ui.table-shell>
                </div>
            @endif
        </div>
    @endif

    <x-ui.page-section
        title="Candidate Filter"
        subtitle="Gunakan filter ini untuk mempersempit daftar kandidat."
    >
        <form method="GET" action="{{ route('master.employee-assignments.bulk-create') }}" class="grid gap-2.5 xl:grid-cols-[170px_minmax(0,1fr)_220px_240px_auto]">
            <x-ui.field label="Reference Date">
                <input
                    type="date"
                    name="reference_date"
                    value="{{ request('reference_date', $referenceDate) }}"
                    class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800"
                >
            </x-ui.field>

            <x-ui.field label="Search">
                <input
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Cari emp code / nama / biometric"
                    class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800"
                >
            </x-ui.field>

            <x-ui.field label="Employment Type">
                <select
                    name="employment_type_id"
                    class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800"
                >
                    <option value="">All</option>
                    @foreach($employmentTypes as $employmentType)
                        <option
                            value="{{ $employmentType->employment_type_id }}"
                            @selected((string) request('employment_type_id') === (string) $employmentType->employment_type_id)
                        >
                            {{ $employmentType->employment_type_name }}
                        </option>
                    @endforeach
                </select>
            </x-ui.field>

            <x-ui.field label="Candidate Scope">
                <select
                    name="candidate_status"
                    class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800"
                >
                    <option value="ALL_ACTIVE_EMPLOYEES" @selected($candidateStatus === 'ALL_ACTIVE_EMPLOYEES')>
                        All Active Employees
                    </option>
                    <option value="SAFE_CANDIDATES" @selected($candidateStatus === 'SAFE_CANDIDATES')>
                        Safe Candidates Only
                    </option>
                    <option value="NO_ASSIGNMENT" @selected($candidateStatus === 'NO_ASSIGNMENT')>
                        No Assignment
                    </option>
                    <option value="NO_ACTIVE_ASSIGNMENT" @selected($candidateStatus === 'NO_ACTIVE_ASSIGNMENT')>
                        No Active Assignment
                    </option>
                </select>
            </x-ui.field>

            <div class="flex items-end gap-2 self-end">
                <x-ui.button type="submit">
                    Search
                </x-ui.button>

                <x-ui.button
                    type="button"
                    variant="ghost"
                    onclick="window.location='{{ route('master.employee-assignments.bulk-create') }}'"
                >
                    Reset
                </x-ui.button>
            </div>
        </form>
    </x-ui.page-section>

    <form
        method="POST"
        action="{{ route('master.employee-assignments.bulk-store') }}"
        class="space-y-3.5"
        @submit="handleSubmit($event)"
    >
        @csrf

        <input type="hidden" name="reference_date" value="{{ request('reference_date', $referenceDate) }}">
        <input type="hidden" name="q" value="{{ request('q') }}">
        <input type="hidden" name="employment_type_id" value="{{ request('employment_type_id') }}">
        <input type="hidden" name="candidate_status" value="{{ $candidateStatus }}">

        <template x-for="employee in selectedEmployees" :key="employee.emp_id">
            <div>
                <input type="hidden" name="employee_ids[]" :value="employee.emp_id">
                <input
                    type="hidden"
                    :name="`employee_grade_overrides[${employee.emp_id}]`"
                    :value="employee.grade_override_id ?? ''"
                >
            </div>
        </template>

        <x-ui.section-card
            title="Assignment Definition"
            subtitle="Set assignment utama. Grade bisa diubah per employee di basket."
        >
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                <x-ui.field label="Branch" :error="$errors->first('branch_id')">
                    <select name="branch_id" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Select branch</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->branch_id }}" @selected((string) old('branch_id') === (string) $branch->branch_id)>
                                {{ $branch->branch_name }}
                            </option>
                        @endforeach
                    </select>
                </x-ui.field>

                <x-ui.field label="Department" :error="$errors->first('dept_id')">
                    <select name="dept_id" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Select department</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->dept_id }}" @selected((string) old('dept_id') === (string) $department->dept_id)>
                                {{ $department->dept_name }}
                            </option>
                        @endforeach
                    </select>
                </x-ui.field>

                <x-ui.field label="Role" :error="$errors->first('role_id')">
                    <select name="role_id" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Select role</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->role_id }}" @selected((string) old('role_id') === (string) $role->role_id)>
                                {{ $role->role_name }} — {{ $role->department->dept_name ?? '-' }}
                            </option>
                        @endforeach
                    </select>
                </x-ui.field>

                <x-ui.field label="Default Grade" :error="$errors->first('grade_id')">
                    <select
                        name="grade_id"
                        x-model="defaultGradeId"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                    >
                        <option value="">Select default grade</option>
                        @foreach($grades as $grade)
                            <option value="{{ $grade->grade_id }}" @selected((string) old('grade_id') === (string) $grade->grade_id)>
                                {{ $grade->grade_name }}
                            </option>
                        @endforeach
                    </select>
                </x-ui.field>

                <x-ui.field label="Effective Start Date" :error="$errors->first('effective_start_date')">
                    <input
                        type="date"
                        name="effective_start_date"
                        value="{{ old('effective_start_date') }}"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                    >
                </x-ui.field>

                <x-ui.field label="Effective End Date" :error="$errors->first('effective_end_date')">
                    <input
                        type="date"
                        name="effective_end_date"
                        value="{{ old('effective_end_date') }}"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                    >
                    <div class="mt-1 text-[11px] text-slate-500">
                        Kosongkan jika assignment masih aktif.
                    </div>
                </x-ui.field>

                <x-ui.field label="Primary Assignment" :error="$errors->first('is_primary')">
                    <select name="is_primary" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        <option value="1" @selected((string) old('is_primary', '1') === '1')>Yes</option>
                        <option value="0" @selected((string) old('is_primary') === '0')>No</option>
                    </select>
                </x-ui.field>

                <div class="md:col-span-2 xl:col-span-4">
                    <x-ui.field label="Notes" :error="$errors->first('notes')">
                        <textarea
                            name="notes"
                            rows="2"
                            class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                        >{{ old('notes') }}</textarea>
                    </x-ui.field>
                </div>
            </div>
        </x-ui.section-card>

        <div class="grid gap-4 xl:grid-cols-[minmax(0,2fr)_minmax(360px,1fr)]">
            <div class="min-w-0">
                <x-ui.section-card
                    title="Candidate Results"
                    subtitle="Pilih employee yang akan diproses. Action mode menunjukkan apakah item akan dibuat baru, direplace, atau diblok."
                >
                    <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div class="text-sm text-slate-600">
                                <span class="font-medium text-slate-900">{{ $candidateRows->count() }}</span> employee tampil
                            </div>
                            @if($isAllActiveMode)
                                <div class="mt-1 text-[11px] text-slate-500">
                                    Item dengan status blocked tidak bisa diproses otomatis.
                                </div>
                            @endif
                        </div>

                        <div class="flex items-center gap-2">
                            <x-ui.button
                                type="button"
                                variant="ghost"
                                x-on:click="addAllVisible()"
                            >
                                Add All Visible
                            </x-ui.button>
                        </div>
                    </div>

                    @if($candidateRows->isNotEmpty())
                        <x-ui.table-shell>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-slate-200 text-sm">
                                    <thead class="bg-slate-50/80">
                                        <tr>
                                            <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                                                Employee
                                            </th>
                                            <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                                                Coverage
                                            </th>
                                            <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                                                Action Mode
                                            </th>
                                            <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                                                Snapshot
                                            </th>
                                            <th class="px-4 py-2.5 text-right text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                                                Action
                                            </th>
                                        </tr>
                                    </thead>

                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        @foreach($candidateRows as $row)
                                            @php
                                                $status = $row['coverage_status'];
                                                $actionMode = $row['action_mode'];
                                                $latestAssignment = $row['latest_assignment'];

                                                $statusConfig = match ($status) {
                                                    'NO_ASSIGNMENT' => ['label' => 'No Assignment', 'class' => 'bg-rose-100 text-rose-700 ring-rose-200'],
                                                    'NO_ACTIVE_ASSIGNMENT' => ['label' => 'No Active Assignment', 'class' => 'bg-amber-100 text-amber-700 ring-amber-200'],
                                                    'HAS_ACTIVE_ASSIGNMENT' => ['label' => 'Has Active Assignment', 'class' => 'bg-emerald-100 text-emerald-700 ring-emerald-200'],
                                                    default => ['label' => 'Overlap Detected', 'class' => 'bg-red-100 text-red-700 ring-red-200'],
                                                };

                                                $actionConfig = match ($actionMode) {
                                                    'CREATE_NEW' => ['label' => 'Create New', 'class' => 'bg-sky-100 text-sky-700 ring-sky-200'],
                                                    'REPLACE_ACTIVE' => ['label' => 'Replace Active', 'class' => 'bg-violet-100 text-violet-700 ring-violet-200'],
                                                    default => ['label' => 'Blocked', 'class' => 'bg-slate-100 text-slate-700 ring-slate-200'],
                                                };

                                                $employeeJs = [
                                                    'emp_id' => $row['employee']->emp_id,
                                                    'emp_code' => $row['employee']->emp_code,
                                                    'full_name' => $row['employee']->full_name,
                                                    'employment_type_name' => $row['employment_type_name'],
                                                    'coverage_status' => $row['coverage_status'],
                                                    'action_mode' => $row['action_mode'],
                                                    'coverage_note' => $row['coverage_note'],
                                                    'grade_override_id' => null,
                                                    'latest_assignment_summary' => $latestAssignment
                                                        ? trim(
                                                            ($latestAssignment->branch->branch_name ?? '—')
                                                            . ' · ' .
                                                            ($latestAssignment->department->dept_name ?? '—')
                                                            . ' · ' .
                                                            ($latestAssignment->role->role_name ?? '—')
                                                        )
                                                        : null,
                                                ];
                                            @endphp

                                            <tr class="transition {{ $actionMode === 'BLOCKED' ? 'bg-slate-50/60 text-slate-400' : 'hover:bg-slate-50' }}">
                                                <td class="px-4 py-3">
                                                    <div class="font-medium {{ $actionMode === 'BLOCKED' ? 'text-slate-500' : 'text-slate-900' }} leading-tight">
                                                        {{ $row['employee']->full_name }}
                                                    </div>
                                                    <div class="mt-1 text-xs {{ $actionMode === 'BLOCKED' ? 'text-slate-400' : 'text-slate-500' }} leading-tight">
                                                        {{ $row['employee']->emp_code }}
                                                        @if($row['employee']->biometric_code)
                                                            · {{ $row['employee']->biometric_code }}
                                                        @endif
                                                        <span class="mx-1">·</span>
                                                        {{ $row['employment_type_name'] }}
                                                    </div>
                                                </td>

                                                <td class="px-4 py-3 align-top">
                                                    <span class="inline-flex items-center rounded-full px-2 py-1 text-[11px] font-medium ring-1 {{ $statusConfig['class'] }}">
                                                        {{ $statusConfig['label'] }}
                                                    </span>
                                                </td>

                                                <td class="px-4 py-3 align-top">
                                                    <span class="inline-flex items-center rounded-full px-2 py-1 text-[11px] font-medium ring-1 {{ $actionConfig['class'] }}">
                                                        {{ $actionConfig['label'] }}
                                                    </span>
                                                </td>

                                                <td class="px-4 py-3 align-top">
                                                    @if($latestAssignment)
                                                        <div class="text-xs leading-tight {{ $actionMode === 'BLOCKED' ? 'text-slate-400' : 'text-slate-600' }}">
                                                            {{ $latestAssignment->branch->branch_name ?? '—' }}
                                                            <span class="mx-1">·</span>
                                                            {{ $latestAssignment->department->dept_name ?? '—' }}
                                                            <span class="mx-1">·</span>
                                                            {{ $latestAssignment->role->role_name ?? '—' }}
                                                        </div>
                                                    @else
                                                        <span class="text-xs text-slate-400">—</span>
                                                    @endif
                                                </td>

                                                <td class="px-4 py-3 align-top">
                                                    <div class="flex justify-end">
                                                        @if($actionMode === 'BLOCKED')
                                                            <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-1 text-[11px] font-medium text-red-700 ring-1 ring-red-200">
                                                                Blocked
                                                            </span>
                                                        @else
                                                            <template x-if="!hasEmployee({{ $row['employee']->emp_id }})">
                                                                <button
                                                                    type="button"
                                                                    class="inline-flex min-w-[68px] items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50"
                                                                    x-on:click='addEmployee(@json($employeeJs))'
                                                                >
                                                                    Add
                                                                </button>
                                                            </template>

                                                            <template x-if="hasEmployee({{ $row['employee']->emp_id }})">
                                                                <span class="inline-flex min-w-[68px] items-center justify-center rounded-full bg-emerald-100 px-2 py-1 text-[11px] font-medium text-emerald-700 ring-1 ring-emerald-200">
                                                                    Added
                                                                </span>
                                                            </template>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </x-ui.table-shell>
                    @else
                        <x-ui.empty-state
                            title="No candidate found"
                            description="Tidak ada employee yang cocok dengan filter kandidat saat ini."
                        />
                    @endif
                </x-ui.section-card>
            </div>

            <div class="min-w-0">
                <x-ui.section-card
                    title="Selected Employees"
                    subtitle="Review final basket sebelum diproses. Grade masih bisa disesuaikan per employee."
                >
                    <div class="mb-3 flex items-start justify-between gap-3">
                        <div>
                            <div class="text-sm text-slate-600">
                                <span class="font-medium text-slate-900" x-text="selectedEmployees.length"></span> selected
                            </div>
                            <div class="mt-1 text-[11px] text-slate-500">
                                <span>Create: <span class="font-medium text-slate-700" x-text="createCount()"></span></span>
                                <span class="mx-1.5">·</span>
                                <span>Replace: <span class="font-medium text-slate-700" x-text="replaceCount()"></span></span>
                            </div>
                        </div>

                        <x-ui.button
                            type="button"
                            variant="ghost"
                            x-on:click="clearAll()"
                            x-bind:disabled="selectedEmployees.length === 0"
                        >
                            Clear Basket
                        </x-ui.button>
                    </div>

                    <div class="space-y-2.5 min-h-[16rem] max-h-[38rem] overflow-auto pr-1">
                        <template x-if="selectedEmployees.length === 0">
                            <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
                                Belum ada employee yang dipilih.
                            </div>
                        </template>

                        <template x-for="employee in selectedEmployees" :key="employee.emp_id">
                            <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0 flex-1">
                                        <div class="truncate text-sm font-semibold text-slate-900" x-text="employee.full_name"></div>
                                        <div class="mt-1 text-xs text-slate-500 leading-tight">
                                            <span x-text="employee.emp_code"></span>
                                            <span> · </span>
                                            <span x-text="employee.employment_type_name"></span>
                                        </div>

                                        <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                            <span
                                                class="inline-flex items-center rounded-full px-2 py-1 text-[11px] font-medium ring-1"
                                                :class="statusBadgeClass(employee.coverage_status)"
                                                x-text="statusBadgeLabel(employee.coverage_status)"
                                            ></span>

                                            <span
                                                class="inline-flex items-center rounded-full px-2 py-1 text-[11px] font-medium ring-1"
                                                :class="actionBadgeClass(employee.action_mode)"
                                                x-text="actionBadgeLabel(employee.action_mode)"
                                            ></span>
                                        </div>

                                        <template x-if="employee.latest_assignment_summary">
                                            <div class="mt-2 text-[11px] text-slate-500 leading-tight" x-text="employee.latest_assignment_summary"></div>
                                        </template>

                                        <template x-if="employee.action_mode === 'REPLACE_ACTIVE'">
                                            <div class="mt-1.5 text-[11px] text-amber-700">
                                                Current active assignment will be ended automatically.
                                            </div>
                                        </template>

                                        <div class="mt-3">
                                            <label class="mb-1 block text-[11px] font-medium uppercase tracking-wide text-slate-500">
                                                Grade
                                            </label>

                                            <select
                                                x-model="employee.grade_override_id"
                                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                                            >
                                                <option value="">Use default grade</option>
                                                <template x-for="grade in gradeOptions" :key="grade.id">
                                                    <option :value="String(grade.id)" x-text="grade.name"></option>
                                                </template>
                                            </select>
                                        </div>
                                    </div>

                                    <button
                                        type="button"
                                        class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50"
                                        x-on:click="removeEmployee(employee.emp_id)"
                                    >
                                        Remove
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </x-ui.section-card>
            </div>
        </div>

        <div class="flex items-center justify-between gap-3">
            <div class="text-xs text-slate-500">
                <span x-text="selectedEmployees.length"></span> employee siap diproses
                <template x-if="selectedEmployees.length > 0">
                    <span>
                        · Create <span class="font-medium text-slate-700" x-text="createCount()"></span>
                        · Replace <span class="font-medium text-slate-700" x-text="replaceCount()"></span>
                    </span>
                </template>
            </div>

            <div class="flex items-center gap-2">
                <x-ui.button
                    type="button"
                    variant="ghost"
                    onclick="window.location='{{ route('master.employee-assignments.index') }}'"
                    x-bind:disabled="submitting"
                >
                    Cancel
                </x-ui.button>

                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
                    x-bind:disabled="submitting || selectedEmployees.length === 0"
                >
                    <span x-show="!submitting">Process Bulk Assignment</span>
                    <span x-show="submitting" style="display: none;">Processing...</span>
                </button>
            </div>
        </div>
    </form>
</div>

<script>
    function bulkAssignmentBasket({ storageKey, visibleCandidates = [], initialSelected = [], gradeOptions = [], defaultGradeId = '' }) {
        return {
            storageKey,
            submitting: false,
            visibleCandidates,
            gradeOptions,
            defaultGradeId: defaultGradeId ? String(defaultGradeId) : '',
            selectedEmployees: initialSelected.map(item => ({
                ...item,
                grade_override_id: item.grade_override_id ? String(item.grade_override_id) : '',
            })),

            init() {
                const saved = this.loadFromStorage();

                if (Array.isArray(saved) && saved.length > 0) {
                    this.selectedEmployees = saved.map(item => ({
                        ...item,
                        grade_override_id: item.grade_override_id ? String(item.grade_override_id) : '',
                    }));
                } else if (Array.isArray(this.initialSelectedFallback()) && this.initialSelectedFallback().length > 0) {
                    this.selectedEmployees = this.initialSelectedFallback().map(item => ({
                        ...item,
                        grade_override_id: item.grade_override_id ? String(item.grade_override_id) : '',
                    }));
                    this.saveToStorage();
                }

                this.$watch('selectedEmployees', () => {
                    this.saveToStorage();
                });
            },

            initialSelectedFallback() {
                return initialSelected;
            },

            loadFromStorage() {
                try {
                    const raw = sessionStorage.getItem(this.storageKey);
                    if (!raw) {
                        return [];
                    }

                    const parsed = JSON.parse(raw);
                    return Array.isArray(parsed) ? parsed : [];
                } catch (e) {
                    return [];
                }
            },

            saveToStorage() {
                try {
                    sessionStorage.setItem(this.storageKey, JSON.stringify(this.selectedEmployees));
                } catch (e) {
                    // no-op
                }
            },

            clearStorage() {
                try {
                    sessionStorage.removeItem(this.storageKey);
                } catch (e) {
                    // no-op
                }
            },

            hasEmployee(empId) {
                return this.selectedEmployees.some(item => Number(item.emp_id) === Number(empId));
            },

            addEmployee(employee) {
                if (employee.action_mode === 'BLOCKED') {
                    return;
                }

                if (this.hasEmployee(employee.emp_id)) {
                    return;
                }

                this.selectedEmployees.push({
                    ...employee,
                    grade_override_id: employee.grade_override_id ? String(employee.grade_override_id) : '',
                });
            },

            removeEmployee(empId) {
                this.selectedEmployees = this.selectedEmployees.filter(
                    item => Number(item.emp_id) !== Number(empId)
                );
            },

            clearAll() {
                this.selectedEmployees = [];
                this.clearStorage();
            },

            addAllVisible() {
                this.visibleCandidates.forEach(employee => {
                    if (employee.action_mode === 'BLOCKED') {
                        return;
                    }

                    if (!this.hasEmployee(employee.emp_id)) {
                        this.selectedEmployees.push({
                            ...employee,
                            grade_override_id: employee.grade_override_id ? String(employee.grade_override_id) : '',
                        });
                    }
                });
            },

            handleSubmit(event) {
                if (this.submitting) {
                    event.preventDefault();
                    return;
                }

                if (this.selectedEmployees.length === 0) {
                    event.preventDefault();
                    alert('Pilih minimal 1 employee untuk diproses.');
                    return;
                }

                this.submitting = true;
            },

            statusBadgeLabel(status) {
                switch (status) {
                    case 'NO_ASSIGNMENT':
                        return 'No Assignment';
                    case 'NO_ACTIVE_ASSIGNMENT':
                        return 'No Active Assignment';
                    case 'HAS_ACTIVE_ASSIGNMENT':
                        return 'Has Active Assignment';
                    case 'OVERLAP_DETECTED':
                        return 'Overlap Detected';
                    default:
                        return status;
                }
            },

            statusBadgeClass(status) {
                switch (status) {
                    case 'NO_ASSIGNMENT':
                        return 'bg-rose-100 text-rose-700 ring-rose-200';
                    case 'NO_ACTIVE_ASSIGNMENT':
                        return 'bg-amber-100 text-amber-700 ring-amber-200';
                    case 'HAS_ACTIVE_ASSIGNMENT':
                        return 'bg-emerald-100 text-emerald-700 ring-emerald-200';
                    case 'OVERLAP_DETECTED':
                        return 'bg-red-100 text-red-700 ring-red-200';
                    default:
                        return 'bg-slate-100 text-slate-700 ring-slate-200';
                }
            },

            actionBadgeLabel(mode) {
                switch (mode) {
                    case 'CREATE_NEW':
                        return 'Create New';
                    case 'REPLACE_ACTIVE':
                        return 'Replace Active';
                    case 'BLOCKED':
                        return 'Blocked';
                    default:
                        return mode;
                }
            },

            actionBadgeClass(mode) {
                switch (mode) {
                    case 'CREATE_NEW':
                        return 'bg-sky-100 text-sky-700 ring-sky-200';
                    case 'REPLACE_ACTIVE':
                        return 'bg-violet-100 text-violet-700 ring-violet-200';
                    case 'BLOCKED':
                        return 'bg-slate-100 text-slate-700 ring-slate-200';
                    default:
                        return 'bg-slate-100 text-slate-700 ring-slate-200';
                }
            },

            createCount() {
                return this.selectedEmployees.filter(item => item.action_mode === 'CREATE_NEW').length;
            },

            replaceCount() {
                return this.selectedEmployees.filter(item => item.action_mode === 'REPLACE_ACTIVE').length;
            },
        };
    }
</script>
@endsection 