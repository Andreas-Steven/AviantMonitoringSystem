<?php

namespace App\Http\Requests\Scheduling;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHolidayEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasPermission('holiday.manage') ?? false;
    }

    public function rules(): array
    {
        $holidayEventId = (int) $this->route('holiday_event');

        return [
            'holiday_code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('holiday_events', 'holiday_code')->ignore($holidayEventId, 'holiday_event_id'),
            ],
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