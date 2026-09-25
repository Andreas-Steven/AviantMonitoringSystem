<?php

namespace App\Http\Requests\Scheduling;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeWorkPatternAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasPermission('workpattern.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'emp_id' => ['required', 'integer', 'exists:employees,emp_id'],
            'work_pattern_id' => ['required', 'integer', 'exists:work_patterns,work_pattern_id'],
            'effective_start_date' => ['required', 'date'],
            'effective_end_date' => ['nullable', 'date', 'after_or_equal:effective_start_date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}