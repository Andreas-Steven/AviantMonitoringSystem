<?php

namespace App\Http\Requests\Scheduling;

use Illuminate\Foundation\Http\FormRequest;

class StoreHolidayImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasPermission('holiday.manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'selected_rows' => $this->input('selected_rows', []),
            'branch_ids' => $this->input('branch_ids', []),
        ]);
    }

    public function rules(): array
    {
        return [
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'source' => ['required', 'string', 'in:calendarific,google'],

            'scope_mode' => ['required', 'string', 'in:all,selected'],
            'branch_ids' => ['required_if:scope_mode,selected', 'array'],
            'branch_ids.*' => ['integer', 'exists:branches,branch_id'],

            'selected_rows' => ['required', 'array', 'min:1'],
            'selected_rows.*.source' => ['required', 'string'],
            'selected_rows.*.external_id' => ['nullable', 'string'],
            'selected_rows.*.holiday_date' => ['required', 'date'],
            'selected_rows.*.original_name' => ['required', 'string'],
            'selected_rows.*.suggested_name' => ['required', 'string'],
            'selected_rows.*.holiday_code' => ['required', 'string'],
            'selected_rows.*.day_type_code' => ['required', 'string', 'exists:day_types,day_type_code'],
            'selected_rows.*.status' => ['required', 'string', 'in:NEW,EXISTS,CONFLICT'],
            'selected_rows.*.status_note' => ['nullable', 'string'],
            'selected_rows.*.enabled' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $selectedRows = collect($this->input('selected_rows', []))
                ->filter(fn (array $row): bool => (string) ($row['enabled'] ?? '0') === '1');

            if ($selectedRows->where('status', 'NEW')->isEmpty()) {
                $validator->errors()->add(
                    'selected_rows',
                    'Minimal pilih satu holiday dengan status NEW untuk diimport.'
                );
            }
        });
    }
}