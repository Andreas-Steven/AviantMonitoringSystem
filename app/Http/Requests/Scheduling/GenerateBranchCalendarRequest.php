<?php

namespace App\Http\Requests\Scheduling;

use Illuminate\Foundation\Http\FormRequest;

class GenerateBranchCalendarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasPermission('calendar.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'integer', 'exists:branches,branch_id'],
            'payroll_period_id' => ['required', 'integer', 'exists:payroll_periods,payroll_period_id'],
            'period_code' => ['required', 'string', 'exists:payroll_periods,period_code'],
            'overwrite_existing' => ['nullable', 'boolean'],
            'view_mode' => ['nullable', 'in:grid,list'],
        ];
    }
}