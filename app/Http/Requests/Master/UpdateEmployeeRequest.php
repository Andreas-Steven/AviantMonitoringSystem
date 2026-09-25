<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasPermission('employee.manage') ?? false;
    }

    public function rules(): array
    {
        $employeeId = $this->route('employee');

        return [
            'emp_code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('employees', 'emp_code')->ignore($employeeId, 'emp_id'),
            ],
            'biometric_code' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('employees', 'biometric_code')->ignore($employeeId, 'emp_id'),
            ],
            'full_name' => ['required', 'string', 'max:255'],
            'employment_type_id' => ['required', 'integer', 'exists:employment_types,employment_type_id'],
            'active' => ['required', 'boolean'],
            'join_date' => ['required', 'date'],
            'resign_date' => ['nullable', 'date', 'after_or_equal:join_date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}