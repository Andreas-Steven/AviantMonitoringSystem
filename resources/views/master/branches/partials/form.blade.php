<div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Branch Code</label>
            <input type="text" name="branch_code"
                value="{{ old('branch_code', $branch->branch_code ?? '') }}"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
            @error('branch_code') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Branch Name</label>
            <input type="text" name="branch_name"
                value="{{ old('branch_name', $branch->branch_name ?? '') }}"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
            @error('branch_name') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Branch Type</label>
            <select name="branch_type_code" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                <option value="">Select branch type</option>
                @foreach($branchTypes as $type)
                    <option value="{{ $type->branch_type_code }}"
                        @selected(old('branch_type_code', $branch->branch_type_code ?? '') === $type->branch_type_code)>
                        {{ $type->branch_type_name }}
                    </option>
                @endforeach
            </select>
            @error('branch_type_code') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Status</label>
            <select name="active" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                <option value="1" @selected((string) old('active', $branch->active ?? '1') === '1')>Active</option>
                <option value="0" @selected((string) old('active', $branch->active ?? '1') === '0')>Inactive</option>
            </select>
            @error('active') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Opened Date</label>
            <input type="date" name="opened_date"
                value="{{ old('opened_date', isset($branch->opened_date) ? $branch->opened_date->format('Y-m-d') : '') }}"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
            @error('opened_date') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Closed Date</label>
            <input type="date" name="closed_date"
                value="{{ old('closed_date', isset($branch->closed_date) ? $branch->closed_date->format('Y-m-d') : '') }}"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
            @error('closed_date') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div class="md:col-span-2">
            <label class="mb-2 block text-sm font-medium text-slate-700">Notes</label>
            <textarea name="notes" rows="4"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">{{ old('notes', $branch->notes ?? '') }}</textarea>
            @error('notes') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>
</div>

<div class="flex items-center gap-3">
    <button type="submit" class="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-medium text-white hover:bg-slate-800">
        Save
    </button>
    <a href="{{ route('master.branches.index') }}" class="rounded-2xl border border-slate-300 px-5 py-3 text-sm font-medium hover:bg-slate-50">
        Cancel
    </a>
</div>