<?php

namespace App\Domains\Scheduling\Services;

use App\Domains\Master\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class EmployeeSchedulingCoverageService
{
    public function getCoverage(array $filters = []): array
    {
        $referenceDate = !empty($filters['reference_date'])
            ? Carbon::parse($filters['reference_date'])->toDateString()
            : Carbon::today()->toDateString();

        $employees = Employee::query()
            ->with([
                'assignments' => function ($query) use ($referenceDate): void {
                    $query
                        ->with(['branch', 'department', 'role', 'grade'])
                        ->whereDate('effective_start_date', '<=', $referenceDate)
                        ->where(function ($q) use ($referenceDate): void {
                            $q->whereNull('effective_end_date')
                                ->orWhereDate('effective_end_date', '>=', $referenceDate);
                        });
                },
                'shiftAssignments' => function ($query) use ($referenceDate): void {
                    $query
                        ->with(['shift', 'assignmentType'])
                        ->whereDate('effective_start_date', '<=', $referenceDate)
                        ->where(function ($q) use ($referenceDate): void {
                            $q->whereNull('effective_end_date')
                                ->orWhereDate('effective_end_date', '>=', $referenceDate);
                        });
                },
                'workPatternAssignments' => function ($query) use ($referenceDate): void {
                    $query
                        ->with(['workPattern'])
                        ->whereDate('effective_start_date', '<=', $referenceDate)
                        ->where(function ($q) use ($referenceDate): void {
                            $q->whereNull('effective_end_date')
                                ->orWhereDate('effective_end_date', '>=', $referenceDate);
                        });
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
            ->orderBy('full_name')
            ->get();

        $allRows = $employees->map(function (Employee $employee): array {
            $assignmentCoverage = $this->resolveLoadedActiveItem(
                $employee->assignments ?? collect()
            );

            $shiftCoverage = $this->resolveLoadedActiveItem(
                $employee->shiftAssignments ?? collect()
            );

            $workPatternCoverage = $this->resolveLoadedActiveItem(
                $employee->workPatternAssignments ?? collect()
            );

            $missingCount = collect([
                $assignmentCoverage['status'] === 'MISSING',
                $shiftCoverage['status'] === 'MISSING',
                $workPatternCoverage['status'] === 'MISSING',
            ])->filter()->count();

            $hasConflict = collect([
                $assignmentCoverage['status'],
                $shiftCoverage['status'],
                $workPatternCoverage['status'],
            ])->contains('CONFLICT');

            $overallStatus = match (true) {
                $hasConflict => 'CONFLICT_DETECTED',
                $missingCount === 0 => 'COMPLETE',
                $missingCount === 3 => 'MISSING_ALL',
                $missingCount > 1 => 'MULTIPLE_MISSING',
                $assignmentCoverage['status'] === 'MISSING' => 'MISSING_ASSIGNMENT',
                $shiftCoverage['status'] === 'MISSING' => 'MISSING_SHIFT_ASSIGNMENT',
                $workPatternCoverage['status'] === 'MISSING' => 'MISSING_WORK_PATTERN',
                default => 'UNKNOWN',
            };

            return [
                'employee' => $employee,
                'overall_status' => $overallStatus,

                'assignment_status' => $assignmentCoverage['status'],
                'assignment_count' => $assignmentCoverage['count'],
                'assignment' => $assignmentCoverage['item'],

                'shift_status' => $shiftCoverage['status'],
                'shift_count' => $shiftCoverage['count'],
                'shift_assignment' => $shiftCoverage['item'],

                'work_pattern_status' => $workPatternCoverage['status'],
                'work_pattern_count' => $workPatternCoverage['count'],
                'work_pattern_assignment' => $workPatternCoverage['item'],
            ];
        });

        $summary = $this->buildSummary($allRows);

        $rows = $allRows;

        if (!empty($filters['overall_status'])) {
            $selectedStatus = strtoupper((string) $filters['overall_status']);

            $rows = $rows
                ->filter(fn (array $row): bool => $row['overall_status'] === $selectedStatus)
                ->values();
        }

        return [
            'referenceDate' => $referenceDate,
            'rows' => $rows,
            'summary' => $summary,
        ];
    }

    protected function resolveLoadedActiveItem(Collection $items): array
    {
        $count = $items->count();

        return [
            'status' => match (true) {
                $count === 0 => 'MISSING',
                $count === 1 => 'OK',
                default => 'CONFLICT',
            },
            'count' => $count,
            'item' => $count === 1 ? $items->first() : null,
        ];
    }

    protected function buildSummary(Collection $rows): array
    {
        return [
            'total' => $rows->count(),

            'complete' => $rows
                ->where('overall_status', 'COMPLETE')
                ->count(),

            'missing_any' => $rows
                ->filter(fn (array $row): bool => in_array($row['overall_status'], [
                    'MISSING_ALL',
                    'MULTIPLE_MISSING',
                    'MISSING_ASSIGNMENT',
                    'MISSING_SHIFT_ASSIGNMENT',
                    'MISSING_WORK_PATTERN',
                ], true))
                ->count(),

            'missing_assignment' => $rows
                ->filter(fn (array $row): bool => $row['assignment_status'] === 'MISSING')
                ->count(),

            'missing_shift_assignment' => $rows
                ->filter(fn (array $row): bool => $row['shift_status'] === 'MISSING')
                ->count(),

            'missing_work_pattern' => $rows
                ->filter(fn (array $row): bool => $row['work_pattern_status'] === 'MISSING')
                ->count(),

            'conflict_detected' => $rows
                ->filter(fn (array $row): bool => in_array('CONFLICT', [
                    $row['assignment_status'],
                    $row['shift_status'],
                    $row['work_pattern_status'],
                ], true))
                ->count(),
        ];
    }
}