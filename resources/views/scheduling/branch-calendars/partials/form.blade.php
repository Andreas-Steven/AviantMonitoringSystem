<div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Branch</label>
            <select name="branch_id" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                <option value="">Select branch</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->branch_id }}"
                        @selected((string) old('branch_id', $calendar->branch_id ?? '') === (string) $branch->branch_id)>
                        {{ $branch->branch_name }}
                    </option>
                @endforeach
            </select>
            @error('branch_id') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Work Date</label>
            <input type="date" name="work_date"
                value="{{ old('work_date', isset($calendar->work_date) ? $calendar->work_date->format('Y-m-d') : '') }}"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
            @error('work_date') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Day Type</label>
            <select name="day_type_code" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                <option value="">Select day type</option>
                @foreach($dayTypes as $dayType)
                    <option value="{{ $dayType->day_type_code }}"
                        @selected(old('day_type_code', $calendar->day_type_code ?? '') === $dayType->day_type_code)>
                        {{ $dayType->day_type_name }}
                    </option>
                @endforeach
            </select>
            @error('day_type_code') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Day Name</label>
            <input type="text" name="day_name"
                value="{{ old('day_name', $calendar->day_name ?? '') }}"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
            @error('day_name') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Is Workday</label>
            <select name="is_workday" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                <option value="1" @selected((string) old('is_workday', $calendar->is_workday ?? '1') === '1')>Yes</option>
                <option value="0" @selected((string) old('is_workday', $calendar->is_workday ?? '1') === '0')>No</option>
            </select>
            @error('is_workday') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div class="md:col-span-2">
            <label class="mb-2 block text-sm font-medium text-slate-700">Notes</label>
            <textarea name="notes" rows="4"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">{{ old('notes', $calendar->notes ?? '') }}</textarea>
            @error('notes') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>
</div>

<div class="flex items-center gap-3">
    <button type="submit" class="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-medium text-white hover:bg-slate-800">
        Save
    </button>
    <a href="{{ route('scheduling.branch-calendars.index') }}" class="rounded-2xl border border-slate-300 px-5 py-3 text-sm font-medium hover:bg-slate-50">
        Cancel
    </a>
</div>