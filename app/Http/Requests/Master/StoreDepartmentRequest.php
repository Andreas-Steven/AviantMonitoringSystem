<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasPermission('department.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'dept_code' => ['required', 'string', 'max:50', Rule::unique('departments', 'dept_code')],
            'dept_name' => ['required', 'string', 'max:255', Rule::unique('departments', 'dept_name')],
            'active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
