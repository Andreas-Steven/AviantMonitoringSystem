<?php

namespace App\Domains\Master\Actions;

use App\Domains\Master\Models\Employee;
use App\Domains\Master\Models\EmployeeAssignment;
use App\Domains\Master\Services\EmployeeAssignmentDomainService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BulkAssignEmployeesAction
{
    public function __construct(
        protected EmployeeAssignmentDomainService $domainService
    ) {
    }

    public function execute(array $payload): array
    {
        $employeeIds = collect($payload['employee_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        $gradeOverrides = collect($payload['employee_grade_overrides'] ?? [])
            ->mapWithKeys(function ($gradeId, $empId) {
                return [(int) $empId => filled($gradeId) ? (int) $gradeId : null];
            });

        $defaultGradeId = (int) $payload['grade_id'];
        $startDate = Carbon::parse((string) $payload['effective_start_date'])->startOfDay();
        $endDate = filled($payload['effective_end_date'] ?? null)
            ? Carbon::parse((string) $payload['effective_end_date'])->startOfDay()
            : null;

        $employees = Employee::query()
            ->whereIn('emp_id', $employeeIds->all())
            ->get()
            ->keyBy('emp_id');

        $result = [
            'total_selected' => $employeeIds->count(),
            'created_count' => 0,
            'replaced_count' => 0,
            'skipped_count' => 0,
            'created_ids' => [],
            'replaced_ids' => [],
            'skipped_items' => [],
        ];

        DB::transaction(function () use (
            $employeeIds,
            $employees,
            $gradeOverrides,
            $defaultGradeId,
            $payload,
            $startDate,
            $endDate,
            &$result
        ): void {
            foreach ($employeeIds as $empId) {
                $employee = $employees->get($empId);

                if (!$employee) {
                    $result['skipped_items'][] = [
                        'emp_id' => $empId,
                        'emp_code' => '-',
                        'full_name' => '-',
                        'reason' => 'EMPLOYEE_NOT_FOUND',
                    ];
                    continue;
                }

                if (!$employee->active) {
                    $result['skipped_items'][] = [
                        'emp_id' => $employee->emp_id,
                        'emp_code' => $employee->emp_code,
                        'full_name' => $employee->full_name,
                        'reason' => 'EMPLOYEE_INACTIVE',
                    ];
                    continue;
                }

                $activeAssignments = $this->domainService->getActiveAssignments(
                    empId: $employee->emp_id,
                    referenceDate: $startDate
                );

                if ($activeAssignments->count() > 1) {
                    $result['skipped_items'][] = [
                        'emp_id' => $employee->emp_id,
                        'emp_code' => $employee->emp_code,
                        'full_name' => $employee->full_name,
                        'reason' => 'OVERLAP_DETECTED',
                    ];
                    continue;
                }

                if ($activeAssignments->count() === 1) {
                    $currentAssignment = $activeAssignments->first();
                    $replacementEndDate = $startDate->copy()->subDay();

                    if ($replacementEndDate->lt(Carbon::parse($currentAssignment->effective_start_date)->startOfDay())) {
                        $result['skipped_items'][] = [
                            'emp_id' => $employee->emp_id,
                            'emp_code' => $employee->emp_code,
                            'full_name' => $employee->full_name,
                            'reason' => 'INVALID_REPLACEMENT_RANGE',
                        ];
                        continue;
                    }

                    $futureOverlapExists = $this->domainService->hasOverlap(
                        empId: $employee->emp_id,
                        startDate: $startDate,
                        endDate: $endDate,
                        ignoreAssignmentIds: [$currentAssignment->assignment_id]
                    );

                    if ($futureOverlapExists) {
                        $result['skipped_items'][] = [
                            'emp_id' => $employee->emp_id,
                            'emp_code' => $employee->emp_code,
                            'full_name' => $employee->full_name,
                            'reason' => 'OVERLAP_ASSIGNMENT',
                        ];
                        continue;
                    }

                    $currentAssignment->update([
                        'effective_end_date' => $replacementEndDate->toDateString(),
                    ]);

                    $newAssignment = $this->createAssignment(
                        empId: $employee->emp_id,
                        payload: $payload,
                        gradeId: $gradeOverrides->get($employee->emp_id) ?: $defaultGradeId,
                        startDate: $startDate->toDateString(),
                        endDate: $endDate?->toDateString()
                    );

                    $result['replaced_ids'][] = $newAssignment->assignment_id;
                    continue;
                }

                $overlapExists = $this->domainService->hasOverlap(
                    empId: $employee->emp_id,
                    startDate: $startDate,
                    endDate: $endDate
                );

                if ($overlapExists) {
                    $result['skipped_items'][] = [
                        'emp_id' => $employee->emp_id,
                        'emp_code' => $employee->emp_code,
                        'full_name' => $employee->full_name,
                        'reason' => 'OVERLAP_ASSIGNMENT',
                    ];
                    continue;
                }

                $newAssignment = $this->createAssignment(
                    empId: $employee->emp_id,
                    payload: $payload,
                    gradeId: $gradeOverrides->get($employee->emp_id) ?: $defaultGradeId,
                    startDate: $startDate->toDateString(),
                    endDate: $endDate?->toDateString()
                );

                $result['created_ids'][] = $newAssignment->assignment_id;
            }
        });

        $result['created_count'] = count($result['created_ids']);
        $result['replaced_count'] = count($result['replaced_ids']);
        $result['skipped_count'] = count($result['skipped_items']);

        return $result;
    }

    protected function createAssignment(
        int $empId,
        array $payload,
        int $gradeId,
        string $startDate,
        ?string $endDate
    ): EmployeeAssignment {
        return EmployeeAssignment::create([
            'emp_id' => $empId,
            'branch_id' => (int) $payload['branch_id'],
            'dept_id' => (int) $payload['dept_id'],
            'role_id' => (int) $payload['role_id'],
            'grade_id' => $gradeId,
            'effective_start_date' => $startDate,
            'effective_end_date' => $endDate,
            'is_primary' => (bool) $payload['is_primary'],
            'notes' => $payload['notes'] ?? null,
        ]);
    }
}