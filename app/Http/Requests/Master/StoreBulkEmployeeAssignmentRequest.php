<?php

namespace App\Http\Requests\Master;

use App\Domains\Master\Models\PositionRole;
use Illuminate\Foundation\Http\FormRequest;

class StoreBulkEmployeeAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasPermission('assignment.manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $employeeIds = $this->input('employee_ids', []);
        $gradeOverrides = $this->input('employee_grade_overrides', []);

        if (!is_array($employeeIds)) {
            $employeeIds = [];
        }

        if (!is_array($gradeOverrides)) {
            $gradeOverrides = [];
        }

        $normalizedIds = collect($employeeIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $normalizedOverrides = collect($gradeOverrides)
            ->mapWithKeys(function ($gradeId, $empId) {
                $empId = (int) $empId;
                $gradeId = filled($gradeId) ? (int) $gradeId : null;

                if ($empId <= 0) {
                    return [];
                }

                return [$empId => $gradeId];
            })
            ->all();

        $this->merge([
            'employee_ids' => $normalizedIds,
            'employee_grade_overrides' => $normalizedOverrides,
            'effective_end_date' => $this->filled('effective_end_date') ? $this->input('effective_end_date') : null,
            'notes' => filled((string) $this->input('notes')) ? trim((string) $this->input('notes')) : null,
            'is_primary' => $this->boolean('is_primary'),
        ]);
    }

    public function rules(): array
    {
        return [
            'employee_ids' => ['required', 'array', 'min:1'],
            'employee_ids.*' => ['integer', 'exists:employees,emp_id'],

            'employee_grade_overrides' => ['nullable', 'array'],
            'employee_grade_overrides.*' => ['nullable', 'integer', 'exists:grades,grade_id'],

            'branch_id' => ['required', 'integer', 'exists:branches,branch_id'],
            'dept_id' => ['required', 'integer', 'exists:departments,dept_id'],
            'role_id' => ['required', 'integer', 'exists:roles,role_id'],
            'grade_id' => ['required', 'integer', 'exists:grades,grade_id'],

            'effective_start_date' => ['required', 'date'],
            'effective_end_date' => ['nullable', 'date', 'after_or_equal:effective_start_date'],
            'is_primary' => ['required', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $deptId = (int) $this->input('dept_id');
            $roleId = (int) $this->input('role_id');

            if ($deptId <= 0 || $roleId <= 0) {
                return;
            }

            $role = PositionRole::query()->find($roleId);

            if ($role && (int) $role->dept_id !== $deptId) {
                $validator->errors()->add('role_id', 'Role yang dipilih tidak berada pada department yang dipilih.');
            }
        });
    }
}