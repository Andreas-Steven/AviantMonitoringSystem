<?php

namespace App\Domains\Requests\Repositories;

use App\Domains\Requests\Models\EmployeeLeaveRequest;
use Illuminate\Database\Eloquent\Collection;

class LeaveRequestRepository
{
    public function getApprovedLeavesForDate(int $empId, string $workDate): Collection
    {
        return EmployeeLeaveRequest::query()
            ->with('leaveType')
            ->where('emp_id', $empId)
            ->where('request_status_code', 'APPROVED')
            ->whereDate('start_date', '<=', $workDate)
            ->whereDate('end_date', '>=', $workDate)
            ->orderBy('start_date')
            ->orderBy('leave_request_id')
            ->get();
    }

    public function findFirstApprovedLeaveForDate(int $empId, string $workDate): ?EmployeeLeaveRequest
    {
        return EmployeeLeaveRequest::query()
            ->with('leaveType')
            ->where('emp_id', $empId)
            ->where('request_status_code', 'APPROVED')
            ->whereDate('start_date', '<=', $workDate)
            ->whereDate('end_date', '>=', $workDate)
            ->orderBy('start_date')
            ->orderBy('leave_request_id')
            ->first();
    }
}