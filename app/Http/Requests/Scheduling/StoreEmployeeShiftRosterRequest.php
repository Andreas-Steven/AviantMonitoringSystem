<?php

namespace App\Http\Requests\Scheduling;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeShiftRosterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasPermission('roster.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'emp_id' => ['required', 'integer', 'exists:employees,emp_id'],
            'work_date' => [
                    'required',
                    'date',
                    Rule::unique('employee_shift_rosters')
                        ->where(fn ($q) => $q->where('emp_id', $this->emp_id))
                ],
            'shift_id' => ['required', 'integer', 'exists:shifts,shift_id'],
            'source_type_code' => ['required', 'string', 'exists:source_types,source_type_code'],
            'source_ref_id' => ['nullable', 'string', 'max:255'],
            'published_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}