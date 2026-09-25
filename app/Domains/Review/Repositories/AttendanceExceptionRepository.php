<?php

namespace App\Domains\Review\Repositories;

use App\Domains\Review\Models\AttendanceException;
use Illuminate\Database\Eloquent\Collection;

class AttendanceExceptionRepository
{
    public function getByEmployeeAndDate(int $empId, string $workDate): Collection
    {
        return AttendanceException::query()
            ->where('emp_id', $empId)
            ->whereDate('work_date', $workDate)
            ->orderBy('attendance_exception_id')
            ->get();
    }

    public function existsByEmployeeAndDate(int $empId, string $workDate): bool
    {
        return AttendanceException::query()
            ->where('emp_id', $empId)
            ->whereDate('work_date', $workDate)
            ->exists();
    }
}