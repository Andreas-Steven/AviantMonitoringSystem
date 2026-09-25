<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;

class ImportEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasPermission('employee.manage') ?? false;
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
            'file' => 'employee import file',
        ];
    }
}