<?php

namespace App\Http\Requests\Scheduling;

use Illuminate\Foundation\Http\FormRequest;

class ImportEmployeeWorkPatternAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasPermission('workpattern.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:5120'],
        ];
    }

    public function attributes(): array
    {
        return [
            'file' => 'employee work pattern assignment import file',
        ];
    }
}