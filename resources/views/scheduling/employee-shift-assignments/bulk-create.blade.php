@extends('layouts.app')

@section('content')
@php
    $bulkResult = session('bulk_shift_assignment_result');

    $candidateStatus = request('candidate_status', 'ALL_ACTIVE_EMPLOYEES');
    $isAllActiveMode = $candidateStatus === 'ALL_ACTIVE_EMPLOYEES';

    $candidatePayload = $candidateRows->map(function ($row) {
        $latestAssignment = $row['latest_assignment'];
        $activeAssignment = $row['active_assignment'];

        return [
            'emp_id' => $row['employee']->emp_id,
            'emp_code' => $row['employee']->emp_code,
            'full_name' => $row['employee']->full_name,
            'employment_type_name' => $row['employment_type_name'],
            'coverage_status' => $row['coverage_status'],
            'action_mode' => $row['action_mode'],
            'coverage_note' => $row['coverage_note'],
            'active_shift_summary' => $activeAssignment
                ? trim(
                    ($activeAssignment->shift->shift_name ?? '—')
                    . ' · ' .
                    ($activeAssignment->assignmentType->assignment_type_name ?? $activeAssignment->assignment_type_code ?? '—')
                )
                : null,
            'latest_assignment_summary' => $latestAssignment
                ? trim(
                    ($latestAssignment->shift->shift_name ?? '—')
                    . ' · ' .
                    ($latestAssignment->assignmentType->assignment_type_name ?? $latestAssignment->assignment_type_code ?? '—')
                )
                : null,
        ];
    })->values();

    $oldSelectedIds = collect(old('employee_ids', []))
        ->map(fn ($id) => (int) $id)
        ->filter(fn ($id) => $id > 0)
        ->values();

    $oldSelectedPayload = $candidatePayload
        ->filter(fn ($item) => $oldSelectedIds->contains($item['emp_id']))
        ->values();

    $basketStorageKey = 'employee_shift_assignment_bulk_basket_v1';

    $reasonMap = [
        'OVERLAP_DETECTED' => 'Dilewati karena terdeteksi lebih dari satu shift assignment aktif.',
        'OVERLAP_ASSIGNMENT' => 'Dilewati karena periode shift assignment bentrok.',
        'EMPLOYEE_INACTIVE' => 'Dilewati karena employee sudah tidak aktif.',
        'EMPLOYEE_NOT_FOUND' => 'Dilewati karena employee tidak ditemukan.',
        'INVALID_REPLACEMENT_RANGE' => 'Dilewati karena rentang replacement tidak valid.',
    ];
@endphp

<div
    class="space-y-4"
    x-data="bulkShiftAssignmentBasket({
        storageKey: @js($basketStorageKey),
        visibleCandidates: {{ $candidatePayload->toJson() }},
        initialSelected: {{ $oldSelectedPayload->toJson() }},
    })"
    x-init="init()"
