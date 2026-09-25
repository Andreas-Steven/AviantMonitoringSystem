<?php

namespace App\Domains\Requests\Services;

use App\Domains\Requests\Models\EmployeeLeaveBalance;

class LeaveBalanceResolverService
{
    public function resolveAvailableBalance(
        int $empId,
        string $leaveTypeCode,
        string $workDate
    ): ?array {
        $balance = EmployeeLeaveBalance::query()
            ->select('employee_leave_balances.*')
            ->join('leave_types', 'leave_types.leave_type_id', '=', 'employee_leave_balances.leave_type_id')
            ->where('employee_leave_balances.emp_id', $empId)
            ->where('leave_types.leave_type_code', $leaveTypeCode)
            ->where('employee_leave_balances.active', true)
            ->whereDate('employee_leave_balances.period_start_date', '<=', $workDate)
            ->whereDate('employee_leave_balances.period_end_date', '>=', $workDate)
            ->orderBy('employee_leave_balances.period_start_date')
            ->first();

        if (!$balance) {
            return null;
        }

        return [
            'employee_leave_balance_id' => (int) $balance->employee_leave_balance_id,
            'emp_id' => (int) $balance->emp_id,
            'leave_type_id' => (int) $balance->leave_type_id,
            'leave_type_code' => $leaveTypeCode,
            'period_start_date' => $balance->period_start_date?->toDateString(),
            'period_end_date' => $balance->period_end_date?->toDateString(),
            'available_balance' => (float) $balance->available_balance,
            'active' => (bool) $balance->active,
            'notes' => $balance->notes,
        ];
    }

    public function hasAvailableBalance(
        int $empId,
        string $leaveTypeCode,
        string $workDate,
        float $qty = 1.0
    ): bool {
        $resolved = $this->resolveAvailableBalance($empId, $leaveTypeCode, $workDate);

        if (!$resolved) {
            return false;
        }

        return (float) $resolved['available_balance'] >= $qty;
    }
}