<div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Employee</label>
            <select name="emp_id" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                <option value="">Select employee</option>
                @foreach($employees as $employee)
                    <option value="{{ $employee->emp_id }}"
                        @selected((string) old('emp_id', $roster->emp_id ?? '') === (string) $employee->emp_id)>
                        {{ $employee->full_name }} ({{ $employee->emp_code }})
                    </option>
                @endforeach
            </select>
            @error('emp_id') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Work Date</label>
            <input type="date" name="work_date"
                value="{{ old('work_date', isset($roster->work_date) ? $roster->work_date->format('Y-m-d') : '') }}"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
            @error('work_date') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Shift</label>
            <select name="shift_id" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                <option value="">Select shift</option>
                @foreach($shifts as $shift)
                    <option value="{{ $shift->shift_id }}"
                        @selected((string) old('shift_id', $roster->shift_id ?? '') === (string) $shift->shift_id)>
                        {{ $shift->shift_name }} ({{ $shift->shift_code }})
                    </option>
                @endforeach
            </select>
            @error('shift_id') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Source Type</label>
            <select name="source_type_code" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                <option value="">Select source type</option>
                @foreach($sourceTypes as $type)
                    <option value="{{ $type->source_type_code }}"
                        @selected(old('source_type_code', $roster->source_type_code ?? '') === $type->source_type_code)>
                        {{ $type->source_type_name }}
                    </option>
                @endforeach
            </select>
            @error('source_type_code') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Source Ref ID</label>
            <input type="text" name="source_ref_id"
                value="{{ old('source_ref_id', $roster->source_ref_id ?? '') }}"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
            @error('source_ref_id') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Published At</label>
            <input type="datetime-local" name="published_at"
                value="{{ old('published_at', isset($roster->published_at) ? $roster->published_at->format('Y-m-d\TH:i') : '') }}"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
            @error('published_at') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div class="md:col-span-2">
            <label class="mb-2 block text-sm font-medium text-slate-700">Notes</label>
            <textarea name="notes" rows="4"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">{{ old('notes', $roster->notes ?? '') }}</textarea>
            @error('notes') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>
</div>

<div class="flex items-center gap-3">
    <button type="submit" class="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-medium text-white hover:bg-slate-800">
        Save
    </button>
    <a href="{{ route('scheduling.employee-shift-rosters.index') }}" class="rounded-2xl border border-slate-300 px-5 py-3 text-sm font-medium hover:bg-slate-50">
        Cancel
    </a>
</div>