<?php

namespace App\Http\Requests\Scheduling;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttendancePolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasPermission('policy.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'policy_code' => ['required', 'string', 'max:255', 'unique:attendance_policies,policy_code'],
            'policy_name' => ['required', 'string', 'max:255'],
            'late_grace_in_min' => ['required', 'integer', 'min:0'],
            'early_out_grace_min' => ['required', 'integer', 'min:0'],
            'min_work_min_half_day' => ['required', 'integer', 'min:0'],
            'min_work_min_full_day' => ['required', 'integer', 'min:0'],
            'overtime_min_before' => ['required', 'integer', 'min:0'],
            'overtime_rounding_mode_code' => ['required', 'string', 'exists:overtime_rounding_modes,overtime_rounding_mode_code'],
            'overtime_rounding_unit_min' => ['required', 'integer', 'min:0'],
            'double_tap_window_min' => ['required', 'integer', 'min:0'],
            'max_pair_gap_hour' => ['required', 'integer', 'min:0'],
            'missing_out_policy_code' => ['required', 'string', 'exists:missing_attendance_policies,missing_attendance_policy_code'],
            'missing_in_policy_code' => ['required', 'string', 'exists:missing_attendance_policies,missing_attendance_policy_code'],
            'active' => ['required', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }
}