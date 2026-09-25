<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasPermission('grade.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'grade_code' => ['required', 'string', 'max:50', Rule::unique('grades', 'grade_code')],
            'grade_name' => ['required', 'string', 'max:255'],
            'level_order' => ['required', 'integer', 'min:0'],
            'active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
