<div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Policy Code</label>
            <input type="text" name="policy_code"
                value="{{ old('policy_code', $policy->policy_code ?? '') }}"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
            @error('policy_code') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Policy Name</label>
            <input type="text" name="policy_name"
                value="{{ old('policy_name', $policy->policy_name ?? '') }}"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
            @error('policy_name') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Late Grace In (min)</label>
            <input type="number" min="0" name="late_grace_in_min"
                value="{{ old('late_grace_in_min', $policy->late_grace_in_min ?? 0) }}"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
            @error('late_grace_in_min') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Early Out Grace (min)</label>
            <input type="number" min="0" name="early_out_grace_min"
                value="{{ old('early_out_grace_min', $policy->early_out_grace_min ?? 0) }}"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
            @error('early_out_grace_min') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Min Work Half Day (min)</label>
            <input type="number" min="0" name="min_work_min_half_day"
                value="{{ old('min_work_min_half_day', $policy->min_work_min_half_day ?? 0) }}"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
            @error('min_work_min_half_day') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Min Work Full Day (min)</label>
            <input type="number" min="0" name="min_work_min_full_day"
                value="{{ old('min_work_min_full_day', $policy->min_work_min_full_day ?? 0) }}"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
            @error('min_work_min_full_day') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Overtime Before (min)</label>
            <input type="number" min="0" name="overtime_min_before"
                value="{{ old('overtime_min_before', $policy->overtime_min_before ?? 0) }}"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
            @error('overtime_min_before') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Overtime Rounding Mode</label>
            <select name="overtime_rounding_mode_code" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                <option value="">Select rounding mode</option>
                @foreach($roundingModes as $mode)
                    <option value="{{ $mode->overtime_rounding_mode_code }}"
                        @selected(old('overtime_rounding_mode_code', $policy->overtime_rounding_mode_code ?? '') === $mode->overtime_rounding_mode_code)>
                        {{ $mode->overtime_rounding_mode_name }}
                    </option>
                @endforeach
            </select>
            @error('overtime_rounding_mode_code') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Overtime Rounding Unit (min)</label>
            <input type="number" min="0" name="overtime_rounding_unit_min"
                value="{{ old('overtime_rounding_unit_min', $policy->overtime_rounding_unit_min ?? 0) }}"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
            @error('overtime_rounding_unit_min') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Double Tap Window (min)</label>
            <input type="number" min="0" name="double_tap_window_min"
                value="{{ old('double_tap_window_min', $policy->double_tap_window_min ?? 0) }}"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
            @error('double_tap_window_min') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Max Pair Gap Hour</label>
            <input type="number" min="0" name="max_pair_gap_hour"
                value="{{ old('max_pair_gap_hour', $policy->max_pair_gap_hour ?? 0) }}"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
            @error('max_pair_gap_hour') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Missing In Policy</label>
            <select name="missing_in_policy_code" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                <option value="">Select missing in policy</option>
                @foreach($missingPolicies as $item)
                    <option value="{{ $item->missing_attendance_policy_code }}"
                        @selected(old('missing_in_policy_code', $policy->missing_in_policy_code ?? '') === $item->missing_attendance_policy_code)>
                        {{ $item->missing_attendance_policy_name }}
                    </option>
                @endforeach
            </select>
            @error('missing_in_policy_code') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Missing Out Policy</label>
            <select name="missing_out_policy_code" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                <option value="">Select missing out policy</option>
                @foreach($missingPolicies as $item)
                    <option value="{{ $item->missing_attendance_policy_code }}"
                        @selected(old('missing_out_policy_code', $policy->missing_out_policy_code ?? '') === $item->missing_attendance_policy_code)>
                        {{ $item->missing_attendance_policy_name }}
                    </option>
                @endforeach
            </select>
            @error('missing_out_policy_code') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Status</label>
            <select name="active" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                <option value="1" @selected((string) old('active', $policy->active ?? '1') === '1')>Active</option>
                <option value="0" @selected((string) old('active', $policy->active ?? '1') === '0')>Inactive</option>
            </select>
            @error('active') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div class="md:col-span-2">
            <label class="mb-2 block text-sm font-medium text-slate-700">Notes</label>
            <textarea name="notes" rows="4"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">{{ old('notes', $policy->notes ?? '') }}</textarea>
            @error('notes') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>
</div>

<div class="flex items-center gap-3">
    <button type="submit" class="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-medium text-white hover:bg-slate-800">
        Save
    </button>
    <a href="{{ route('scheduling.attendance-policies.index') }}" class="rounded-2xl border border-slate-300 px-5 py-3 text-sm font-medium hover:bg-slate-50">
        Cancel
    </a>
</div>