<?php

namespace App\Http\Requests\Scheduling;

use Illuminate\Foundation\Http\FormRequest;

class PreviewHolidayImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasPermission('holiday.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'source' => ['required', 'string', 'in:calendarific,google'],
        ];
    }
}