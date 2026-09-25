<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasPermission('employee.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'emp_code' => ['required', 'string', 'max:255', 'unique:employees,emp_code'],
            'biometric_code' => ['nullable', 'string', 'max:255', 'unique:employees,biometric_code'],
            'full_name' => ['required', 'string', 'max:255'],
            'employment_type_id' => ['required', 'integer', 'exists:employment_types,employment_type_id'],
            'active' => ['required', 'boolean'],
            'join_date' => ['required', 'date'],
            'resign_date' => ['nullable', 'date', 'after_or_equal:join_date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}