<?php

namespace App\Http\Requests\Scheduling;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkPatternRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasPermission('workpattern.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'work_pattern_code' => ['required', 'string', 'max:255', 'unique:work_patterns,work_pattern_code'],
            'work_pattern_name' => ['required', 'string', 'max:255'],
            'evaluation_mode_code' => ['required', 'string', 'exists:evaluation_modes,evaluation_mode_code'],
            'active' => ['required', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }
}