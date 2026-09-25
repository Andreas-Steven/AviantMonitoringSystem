<?php

namespace App\Http\Requests\Scheduling;

use Illuminate\Foundation\Http\FormRequest;

class StoreBranchCalendarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasPermission('calendar.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'integer', 'exists:branches,branch_id'],
            'work_date' => ['required', 'date'],
            'day_type_code' => ['required', 'string', 'exists:day_types,day_type_code'],
            'day_name' => ['nullable', 'string', 'max:255'],
            'is_workday' => ['required', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }
}