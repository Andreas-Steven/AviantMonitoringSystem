<?php

namespace App\Domains\Master\Services;

use App\Domains\Master\Models\Employee;
use App\Domains\Master\Models\EmployeeAssignment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class EmployeeAssignmentDomainService
{
    public function getActiveAssignments(int $empId, Carbon|string $referenceDate, array $ignoreAssignmentIds = []): Collection
    {
        $referenceDate = $referenceDate instanceof Carbon
            ? $referenceDate->toDateString()
            : Carbon::parse($referenceDate)->toDateString();

        return EmployeeAssignment::query()
            ->where('emp_id', $empId)
            ->when(!empty($ignoreAssignmentIds), function ($query) use ($ignoreAssignmentIds): void {
                $query->whereNotIn('assignment_id', $ignoreAssignmentIds);
            })
            ->whereDate('effective_start_date', '<=', $referenceDate)
            ->where(function ($query) use ($referenceDate): void {
                $query->whereNull('effective_end_date')
                    ->orWhereDate('effective_end_date', '>=', $referenceDate);
            })
            ->orderByDesc('effective_start_date')
            ->orderByDesc('assignment_id')
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

        return EmployeeAssignment::query()
            ->where('emp_id', $empId)
            ->when(!empty($ignoreAssignmentIds), function ($query) use ($ignoreAssignmentIds): void {
                $query->whereNotIn('assignment_id', $ignoreAssignmentIds);
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
                'emp_id' => 'Employee nonaktif tidak dapat dibuatkan assignment baru melalui single create.',
            ]);
        }

        if ($this->hasOverlap(
            empId: $empId,
            startDate: (string) $payload['effective_start_date'],
            endDate: $payload['effective_end_date'] ?? null
        )) {
            throw ValidationException::withMessages([
                'effective_start_date' => 'Periode assignment bertabrakan dengan histori assignment employee ini. Gunakan bulk replace atau rapikan histori terlebih dahulu.',
            ]);
        }
    }

    public function assertSingleUpdateAllowed(EmployeeAssignment $assignment, array $payload): void
    {
        $submittedEmpId = (int) ($payload['emp_id'] ?? 0);

        if ($submittedEmpId !== (int) $assignment->emp_id) {
            throw ValidationException::withMessages([
                'emp_id' => 'Employee pada assignment existing tidak boleh diganti melalui edit. Buat assignment baru jika ingin memindahkan employee.',
            ]);
        }

        if ($this->hasOverlap(
            empId: (int) $assignment->emp_id,
            startDate: (string) $payload['effective_start_date'],
            endDate: $payload['effective_end_date'] ?? null,
            ignoreAssignmentIds: [(int) $assignment->assignment_id]
        )) {
            throw ValidationException::withMessages([
                'effective_start_date' => 'Perubahan periode menyebabkan overlap dengan histori assignment lain milik employee ini.',
            ]);
        }
    }
}