<?php

namespace App\Domains\Scheduling\Services;

use App\Domains\Master\Models\Employee;
use App\Domains\Scheduling\Models\EmployeeShiftAssignment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class EmployeeShiftAssignmentDomainService
{
    public function getActiveAssignments(
        int $empId,
        Carbon|string $referenceDate,
        array $ignoreAssignmentIds = []
    ): Collection {
        $referenceDate = $referenceDate instanceof Carbon
            ? $referenceDate->toDateString()
            : Carbon::parse($referenceDate)->toDateString();

        return EmployeeShiftAssignment::query()
            ->where('emp_id', $empId)
            ->when(!empty($ignoreAssignmentIds), function ($query) use ($ignoreAssignmentIds): void {
                $query->whereNotIn('employee_shift_assignment_id', $ignoreAssignmentIds);
            })
            ->whereDate('effective_start_date', '<=', $referenceDate)
            ->where(function ($query) use ($referenceDate): void {
                $query->whereNull('effective_end_date')
                    ->orWhereDate('effective_end_date', '>=', $referenceDate);
            })
            ->orderByDesc('effective_start_date')
            ->orderByDesc('employee_shift_assignment_id')
            ->get();
    }

    public function hasOverlap(
        int $empId,
        Carbon|string $startDate,
        Carbon|string|null $endDate = null,
        array $ignoreAssignmentIds = []
    ): bool {
        $startDate = $startDate instanceof Carbon
            ? $startDate->toDateString()
            : Carbon::parse($startDate)->toDateString();

        $endDate = $endDate
            ? ($endDate instanceof Carbon ? $endDate->toDateString() : Carbon::parse($endDate)->toDateString())
            : '9999-12-31';

        return EmployeeShiftAssignment::query()
            ->where('emp_id', $empId)
            ->when(!empty($ignoreAssignmentIds), function ($query) use ($ignoreAssignmentIds): void {
                $query->whereNotIn('employee_shift_assignment_id', $ignoreAssignmentIds);
            })
            ->whereDate('effective_start_date', '<=', $endDate)
            ->whereRaw('? <= COALESCE(effective_end_date, DATE \'9999-12-31\')', [$startDate])
            ->exists();
    }

    public function assertSingleStoreAllowed(array $payload): void
    {
        $empId = (int) ($payload['emp_id'] ?? 0);

        $employee = Employee::query()->find($empId);

        if (!$employee) {
            throw ValidationException::withMessages([
                'emp_id' => 'Employee tidak ditemukan.',
            ]);
        }

        if (!$employee->active) {
            throw ValidationException::withMessages([
                'emp_id' => 'Employee nonaktif tidak dapat dibuatkan shift assignment baru.',
            ]);
        }

        if ($this->hasOverlap(
            empId: $empId,
            startDate: (string) $payload['effective_start_date'],
            endDate: $payload['effective_end_date'] ?? null
        )) {
            throw ValidationException::withMessages([
                'effective_start_date' => 'Periode shift assignment bertabrakan dengan histori shift assignment employee ini.',
            ]);
        }
    }

    public function assertSingleUpdateAllowed(EmployeeShiftAssignment $assignment, array $payload): void
    {
        $submittedEmpId = (int) ($payload['emp_id'] ?? 0);

        if ($submittedEmpId !== (int) $assignment->emp_id) {
            throw ValidationException::withMessages([
                'emp_id' => 'Employee pada shift assignment existing tidak boleh diganti melalui edit.',
            ]);
        }

        if ($this->hasOverlap(
            empId: (int) $assignment->emp_id,
            startDate: (string) $payload['effective_start_date'],
            endDate: $payload['effective_end_date'] ?? null,
            ignoreAssignmentIds: [(int) $assignment->employee_shift_assignment_id]
        )) {
            throw ValidationException::withMessages([
                'effective_start_date' => 'Perubahan periode menyebabkan overlap dengan histori shift assignment lain milik employee ini.',
            ]);
        }
    }
}