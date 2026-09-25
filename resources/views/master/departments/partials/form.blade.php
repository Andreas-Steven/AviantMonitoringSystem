<div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Department Code</label>
            <input type="text" name="dept_code" value="{{ old('dept_code', $department->dept_code ?? '') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
            @error('dept_code') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Department Name</label>
            <input type="text" name="dept_name" value="{{ old('dept_name', $department->dept_name ?? '') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
            @error('dept_name') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Status</label>
            <select name="active" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                <option value="1" @selected((string) old('active', $department->active ?? '1') === '1')>Active</option>
                <option value="0" @selected((string) old('active', $department->active ?? '1') === '0')>Inactive</option>
            </select>
            @error('active') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div class="md:col-span-2">
            <label class="mb-2 block text-sm font-medium text-slate-700">Notes</label>
            <textarea name="notes" rows="4" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">{{ old('notes', $department->notes ?? '') }}</textarea>
            @error('notes') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>
</div>
<div class="flex items-center gap-3">
    <button type="submit" class="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-medium text-white hover:bg-slate-800">Save</button>
    <a href="{{ route('master.departments.index') }}" class="rounded-2xl border border-slate-300 px-5 py-3 text-sm font-medium hover:bg-slate-50">Cancel</a>
</div>
