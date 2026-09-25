<?php

namespace App\Domains\Scheduling\Services;

use App\Domains\Master\Models\Employee;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class EmployeeShiftAssignmentCoverageService
{
    public function getCoverage(array $filters = []): array
    {
        $referenceDate = !empty($filters['reference_date'])
            ? Carbon::parse($filters['reference_date'])->toDateString()
            : Carbon::today()->toDateString();

        $employees = Employee::query()
            ->with([
                'employmentType',
                'shiftAssignments' => function ($query) {
                    $query->with(['shift', 'assignmentType'])
                        ->orderByDesc('effective_start_date')
                        ->orderByDesc('employee_shift_assignment_id');
                },
            ])
            ->where('active', true)
            ->when(!empty($filters['q']), function ($query) use ($filters): void {
                $keyword = trim((string) $filters['q']);

                $query->where(function ($sub) use ($keyword): void {
                    $sub->where('emp_code', 'ilike', "%{$keyword}%")
                        ->orWhere('full_name', 'ilike', "%{$keyword}%")
                        ->orWhere('biometric_code', 'ilike', "%{$keyword}%");
                });
            })
            ->when(!empty($filters['employment_type_id']), function ($query) use ($filters): void {
                $query->where('employment_type_id', (int) $filters['employment_type_id']);
            })
            ->orderBy('full_name')
            ->get();

        $rows = $employees->map(function (Employee $employee) use ($referenceDate): array {
            $assignments = $employee->shiftAssignments ?? new Collection();

            $activeAssignments = $assignments->filter(function ($assignment) use ($referenceDate): bool {
                $start = optional($assignment->effective_start_date)->format('Y-m-d');
                $end = optional($assignment->effective_end_date)->format('Y-m-d');

                if ($start === null) {
                    return false;
                }

                return $start <= $referenceDate
                    && ($end === null || $end >= $referenceDate);
            })->values();

            $assignmentCount = $assignments->count();
            $activeCount = $activeAssignments->count();
            $latestAssignment = $assignments->first();
            $activeAssignment = $activeCount === 1 ? $activeAssignments->first() : null;

            $coverageStatus = match (true) {
                $assignmentCount === 0 => 'NO_ASSIGNMENT',
                $activeCount === 0 => 'NO_ACTIVE_ASSIGNMENT',
                $activeCount === 1 => 'HAS_ACTIVE_ASSIGNMENT',
                default => 'OVERLAP_DETECTED',
            };

            $actionMode = match ($coverageStatus) {
                'NO_ASSIGNMENT', 'NO_ACTIVE_ASSIGNMENT' => 'CREATE_NEW',
                'HAS_ACTIVE_ASSIGNMENT' => 'REPLACE_ACTIVE',
                default => 'BLOCKED',
            };

            $coverageNote = match ($coverageStatus) {
                'NO_ASSIGNMENT' => 'Belum pernah memiliki shift assignment.',
                'NO_ACTIVE_ASSIGNMENT' => 'Tidak ada shift assignment aktif pada tanggal referensi.',
                'HAS_ACTIVE_ASSIGNMENT' => 'Employee memiliki satu shift assignment aktif dan dapat direplace di bulk flow.',
                'OVERLAP_DETECTED' => $activeCount . ' shift assignment aktif terdeteksi pada tanggal referensi. Tidak dapat diproses otomatis.',
                default => '-',
            };

            return [
                'employee' => $employee,
                'employment_type_name' => $employee->employmentType->employment_type_name ?? '-',
                'coverage_status' => $coverageStatus,
                'action_mode' => $actionMode,
                'active_assignment_count' => $activeCount,
                'active_assignment' => $activeAssignment,
                'active_assignments' => $activeAssignments,
                'latest_assignment' => $latestAssignment,
                'coverage_note' => $coverageNote,
            ];
        });

        if (!empty($filters['coverage_status'])) {
            $selectedStatus = strtoupper((string) $filters['coverage_status']);
            $rows = $rows->filter(fn (array $row): bool => $row['coverage_status'] === $selectedStatus)->values();
        }

        if (!empty($filters['candidate_status'])) {
            $candidateStatus = strtoupper((string) $filters['candidate_status']);

            if ($candidateStatus === 'SAFE_CANDIDATES') {
                $rows = $rows->filter(function (array $row): bool {
                    return in_array($row['coverage_status'], ['NO_ASSIGNMENT', 'NO_ACTIVE_ASSIGNMENT'], true);
                })->values();
            } elseif ($candidateStatus === 'ALL_ACTIVE_EMPLOYEES') {
                $rows = $rows->values();
            } elseif (in_array($candidateStatus, ['NO_ASSIGNMENT', 'NO_ACTIVE_ASSIGNMENT', 'HAS_ACTIVE_ASSIGNMENT', 'OVERLAP_DETECTED'], true)) {
                $rows = $rows->filter(fn (array $row): bool => $row['coverage_status'] === $candidateStatus)->values();
            }
        }

        $summary = [
            'no_assignment' => $rows->where('coverage_status', 'NO_ASSIGNMENT')->count(),
            'no_active_assignment' => $rows->where('coverage_status', 'NO_ACTIVE_ASSIGNMENT')->count(),
            'has_active_assignment' => $rows->where('coverage_status', 'HAS_ACTIVE_ASSIGNMENT')->count(),
            'overlap_detected' => $rows->where('coverage_status', 'OVERLAP_DETECTED')->count(),
        ];

        return [
            'referenceDate' => $referenceDate,
            'rows' => $rows,
            'summary' => $summary,
        ];
    }
}