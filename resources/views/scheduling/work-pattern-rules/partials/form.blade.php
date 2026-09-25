<div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Work Pattern</label>
            <select name="work_pattern_id" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                <option value="">Select work pattern</option>
                @foreach($workPatterns as $pattern)
                    <option value="{{ $pattern->work_pattern_id }}"
                        @selected((string) old('work_pattern_id', $rule->work_pattern_id ?? '') === (string) $pattern->work_pattern_id)>
                        {{ $pattern->work_pattern_name }}
                    </option>
                @endforeach
            </select>
            @error('work_pattern_id') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Rule Type</label>
            <select name="rule_type_code" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                <option value="">Select rule type</option>
                @foreach($ruleTypes as $type)
                    <option value="{{ $type->rule_type_code }}"
                        @selected(old('rule_type_code', $rule->rule_type_code ?? '') === $type->rule_type_code)>
                        {{ $type->rule_type_name }}
                    </option>
                @endforeach
            </select>
            @error('rule_type_code') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Rule Code</label>
            <input type="text" name="rule_code"
                value="{{ old('rule_code', $rule->rule_code ?? '') }}"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
            @error('rule_code') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Rule Name</label>
            <input type="text" name="rule_name"
                value="{{ old('rule_name', $rule->rule_name ?? '') }}"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
            @error('rule_name') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Day of Week</label>
            <select name="day_of_week_code" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                <option value="">-</option>
                @foreach($dayOfWeeks as $day)
                    <option value="{{ $day->day_of_week_code }}"
                        @selected(old('day_of_week_code', $rule->day_of_week_code ?? '') === $day->day_of_week_code)>
                        {{ $day->day_of_week_name }}
                    </option>
                @endforeach
            </select>
            @error('day_of_week_code') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Shift</label>
            <select name="shift_id" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                <option value="">-</option>
                @foreach($shifts as $shift)
                    <option value="{{ $shift->shift_id }}"
                        @selected((string) old('shift_id', $rule->shift_id ?? '') === (string) $shift->shift_id)>
                        {{ $shift->shift_name }}
                    </option>
                @endforeach
            </select>
            @error('shift_id') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Target Count / Period</label>
            <input type="number" min="0" name="target_count_per_period"
                value="{{ old('target_count_per_period', $rule->target_count_per_period ?? '') }}"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
            @error('target_count_per_period') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Priority Order</label>
            <input type="number" min="1" name="priority_order"
                value="{{ old('priority_order', $rule->priority_order ?? 1) }}"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
            @error('priority_order') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Holiday Wins</label>
            <select name="holiday_wins_flag" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                <option value="0" @selected((string) old('holiday_wins_flag', $rule->holiday_wins_flag ?? '0') === '0')>No</option>
                <option value="1" @selected((string) old('holiday_wins_flag', $rule->holiday_wins_flag ?? '0') === '1')>Yes</option>
            </select>
            @error('holiday_wins_flag') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Holiday Scope Mode</label>
            <select name="holiday_scope_mode_code" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                <option value="">-</option>
                @foreach($holidayScopeModes as $mode)
                    <option value="{{ $mode->holiday_scope_mode_code }}"
                        @selected(old('holiday_scope_mode_code', $rule->holiday_scope_mode_code ?? '') === $mode->holiday_scope_mode_code)>
                        {{ $mode->holiday_scope_mode_name }}
                    </option>
                @endforeach
            </select>
            @error('holiday_scope_mode_code') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Substitution Allowed</label>
            <select name="substitution_allowed_flag" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                <option value="0" @selected((string) old('substitution_allowed_flag', $rule->substitution_allowed_flag ?? '0') === '0')>No</option>
                <option value="1" @selected((string) old('substitution_allowed_flag', $rule->substitution_allowed_flag ?? '0') === '1')>Yes</option>
            </select>
            @error('substitution_allowed_flag') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Excess Treatment</label>
            <select name="excess_treatment_mode_code" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                <option value="">-</option>
                @foreach($excessTreatmentModes as $mode)
                    <option value="{{ $mode->excess_treatment_mode_code }}"
                        @selected(old('excess_treatment_mode_code', $rule->excess_treatment_mode_code ?? '') === $mode->excess_treatment_mode_code)>
                        {{ $mode->excess_treatment_mode_name }}
                    </option>
                @endforeach
            </select>
            @error('excess_treatment_mode_code') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Deficit Treatment</label>
            <select name="deficit_treatment_mode_code" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                <option value="">-</option>
                @foreach($deficitTreatmentModes as $mode)
                    <option value="{{ $mode->deficit_treatment_mode_code }}"
                        @selected(old('deficit_treatment_mode_code', $rule->deficit_treatment_mode_code ?? '') === $mode->deficit_treatment_mode_code)>
                        {{ $mode->deficit_treatment_mode_name }}
                    </option>
                @endforeach
            </select>
            @error('deficit_treatment_mode_code') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Status</label>
            <select name="active" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                <option value="1" @selected((string) old('active', $rule->active ?? '1') === '1')>Active</option>
                <option value="0" @selected((string) old('active', $rule->active ?? '1') === '0')>Inactive</option>
            </select>
            @error('active') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div class="md:col-span-2">
            <label class="mb-2 block text-sm font-medium text-slate-700">Notes</label>
            <textarea name="notes" rows="4"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">{{ old('notes', $rule->notes ?? '') }}</textarea>
            @error('notes') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>
</div>

<div class="flex items-center gap-3">
    <button type="submit" class="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-medium text-white hover:bg-slate-800">
        Save
    </button>
    <a href="{{ route('scheduling.work-pattern-rules.index') }}" class="rounded-2xl border border-slate-300 px-5 py-3 text-sm font-medium hover:bg-slate-50">
        Cancel
    </a>
</div>