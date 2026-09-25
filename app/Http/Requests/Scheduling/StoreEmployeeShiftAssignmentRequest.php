<?php

namespace App\Http\Requests\Scheduling;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeShiftAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasPermission('shift.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'emp_id' => ['required', 'integer', 'exists:employees,emp_id'],
            'shift_id' => ['required', 'integer', 'exists:shifts,shift_id'],
            'assignment_type_code' => ['required', 'string', 'exists:assignment_types,assignment_type_code'],
            'effective_start_date' => ['required', 'date'],
            'effective_end_date' => ['nullable', 'date', 'after_or_equal:effective_start_date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}