<div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="grid gap-6 md:grid-cols-2">
        <div>
            @if(isset($assignment) && $assignment?->exists)
                <x-ui.field label="Employee" :error="$errors->first('emp_id')">
                    <input type="hidden" name="emp_id" value="{{ old('emp_id', $assignment->emp_id) }}">

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <div class="text-sm font-medium text-slate-900">
                            {{ $assignment->employee->emp_code ?? '-' }} · {{ $assignment->employee->full_name ?? '-' }}
                        </div>
                        <div class="mt-1 text-xs text-slate-500">
                            Employee pada work pattern assignment existing dikunci untuk menjaga konsistensi histori.
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
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Work Pattern</label>
            <select name="work_pattern_id" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                <option value="">Select work pattern</option>
                @foreach($workPatterns as $pattern)
                    <option value="{{ $pattern->work_pattern_id }}"
                        @selected((string) old('work_pattern_id', $assignment->work_pattern_id ?? '') === (string) $pattern->work_pattern_id)>
                        {{ $pattern->work_pattern_name }} ({{ $pattern->work_pattern_code }})
                    </option>
                @endforeach
            </select>
            @error('work_pattern_id') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Effective Start Date</label>
            <input type="date" name="effective_start_date"
                value="{{ old('effective_start_date', isset($assignment->effective_start_date) ? $assignment->effective_start_date->format('Y-m-d') : '') }}"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
            @error('effective_start_date') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Effective End Date</label>
            <input type="date" name="effective_end_date"
                value="{{ old('effective_end_date', isset($assignment->effective_end_date) ? $assignment->effective_end_date->format('Y-m-d') : '') }}"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
            @error('effective_end_date') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div class="md:col-span-2">
            <label class="mb-2 block text-sm font-medium text-slate-700">Notes</label>
            <textarea name="notes" rows="4"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">{{ old('notes', $assignment->notes ?? '') }}</textarea>
            @error('notes') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>
</div>

<div class="flex items-center gap-3">
    <button type="submit" class="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-medium text-white hover:bg-slate-800">
        Save
    </button>
    <a href="{{ route('scheduling.employee-work-pattern-assignments.index') }}" class="rounded-2xl border border-slate-300 px-5 py-3 text-sm font-medium hover:bg-slate-50">
        Cancel
    </a>
</div>