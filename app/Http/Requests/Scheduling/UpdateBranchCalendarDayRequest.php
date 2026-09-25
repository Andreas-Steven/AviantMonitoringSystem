<?php

namespace App\Http\Requests\Scheduling;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBranchCalendarDayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasPermission('calendar.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'period_code' => ['required', 'string', 'exists:payroll_periods,period_code'],
            'view_mode' => ['nullable', 'in:grid,list'],
            'day_type_code' => ['required', 'string', 'exists:day_types,day_type_code'],
            'is_workday' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}