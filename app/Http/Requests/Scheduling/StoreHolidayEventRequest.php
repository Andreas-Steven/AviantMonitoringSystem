<?php

namespace App\Http\Requests\Scheduling;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHolidayEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasPermission('holiday.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'holiday_code' => ['required', 'string', 'max:255', 'unique:holiday_events,holiday_code'],
            'holiday_name' => ['required', 'string', 'max:255'],
            'holiday_date' => ['required', 'date'],
            'day_type_code' => [
                'required',
                Rule::in(['HOLIDAY_NATIONAL', 'HOLIDAY_COMPANY', 'HALF_DAY', 'SPECIAL']),
            ],
            'active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],

            'scope_mode' => ['required', Rule::in(['all', 'selected'])],
            'branch_ids' => ['nullable', 'array'],
            'branch_ids.*' => ['integer', 'exists:branches,branch_id'],
            'scope_notes' => ['nullable', 'string'],
        ];
    }
}