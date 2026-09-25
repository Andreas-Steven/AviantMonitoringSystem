<?php

namespace App\Domains\Scheduling\Actions;

use App\Domains\Audit\Services\SystemChangeLogService;
use App\Domains\Master\Models\EmployeeAssignment;
use App\Domains\Master\Services\EmployeeAssignmentDomainService;
use App\Domains\Scheduling\Models\EmployeeShiftAssignment;
use App\Domains\Scheduling\Models\EmployeeWorkPatternAssignment;
use App\Domains\Scheduling\Services\EmployeeSchedulingCoverageService;
use App\Domains\Scheduling\Services\EmployeeShiftAssignmentDomainService;
use App\Domains\Scheduling\Services\EmployeeWorkPatternAssignmentDomainService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateEmployeeSchedulingSetupAction
{
    public function __construct(
        protected EmployeeAssignmentDomainService $employeeAssignmentDomainService,
        protected EmployeeShiftAssignmentDomainService $employeeShiftAssignmentDomainService,
        protected EmployeeWorkPatternAssignmentDomainService $employeeWorkPatternAssignmentDomainService,
        protected EmployeeSchedulingCoverageService $coverageService,
        protected SystemChangeLogService $changeLogService,
    ) {
    }

    public function execute(array $payload): array
    {
        return DB::transaction(function () use ($payload): array {
            $this->assertCoverageStillAllowsSetup($payload);

            $created = [
                'assignment' => false,
                'shift_assignment' => false,
                'work_pattern_assignment' => false,
            ];

            if (!empty($payload['create_assignment'])) {
                $assignmentPayload = [
                    'emp_id' => (int) $payload['emp_id'],
                    'branch_id' => (int) $payload['branch_id'],
                    'dept_id' => (int) $payload['dept_id'],
                    'role_id' => (int) $payload['role_id'],
                    'grade_id' => (int) $payload['grade_id'],
                    'effective_start_date' => $payload['assignment_effective_start_date'],
                    'effective_end_date' => $payload['assignment_effective_end_date'] ?? null,
                    'is_primary' => true,
                    'notes' => $payload['assignment_notes'] ?? null,
                ];

                $this->employeeAssignmentDomainService->assertSingleStoreAllowed($assignmentPayload);

                $assignment = EmployeeAssignment::query()->create($assignmentPayload);

                $this->changeLogService->logCreated(
                    model: $assignment,
                    notes: 'Created from Employee Scheduling Coverage Wizard.'
                );

                $created['assignment'] = true;
            }

            if (!empty($payload['create_shift_assignment'])) {
                $shiftPayload = [
                    'emp_id' => (int) $payload['emp_id'],
                    'shift_id' => (int) $payload['shift_id'],
                    'assignment_type_code' => (string) $payload['assignment_type_code'],
                    'effective_start_date' => $payload['shift_effective_start_date'],
                    'effective_end_date' => $payload['shift_effective_end_date'] ?? null,
                    'notes' => $payload['shift_notes'] ?? null,
                ];

                $this->employeeShiftAssignmentDomainService->assertSingleStoreAllowed($shiftPayload);

                $shiftAssignment = EmployeeShiftAssignment::query()->create($shiftPayload);

                $this->changeLogService->logCreated(
                    model: $shiftAssignment,
                    notes: 'Created from Employee Scheduling Coverage Wizard.'
                );

                $created['shift_assignment'] = true;
            }

            if (!empty($payload['create_work_pattern_assignment'])) {
                $workPatternPayload = [
                    'emp_id' => (int) $payload['emp_id'],
                    'work_pattern_id' => (int) $payload['work_pattern_id'],
                    'effective_start_date' => $payload['work_pattern_effective_start_date'],
                    'effective_end_date' => $payload['work_pattern_effective_end_date'] ?? null,
                    'notes' => $payload['work_pattern_notes'] ?? null,
                ];

                $this->employeeWorkPatternAssignmentDomainService->assertSingleStoreAllowed($workPatternPayload);

                $workPatternAssignment = EmployeeWorkPatternAssignment::query()->create($workPatternPayload);

                $this->changeLogService->logCreated(
                    model: $workPatternAssignment,
                    notes: 'Created from Employee Scheduling Coverage Wizard.'
                );

                $created['work_pattern_assignment'] = true;
            }

            return $created;
        });
    }

    protected function assertCoverageStillAllowsSetup(array $payload): void
    {
        $coverage = $this->coverageService->getCoverage([
            'reference_date' => $payload['reference_date'],
        ]);

        $row = $coverage['rows']
            ->first(fn (array $item): bool => (int) $item['employee']->emp_id === (int) $payload['emp_id']);

        if (!$row) {
            throw ValidationException::withMessages([
                'emp_id' => 'Employee tidak ditemukan dalam coverage aktif.',
            ]);
        }

        if ($row['overall_status'] === 'COMPLETE') {
            throw ValidationException::withMessages([
                'emp_id' => 'Employee ini sudah lengkap. Refresh halaman coverage sebelum membuat setup baru.',
            ]);
        }

        if ($row['overall_status'] === 'CONFLICT_DETECTED') {
            throw ValidationException::withMessages([
                'emp_id' => 'Employee ini memiliki conflict setup aktif. Rapikan histori terlebih dahulu.',
            ]);
        }

        if (!empty($payload['create_assignment']) && $row['assignment_status'] !== 'MISSING') {
            throw ValidationException::withMessages([
                'create_assignment' => 'Organization assignment sudah tidak missing. Refresh halaman coverage.',
            ]);
        }

        if (!empty($payload['create_shift_assignment']) && $row['shift_status'] !== 'MISSING') {
            throw ValidationException::withMessages([
                'create_shift_assignment' => 'Shift assignment sudah tidak missing. Refresh halaman coverage.',
            ]);
        }

        if (!empty($payload['create_work_pattern_assignment']) && $row['work_pattern_status'] !== 'MISSING') {
            throw ValidationException::withMessages([
                'create_work_pattern_assignment' => 'Work pattern assignment sudah tidak missing. Refresh halaman coverage.',
            ]);
        }
    }
}