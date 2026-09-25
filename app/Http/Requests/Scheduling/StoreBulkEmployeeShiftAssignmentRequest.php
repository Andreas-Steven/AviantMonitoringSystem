<?php

namespace App\Http\Requests\Scheduling;

use Illuminate\Foundation\Http\FormRequest;

class StoreBulkEmployeeShiftAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasPermission('shift.manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $employeeIds = $this->input('employee_ids', []);

        if (!is_array($employeeIds)) {
            $employeeIds = [];
        }

        $normalizedIds = collect($employeeIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $this->merge([
            'employee_ids' => $normalizedIds,
            'effective_end_date' => $this->filled('effective_end_date') ? $this->input('effective_end_date') : null,
            'notes' => filled((string) $this->input('notes')) ? trim((string) $this->input('notes')) : null,
            'candidate_status' => $this->input('candidate_status', 'ALL_ACTIVE_EMPLOYEES'),
        ]);
    }

    public function rules(): array
    {
        return [
            'employee_ids' => ['required', 'array', 'min:1'],
            'employee_ids.*' => ['integer', 'exists:employees,emp_id'],

            'shift_id' => ['required', 'integer', 'exists:shifts,shift_id'],
            'assignment_type_code' => ['required', 'string', 'exists:assignment_types,assignment_type_code'],

            'effective_start_date' => ['required', 'date'],
            'effective_end_date' => ['nullable', 'date', 'after_or_equal:effective_start_date'],
            'notes' => ['nullable', 'string'],

            'reference_date' => ['nullable', 'date'],
            'q' => ['nullable', 'string'],
            'employment_type_id' => ['nullable', 'integer', 'exists:employment_types,employment_type_id'],
            'candidate_status' => ['nullable', 'string'],
        ];
    }
}