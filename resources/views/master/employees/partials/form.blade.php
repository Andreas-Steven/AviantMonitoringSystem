<div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Employee Code</label>
            <input type="text" name="emp_code"
                value="{{ old('emp_code', $employee->emp_code ?? '') }}"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
            @error('emp_code') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Biometric Code</label>
            <input type="text" name="biometric_code"
                value="{{ old('biometric_code', $employee->biometric_code ?? '') }}"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
            @error('biometric_code') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div class="md:col-span-2">
            <label class="mb-2 block text-sm font-medium text-slate-700">Full Name</label>
            <input type="text" name="full_name"
                value="{{ old('full_name', $employee->full_name ?? '') }}"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
            @error('full_name') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Employment Type</label>
            <select name="employment_type_id" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                <option value="">Select employment type</option>
                @foreach($employmentTypes as $type)
                    <option value="{{ $type->employment_type_id }}"
                        @selected((string) old('employment_type_id', $employee->employment_type_id ?? '') === (string) $type->employment_type_id)>
                        {{ $type->employment_type_name }}
                    </option>
                @endforeach
            </select>
            @error('employment_type_id') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Status</label>
            <select name="active" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                <option value="1" @selected((string) old('active', $employee->active ?? '1') === '1')>Active</option>
                <option value="0" @selected((string) old('active', $employee->active ?? '1') === '0')>Inactive</option>
            </select>
            @error('active') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Join Date</label>
            <input type="date" name="join_date"
                value="{{ old('join_date', isset($employee->join_date) ? $employee->join_date->format('Y-m-d') : '') }}"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
            @error('join_date') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Resign Date</label>
            <input type="date" name="resign_date"
                value="{{ old('resign_date', isset($employee->resign_date) ? $employee->resign_date->format('Y-m-d') : '') }}"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
            @error('resign_date') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div class="md:col-span-2">
            <label class="mb-2 block text-sm font-medium text-slate-700">Notes</label>
            <textarea name="notes" rows="4"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">{{ old('notes', $employee->notes ?? '') }}</textarea>
            @error('notes') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>
</div>

<div class="flex items-center gap-3">
    <button type="submit" class="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-medium text-white hover:bg-slate-800">
        Save
    </button>
    <a href="{{ route('master.employees.index') }}" class="rounded-2xl border border-slate-300 px-5 py-3 text-sm font-medium hover:bg-slate-50">
        Cancel
    </a>
</div>