<?php

namespace App\Http\Requests\Scheduling;

use Illuminate\Foundation\Http\FormRequest;

class StorePayrollPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasPermission('payroll_period.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'period_code' => ['required', 'string', 'max:255', 'unique:payroll_periods,period_code'],
            'period_start_date' => ['required', 'date'],
            'period_end_date' => ['required', 'date', 'after_or_equal:period_start_date'],
            'payroll_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'payroll_month' => ['required', 'integer', 'between:1,12'],
            'payroll_period_status_code' => ['required', 'string', 'exists:payroll_period_statuses,payroll_period_status_code'],
            'notes' => ['nullable', 'string'],
        ];
    }
}