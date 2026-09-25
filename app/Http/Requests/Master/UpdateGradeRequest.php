<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasPermission('grade.manage') ?? false;
    }

    public function rules(): array
    {
        $gradeId = (int) $this->route('grade');

        return [
            'grade_code' => ['required', 'string', 'max:50', Rule::unique('grades', 'grade_code')->ignore($gradeId, 'grade_id')],
            'grade_name' => ['required', 'string', 'max:255'],
            'level_order' => ['required', 'integer', 'min:0'],
            'active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