>
    <x-ui.page-header
        title="Bulk Employee Shift Assignment"
        subtitle="Tetapkan shift baru secara massal untuk employee yang belum punya shift aktif, atau ganti shift aktif secara terkontrol."
        :breadcrumbs="[
            ['label' => 'Scheduling'],
            ['label' => 'Employee Shift Assignments', 'url' => route('scheduling.employee-shift-assignments.index')],
            ['label' => 'Bulk Assignment'],
        ]"
    >
        <x-slot:actions>
            <div class="flex items-center gap-2">
                <x-ui.button
                    type="button"
                    variant="ghost"
                    onclick="window.location='{{ route('scheduling.employee-shift-assignments.coverage') }}'"
                >
                    Coverage
                </x-ui.button>

                <x-ui.button
                    type="button"
                    variant="ghost"
                    onclick="window.location='{{ route('scheduling.employee-shift-assignments.index') }}'"
                >
                    Back
                </x-ui.button>
            </div>
        </x-slot:actions>
    </x-ui.page-header>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>

        <script>
            try {
                sessionStorage.removeItem(@json($basketStorageKey));
            } catch (e) {}
        </script>
    @endif

    @if($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            {{ $errors->first() }}
        </div>
    @endif

    @if($isAllActiveMode)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <div class="font-medium">Mode: All Active Employees</div>
            <div class="mt-1 text-xs text-amber-800">
                Employee tanpa shift aktif akan dibuatkan assignment baru. Employee dengan satu shift aktif akan diproses sebagai replace. Conflict tetap akan dilewati.
            </div>
        </div>
    @else
        <div class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900">
            <div class="font-medium">Mode: Safe Candidates</div>
            <div class="mt-1 text-xs text-sky-800">
                Fokus pada employee yang belum memiliki shift assignment aktif di tanggal referensi, sehingga lebih aman untuk bulk create biasa.
            </div>
        </div>
    @endif

    @if($bulkResult)
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="mb-3">
                <div class="text-sm font-semibold text-slate-900">Hasil Proses Terakhir</div>
                <div class="text-xs text-slate-500">Ringkasan eksekusi bulk shift assignment terbaru.</div>
            </div>

            <div class="grid gap-3 md:grid-cols-4">
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5">
                    <div class="text-[10px] uppercase tracking-wide text-slate-500">Selected</div>
                    <div class="mt-0.5 text-base font-semibold text-slate-900">{{ $bulkResult['total_selected'] ?? 0 }}</div>
                </div>

                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2.5">
                    <div class="text-[10px] uppercase tracking-wide text-emerald-700">Created</div>
                    <div class="mt-0.5 text-base font-semibold text-emerald-800">{{ $bulkResult['created_count'] ?? 0 }}</div>
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
                <div class="mt-4">
                    <div class="mb-2 text-[11px] font-medium uppercase tracking-wide text-slate-500">Skipped Items</div>

                    <x-ui.table-shell>
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
                    </x-ui.table-shell>
                </div>
            @endif
        </div>
    @endif

    <x-ui.page-section
        title="Candidate Filter"
        subtitle="Saring employee terlebih dahulu, lalu masukkan kandidat yang ingin diproses ke basket."
    >
        <form method="GET" action="{{ route('scheduling.employee-shift-assignments.bulk-create') }}" class="grid gap-3 xl:grid-cols-[170px_minmax(0,1fr)_220px_240px_auto]">
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
                    <option value="SAFE_CANDIDATES" @selected($candidateStatus === 'SAFE_CANDIDATES')>Safe Candidates</option>
                    <option value="NO_ASSIGNMENT" @selected($candidateStatus === 'NO_ASSIGNMENT')>No Assignment</option>
                    <option value="NO_ACTIVE_ASSIGNMENT" @selected($candidateStatus === 'NO_ACTIVE_ASSIGNMENT')>No Active Assignment</option>
                    <option value="ALL_ACTIVE_EMPLOYEES" @selected($candidateStatus === 'ALL_ACTIVE_EMPLOYEES')>All Active Employees</option>
                </select>
            </x-ui.field>

            <div class="flex items-end gap-3">
                <x-ui.button type="submit">Search</x-ui.button>

                <x-ui.button
                    type="button"
                    variant="ghost"
                    onclick="window.location='{{ route('scheduling.employee-shift-assignments.bulk-create') }}'"
                >
                    Reset
                </x-ui.button>
            </div>
        </form>
    </x-ui.page-section>

    <form
        method="POST"
        action="{{ route('scheduling.employee-shift-assignments.bulk-store') }}"
        class="space-y-4"
        @submit="handleSubmit($event)"
    >
        @csrf

        <input type="hidden" name="reference_date" value="{{ request('reference_date', $referenceDate) }}">
        <input type="hidden" name="q" value="{{ request('q') }}">
        <input type="hidden" name="employment_type_id" value="{{ request('employment_type_id') }}">
        <input type="hidden" name="candidate_status" value="{{ $candidateStatus }}">

        <div class="grid gap-4 xl:grid-cols-[minmax(0,1.45fr)_400px]">
            <x-ui.page-section
                title="Candidate Table"
                subtitle="Tambahkan employee ke basket. Baris blocked tidak dapat diproses otomatis."
            >
                <div class="mb-3 flex items-center justify-between gap-3">
                    <div class="text-xs text-slate-500">
                        Visible candidates: <span class="font-medium text-slate-700">{{ $candidateRows->count() }}</span>
                    </div>

                    <div class="flex items-center gap-2">
                        <x-ui.button type="button" size="sm" variant="ghost" @click="addAllVisible()">
                            Add All Visible
                        </x-ui.button>
                    </div>
                </div>

                <x-ui.table-shell>
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50/80">
                            <tr>
                                <th class="px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wide text-slate-500">Employee</th>
                                <th class="px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wide text-slate-500">Coverage</th>
                                <th class="px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wide text-slate-500">Bulk Action</th>
                                <th class="px-4 py-2 text-right text-[10px] font-semibold uppercase tracking-wide text-slate-500">Pick</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse($candidateRows as $row)
                                @php
                                    $activeAssignment = $row['active_assignment'];
                                    $isBlocked = $row['action_mode'] === 'BLOCKED';
                                    $empId = (int) $row['employee']->emp_id;
                                @endphp

                                <tr @class([
                                    'transition hover:bg-slate-50',
                                    'opacity-70' => $isBlocked,
                                ])>
                                    <td class="px-4 py-3 align-top">
                                        <div class="font-medium text-slate-900">{{ $row['employee']->full_name }}</div>
                                        <div class="mt-0.5 text-xs text-slate-500">
                                            {{ $row['employee']->emp_code }} · {{ $row['employment_type_name'] }}
                                        </div>

                                        @if($activeAssignment)
                                            <div class="mt-1.5 text-[11px] text-slate-500">
                                                Current: {{ $activeAssignment->shift->shift_name ?? '—' }}
                                            </div>
                                        @elseif($row['latest_assignment'])
                                            <div class="mt-1.5 text-[11px] text-slate-500">
                                                Latest: {{ $row['latest_assignment']->shift->shift_name ?? '—' }}
                                            </div>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 align-top">
                                        <span
                                            class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1"
                                            :class="statusBadgeClass('{{ $row['coverage_status'] }}')"
                                            x-text="statusBadgeLabel('{{ $row['coverage_status'] }}')"
                                        ></span>

                                        <div class="mt-1.5 text-[11px] leading-5 text-slate-500">
                                            {{ $row['coverage_note'] }}
                                        </div>
                                    </td>

                                    <td class="px-4 py-3 align-top">
                                        <span
                                            class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1"
                                            :class="actionBadgeClass('{{ $row['action_mode'] }}')"
                                            x-text="actionBadgeLabel('{{ $row['action_mode'] }}')"
                                        ></span>
                                    </td>

                                    <td class="px-4 py-3 text-right align-top">
                                        @if($isBlocked)
                                            <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-500">
                                                Blocked
                                            </span>
                                        @else
                                            <button
                                                type="button"
                                                class="inline-flex min-w-[94px] items-center justify-center rounded-2xl border px-4 py-2 text-sm font-medium transition"
                                                :class="hasEmployee({{ $empId }})
                                                    ? 'border-emerald-300 bg-emerald-100 text-emerald-700 hover:bg-emerald-100'
                                                    : 'border-slate-300 bg-white text-slate-700 hover:border-slate-400 hover:bg-slate-50'"
                                                @click="toggleEmployee({{ $empId }})"
                                            >
                                                <span x-text="hasEmployee({{ $empId }}) ? 'Added' : 'Add'"></span>
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-10">
                                        <x-ui.empty-state
                                            title="No candidates found"
                                            description="Tidak ada kandidat yang cocok dengan filter bulk shift assignment saat ini."
                                        />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </x-ui.table-shell>
            </x-ui.page-section>

            <x-ui.page-section
                title="Basket"
                subtitle="Employee yang dipilih akan diproses saat submit."
            >
                <div class="grid gap-3 md:grid-cols-3 xl:grid-cols-1">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5">
                        <div class="text-[10px] uppercase tracking-wide text-slate-500">Selected</div>
                        <div class="mt-0.5 text-base font-semibold text-slate-900" x-text="selectedEmployees.length"></div>
                    </div>

                    <div class="rounded-xl border border-sky-200 bg-sky-50 px-3 py-2.5">
                        <div class="text-[10px] uppercase tracking-wide text-sky-700">Create</div>
                        <div class="mt-0.5 text-base font-semibold text-sky-800" x-text="createCount()"></div>
                    </div>

                    <div class="rounded-xl border border-violet-200 bg-violet-50 px-3 py-2.5">
                        <div class="text-[10px] uppercase tracking-wide text-violet-700">Replace</div>
                        <div class="mt-0.5 text-base font-semibold text-violet-800" x-text="replaceCount()"></div>
                    </div>
                </div>

                <div class="mt-4 space-y-4">
                    <x-ui.field label="Target Shift" :error="$errors->first('shift_id')">
                        <select
                            name="shift_id"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800"
                        >
                            <option value="">Select shift</option>
                            @foreach($shifts as $shift)
                                <option value="{{ $shift->shift_id }}" @selected((string) old('shift_id') === (string) $shift->shift_id)>
                                    {{ $shift->shift_name }} — {{ $shift->shift_code }}
                                </option>
                            @endforeach
                        </select>
                    </x-ui.field>

                    <x-ui.field label="Assignment Type" :error="$errors->first('assignment_type_code')">
                        <select
                            name="assignment_type_code"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800"
                        >
                            <option value="">Select type</option>
                            @foreach($assignmentTypes as $type)
                                <option value="{{ $type->assignment_type_code }}" @selected(old('assignment_type_code') === $type->assignment_type_code)>
                                    {{ $type->assignment_type_name }}
                                </option>
                            @endforeach
                        </select>
                    </x-ui.field>

                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-1">
                        <x-ui.field label="Effective Start Date" :error="$errors->first('effective_start_date')">
                            <input
                                type="date"
                                name="effective_start_date"
                                value="{{ old('effective_start_date', $referenceDate) }}"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800"
                            >
                        </x-ui.field>

                        <x-ui.field label="Effective End Date" :error="$errors->first('effective_end_date')">
                            <input
                                type="date"
                                name="effective_end_date"
                                value="{{ old('effective_end_date') }}"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800"
                            >
                            <div class="mt-1 text-[11px] text-slate-500">
                                Kosongkan jika assignment baru ingin dibiarkan open end.
                            </div>
                        </x-ui.field>
                    </div>

                    <x-ui.field label="Notes" :error="$errors->first('notes')">
                        <textarea
                            name="notes"
                            rows="3"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800"
                        >{{ old('notes') }}</textarea>
                    </x-ui.field>
                </div>

                <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <div class="text-xs font-medium text-slate-700">Preview Dampak</div>
                    <div class="mt-1 text-[11px] leading-5 text-slate-500">
                        Employee dengan status <strong>Create New</strong> akan dibuatkan shift assignment baru.
                        Employee dengan status <strong>Replace Active</strong> akan mengakhiri shift aktif di H-1, lalu dibuatkan assignment baru mulai tanggal efektif.
                        Item <strong>Blocked</strong> tidak dapat masuk basket.
                    </div>
                </div>

                <div class="mt-4 flex items-center justify-between gap-3">
                    <x-ui.button type="button" variant="ghost" @click="clearAll()">
                        Clear Basket
                    </x-ui.button>

                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-medium text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="submitting"
                    >
                        <span x-show="!submitting">Submit Bulk Assignment</span>
                        <span x-show="submitting" style="display:none;">Submitting...</span>
                    </button>
                </div>

                <div class="mt-4 space-y-2">
                    <template x-if="selectedEmployees.length === 0">
                        <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-3 py-4 text-center text-sm text-slate-500">
                            Basket masih kosong. Tambahkan candidate dari tabel di sebelah kiri.
                        </div>
                    </template>

                    <template x-for="employee in selectedEmployees" :key="employee.emp_id">
                        <div class="rounded-xl border border-slate-200 bg-white px-3 py-3 shadow-sm">
                            <input type="hidden" name="employee_ids[]" :value="employee.emp_id">

                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="text-sm font-medium text-slate-900" x-text="employee.full_name"></div>
                                    <div class="mt-1 text-xs text-slate-500">
                                        <span x-text="employee.emp_code"></span>
                                        <span> · </span>
                                        <span x-text="employee.employment_type_name"></span>
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    class="text-xs font-medium text-rose-600 hover:text-rose-700"
                                    @click="removeEmployee(employee.emp_id)"
                                >
                                    Remove
                                </button>
                            </div>

                            <div class="mt-2 flex flex-wrap gap-2">
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1"
                                    :class="statusBadgeClass(employee.coverage_status)"
                                    x-text="statusBadgeLabel(employee.coverage_status)"
                                ></span>

                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1"
                                    :class="actionBadgeClass(employee.action_mode)"
                                    x-text="actionBadgeLabel(employee.action_mode)"
                                ></span>
                            </div>

                            <div class="mt-2 text-[11px] leading-5 text-slate-500" x-text="employee.coverage_note"></div>
                        </div>
                    </template>
                </div>
            </x-ui.page-section>
        </div>
    </form>
</div>

<script>
    function bulkShiftAssignmentBasket({ storageKey, visibleCandidates = [], initialSelected = [] }) {
        return {
            storageKey,
            visibleCandidates,
            selectedEmployees: [],
            submitting: false,
            candidateMap: {},

            init() {
                this.candidateMap = this.buildCandidateMap(this.visibleCandidates);

                const saved = this.loadFromStorage();

                if (Array.isArray(saved) && saved.length > 0) {
                    this.selectedEmployees = saved;
                } else if (Array.isArray(initialSelected) && initialSelected.length > 0) {
                    this.selectedEmployees = initialSelected;
                    this.saveToStorage();
                }

                this.$watch('selectedEmployees', () => {
                    this.saveToStorage();
                });
            },

            buildCandidateMap(candidates) {
                const map = {};

                (candidates || []).forEach(candidate => {
                    map[String(candidate.emp_id)] = { ...candidate };
                });

                return map;
            },

            loadFromStorage() {
                try {
                    const raw = sessionStorage.getItem(this.storageKey);
                    if (!raw) return [];

                    const parsed = JSON.parse(raw);

                    return Array.isArray(parsed) ? parsed : [];
                } catch (e) {
                    return [];
                }
            },

            saveToStorage() {
                try {
                    sessionStorage.setItem(this.storageKey, JSON.stringify(this.selectedEmployees));
                } catch (e) {}
            },

            clearStorage() {
                try {
                    sessionStorage.removeItem(this.storageKey);
                } catch (e) {}
            },

            hasEmployee(empId) {
                return this.selectedEmployees.some(item => Number(item.emp_id) === Number(empId));
            },

            getCandidate(empId) {
                return this.candidateMap[String(empId)] ?? null;
            },

            addEmployee(employee) {
                if (!employee || employee.action_mode === 'BLOCKED') return;
                if (this.hasEmployee(employee.emp_id)) return;

                this.selectedEmployees.push({ ...employee });
            },

            removeEmployee(empId) {
                this.selectedEmployees = this.selectedEmployees.filter(
                    item => Number(item.emp_id) !== Number(empId)
                );
            },

            toggleEmployee(empId) {
                if (this.hasEmployee(empId)) {
                    this.removeEmployee(empId);
                    return;
                }

                const candidate = this.getCandidate(empId);

                if (!candidate) return;

                this.addEmployee(candidate);
            },

            clearAll() {
                this.selectedEmployees = [];
                this.clearStorage();
            },

            addAllVisible() {
                this.visibleCandidates.forEach(candidate => {
                    if (candidate.action_mode === 'BLOCKED') return;
                    if (!this.hasEmployee(candidate.emp_id)) {
                        this.selectedEmployees.push({ ...candidate });
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
                        return 'No Active Shift';
                    case 'HAS_ACTIVE_ASSIGNMENT':
                        return 'Has Active Shift';
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