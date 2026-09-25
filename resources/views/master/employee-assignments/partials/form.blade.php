<x-ui.section-card
    title="Assignment Information"
    subtitle="Isi organisasi, periode berlaku, dan status primary assignment."
>
    <div class="grid gap-4 md:grid-cols-2">
        @if(isset($assignment) && $assignment?->exists)
            <x-ui.field label="Employee" :error="$errors->first('emp_id')">
                <input type="hidden" name="emp_id" value="{{ old('emp_id', $assignment->emp_id) }}">

                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="text-sm font-medium text-slate-900">
                        {{ $assignment->employee->emp_code ?? '-' }} · {{ $assignment->employee->full_name ?? '-' }}
                    </div>
                    <div class="mt-1 text-xs text-slate-500">
                        Employee pada assignment existing dikunci untuk menjaga konsistensi histori.
                    </div>
                </div>
            </x-ui.field>
        @else
            <x-ui.employee-combobox
                name="emp_id"
                label="Employee"
                :employees="$employees"
                :selected="old('emp_id', $assignment->emp_id ?? ($prefillEmpId ?? null))"
                :error="$errors->first('emp_id')"
                placeholder="Cari employee..."
            />
        @endif

        <x-ui.field label="Branch" :error="$errors->first('branch_id')">
            <select
                name="branch_id"
                class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
            >
                <option value="">Select branch</option>
                @foreach($branches as $branch)
                    <option
                        value="{{ $branch->branch_id }}"
                        @selected((string) old('branch_id', $assignment->branch_id ?? '') === (string) $branch->branch_id)
                    >
                        {{ $branch->branch_name }}
                    </option>
                @endforeach
            </select>
        </x-ui.field>

        <x-ui.field label="Department" :error="$errors->first('dept_id')">
            <select
                name="dept_id"
                class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
            >
                <option value="">Select department</option>
                @foreach($departments as $department)
                    <option
                        value="{{ $department->dept_id }}"
                        @selected((string) old('dept_id', $assignment->dept_id ?? '') === (string) $department->dept_id)
                    >
                        {{ $department->dept_name }}
                    </option>
                @endforeach
            </select>
        </x-ui.field>

        <x-ui.field label="Role" :error="$errors->first('role_id')">
            <select
                name="role_id"
                class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
            >
                <option value="">Select role</option>
                @foreach($roles as $role)
                    <option
                        value="{{ $role->role_id }}"
                        @selected((string) old('role_id', $assignment->role_id ?? '') === (string) $role->role_id)
                    >
                        {{ $role->role_name }} — {{ $role->department->dept_name ?? '-' }}
                    </option>
                @endforeach
            </select>
            <div class="mt-2 text-xs text-slate-500">
                Role harus sesuai dengan department yang dipilih.
            </div>
        </x-ui.field>

        <x-ui.field label="Grade" :error="$errors->first('grade_id')">
            <select
                name="grade_id"
                class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
            >
                <option value="">Select grade</option>
                @foreach($grades as $grade)
                    <option
                        value="{{ $grade->grade_id }}"
                        @selected((string) old('grade_id', $assignment->grade_id ?? '') === (string) $grade->grade_id)
                    >
                        {{ $grade->grade_name }}
                    </option>
                @endforeach
            </select>
        </x-ui.field>

        <x-ui.field label="Primary Assignment" :error="$errors->first('is_primary')">
            <select
                name="is_primary"
                class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
            >
                <option value="1" @selected((string) old('is_primary', $assignment->is_primary ?? '1') === '1')>Yes</option>
                <option value="0" @selected((string) old('is_primary', $assignment->is_primary ?? '1') === '0')>No</option>
            </select>
            <div class="mt-2 text-xs text-slate-500">
                Primary digunakan sebagai assignment utama untuk referensi organisasi employee.
            </div>
        </x-ui.field>
    </div>
</x-ui.section-card>

<x-ui.section-card
    title="Effective Period"
    subtitle="Assignment disimpan sebagai histori, bukan overwrite struktur lama."
>
    <div class="grid gap-4 md:grid-cols-2">
        <x-ui.field label="Effective Start Date" :error="$errors->first('effective_start_date')">
            <input
                type="date"
                name="effective_start_date"
                value="{{ old('effective_start_date', isset($assignment->effective_start_date) ? $assignment->effective_start_date->format('Y-m-d') : '') }}"
                class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
            >
        </x-ui.field>

        <x-ui.field label="Effective End Date" :error="$errors->first('effective_end_date')">
            <input
                type="date"
                name="effective_end_date"
                value="{{ old('effective_end_date', isset($assignment->effective_end_date) ? $assignment->effective_end_date->format('Y-m-d') : '') }}"
                class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
            >
            <div class="mt-2 text-xs text-slate-500">
                Kosongkan jika assignment masih aktif.
            </div>
        </x-ui.field>
    </div>
</x-ui.section-card>

<x-ui.section-card
    title="Notes"
    subtitle="Catatan tambahan bila assignment ini bersifat khusus atau temporer."
>
    <x-ui.field label="Notes" :error="$errors->first('notes')">
        <textarea
            name="notes"
            rows="4"
            class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
        >{{ old('notes', $assignment->notes ?? '') }}</textarea>
    </x-ui.field>
</x-ui.section-card>

<div class="flex items-center justify-end gap-3">
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
        class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-medium text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
        x-bind:disabled="submitting"
    >
        <span x-show="!submitting">Save Assignment</span>
        <span x-show="submitting" style="display: none;">Saving...</span>
    </button>
</div>