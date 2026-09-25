<?php

namespace App\Http\Requests\Scheduling;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkPatternRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasPermission('workpattern.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'work_pattern_id' => ['required', 'integer', 'exists:work_patterns,work_pattern_id'],
            'rule_code' => ['required', 'string', 'max:255'],
            'rule_name' => ['required', 'string', 'max:255'],
            'rule_type_code' => ['required', 'string', 'exists:rule_types,rule_type_code'],
            'day_of_week_code' => ['nullable', 'string', 'exists:day_of_week_codes,day_of_week_code'],
            'shift_id' => ['nullable', 'integer', 'exists:shifts,shift_id'],
            'target_count_per_period' => ['nullable', 'integer', 'min:0'],
            'holiday_wins_flag' => ['required', 'boolean'],
            'holiday_scope_mode_code' => ['nullable', 'string', 'exists:holiday_scope_modes,holiday_scope_mode_code'],
            'substitution_allowed_flag' => ['required', 'boolean'],
            'excess_treatment_mode_code' => ['nullable', 'string', 'exists:excess_treatment_modes,excess_treatment_mode_code'],
            'deficit_treatment_mode_code' => ['nullable', 'string', 'exists:deficit_treatment_modes,deficit_treatment_mode_code'],
            'priority_order' => ['required', 'integer', 'min:1'],
            'active' => ['required', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }
}