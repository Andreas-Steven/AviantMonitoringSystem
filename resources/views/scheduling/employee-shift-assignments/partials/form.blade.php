@php
    $selectedEmpId = old('emp_id', $assignment->emp_id ?? $prefillEmpId ?? null);
@endphp

<x-ui.section-card
    title="Assignment Information"
    subtitle="Pilih employee, shift, dan tipe assignment yang ingin diberlakukan."
>
    <div class="grid gap-4 md:grid-cols-2">
        <x-ui.employee-combobox
            name="emp_id"
            label="Employee"
            :employees="$employees"
            :selected="$selectedEmpId"
            :error="$errors->first('emp_id')"
            placeholder="Cari employee..."
        />

        <x-ui.field label="Shift" :error="$errors->first('shift_id')">
            <select
                name="shift_id"
                class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
            >
                <option value="">Select shift</option>
                @foreach($shifts as $shift)
                    <option
                        value="{{ $shift->shift_id }}"
                        @selected((string) old('shift_id', $assignment->shift_id ?? '') === (string) $shift->shift_id)
                    >
                        {{ $shift->shift_name }} — {{ $shift->shift_code }}
                    </option>
                @endforeach
            </select>
        </x-ui.field>

        <x-ui.field label="Assignment Type" :error="$errors->first('assignment_type_code')">
            <select
                name="assignment_type_code"
                class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
            >
                <option value="">Select type</option>
                @foreach($assignmentTypes as $type)
                    <option
                        value="{{ $type->assignment_type_code }}"
                        @selected(old('assignment_type_code', $assignment->assignment_type_code ?? '') === $type->assignment_type_code)
                    >
                        {{ $type->assignment_type_name }}
                    </option>
                @endforeach
            </select>
            <div class="mt-2 text-xs text-slate-500">
                Gunakan type untuk membedakan assignment default, temporary, rotation, atau replacement.
            </div>
        </x-ui.field>
    </div>
</x-ui.section-card>

<x-ui.section-card
    title="Effective Period"
    subtitle="Assignment shift disimpan sebagai histori per periode aktif."
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
    subtitle="Tambahkan catatan jika assignment ini bersifat khusus atau sementara."
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
        onclick="window.location='{{ route('scheduling.employee-shift-assignments.index') }}'"
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