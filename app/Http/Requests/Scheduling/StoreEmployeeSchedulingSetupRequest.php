<?php

namespace App\Http\Requests\Scheduling;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeSchedulingSetupRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = auth()->user();

        return $user
            && (
                $user->hasPermission('assignment.manage')
                || $user->hasPermission('shift.manage')
                || $user->hasPermission('workpattern.manage')
            );
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'create_assignment' => $this->boolean('create_assignment'),
            'create_shift_assignment' => $this->boolean('create_shift_assignment'),
            'create_work_pattern_assignment' => $this->boolean('create_work_pattern_assignment'),

            'assignment_effective_end_date' => $this->filled('assignment_effective_end_date')
                ? $this->input('assignment_effective_end_date')
                : null,

            'shift_effective_end_date' => $this->filled('shift_effective_end_date')
                ? $this->input('shift_effective_end_date')
                : null,

            'work_pattern_effective_end_date' => $this->filled('work_pattern_effective_end_date')
                ? $this->input('work_pattern_effective_end_date')
                : null,

            'assignment_notes' => filled((string) $this->input('assignment_notes'))
                ? trim((string) $this->input('assignment_notes'))
                : null,

            'shift_notes' => filled((string) $this->input('shift_notes'))
                ? trim((string) $this->input('shift_notes'))
                : null,

            'work_pattern_notes' => filled((string) $this->input('work_pattern_notes'))
                ? trim((string) $this->input('work_pattern_notes'))
                : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'emp_id' => ['required', 'integer', 'exists:employees,emp_id'],
            'reference_date' => ['required', 'date'],

            'create_assignment' => ['required', 'boolean'],
            'create_shift_assignment' => ['required', 'boolean'],
            'create_work_pattern_assignment' => ['required', 'boolean'],

            'branch_id' => ['required_if:create_assignment,1', 'nullable', 'integer', 'exists:branches,branch_id'],
            'dept_id' => ['required_if:create_assignment,1', 'nullable', 'integer', 'exists:departments,dept_id'],
            'role_id' => ['required_if:create_assignment,1', 'nullable', 'integer', 'exists:roles,role_id'],
            'grade_id' => ['required_if:create_assignment,1', 'nullable', 'integer', 'exists:grades,grade_id'],
            'assignment_effective_start_date' => ['required_if:create_assignment,1', 'nullable', 'date'],
            'assignment_effective_end_date' => ['nullable', 'date', 'after_or_equal:assignment_effective_start_date'],
            'assignment_notes' => ['nullable', 'string'],

            'shift_id' => ['required_if:create_shift_assignment,1', 'nullable', 'integer', 'exists:shifts,shift_id'],
            'assignment_type_code' => ['required_if:create_shift_assignment,1', 'nullable', 'string', 'exists:assignment_types,assignment_type_code'],
            'shift_effective_start_date' => ['required_if:create_shift_assignment,1', 'nullable', 'date'],
            'shift_effective_end_date' => ['nullable', 'date', 'after_or_equal:shift_effective_start_date'],
            'shift_notes' => ['nullable', 'string'],

            'work_pattern_id' => ['required_if:create_work_pattern_assignment,1', 'nullable', 'integer', 'exists:work_patterns,work_pattern_id'],
            'work_pattern_effective_start_date' => ['required_if:create_work_pattern_assignment,1', 'nullable', 'date'],
            'work_pattern_effective_end_date' => ['nullable', 'date', 'after_or_equal:work_pattern_effective_start_date'],
            'work_pattern_notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if (
                !$this->boolean('create_assignment')
                && !$this->boolean('create_shift_assignment')
                && !$this->boolean('create_work_pattern_assignment')
            ) {
                $validator->errors()->add(
                    'create_assignment',
                    'Minimal satu setup missing harus dipilih untuk dibuat.'
                );
            }
        });
    }
}