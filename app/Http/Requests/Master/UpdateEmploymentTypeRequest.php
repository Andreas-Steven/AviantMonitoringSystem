<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmploymentTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasPermission('employment_type.manage') ?? false;
    }

    public function rules(): array
    {
        $employmentTypeId = (int) $this->route('employment_type');

        return [
            'employment_type_code' => ['required', 'string', 'max:50', Rule::unique('employment_types', 'employment_type_code')->ignore($employmentTypeId, 'employment_type_id')],
            'employment_type_name' => ['required', 'string', 'max:255', Rule::unique('employment_types', 'employment_type_name')->ignore($employmentTypeId, 'employment_type_id')],
            'active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
