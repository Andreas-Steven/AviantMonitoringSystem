<?php

namespace App\Http\Requests\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOvertimeRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasPermission('overtime.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'emp_id' => ['required', 'integer', 'exists:employees,emp_id'],
            'work_date' => ['required', 'date'],
            'planned_start_datetime' => ['nullable', 'date'],
            'planned_end_datetime' => ['nullable', 'date', 'after_or_equal:planned_start_datetime'],
            'actual_start_datetime' => ['nullable', 'date'],
            'actual_end_datetime' => ['nullable', 'date', 'after_or_equal:actual_start_datetime'],
            'reason' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ];
    }
}