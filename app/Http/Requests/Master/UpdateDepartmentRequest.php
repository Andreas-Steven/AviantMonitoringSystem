<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasPermission('department.manage') ?? false;
    }

    public function rules(): array
    {
        $departmentId = (int) $this->route('department');

        return [
            'dept_code' => ['required', 'string', 'max:50', Rule::unique('departments', 'dept_code')->ignore($departmentId, 'dept_id')],
            'dept_name' => ['required', 'string', 'max:255', Rule::unique('departments', 'dept_name')->ignore($departmentId, 'dept_id')],
            'active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
