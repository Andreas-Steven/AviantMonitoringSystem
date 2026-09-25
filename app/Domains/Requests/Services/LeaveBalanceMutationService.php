<?php

namespace App\Domains\Requests\Services;

use App\Domains\Requests\Models\EmployeeLeaveBalance;
use App\Domains\Requests\Models\EmployeeLeaveBalanceTransaction;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LeaveBalanceMutationService
{
    public function consumeAutoForce(
        int $empId,
        int $leaveTypeId,
        string $workDate,
        float $qty,
        int $employeeLeaveBalanceId,
        ?string $sourceTypeCode = 'SYSTEM',
        ?string $sourceRefId = null,
        ?string $notes = null
    ): EmployeeLeaveBalanceTransaction {
        if ($sourceRefId) {
            $existing = $this->findActiveTransactionBySource(
                transactionTypeCode: 'USE_AUTO_FORCE',
                sourceRefId: $sourceRefId
            );

            if ($existing) {
                return $existing;
            }
        }

        return $this->createTransactionAndRecalc(
            employeeLeaveBalanceId: $employeeLeaveBalanceId,
            empId: $empId,
            leaveTypeId: $leaveTypeId,
            transactionTypeCode: 'USE_AUTO_FORCE',
            transactionDate: $workDate,
            qty: -abs($qty),
            sourceTypeCode: $sourceTypeCode,
            sourceRefId: $sourceRefId,
            reversesTransactionId: null,
            notes: $notes ?? 'Auto force annual leave'
        );
    }

    public function consumeApprovedLeave(
        int $empId,
        int $leaveTypeId,
        string $workDate,
        float $qty,
        int $employeeLeaveBalanceId,
        ?string $sourceTypeCode = 'APPROVAL',
        ?string $sourceRefId = null,
        ?string $notes = null
    ): EmployeeLeaveBalanceTransaction {
        if ($sourceRefId) {
            $existing = $this->findActiveTransactionBySource(
                transactionTypeCode: 'USE_APPROVED_LEAVE',
                sourceRefId: $sourceRefId
            );

            if ($existing) {
                return $existing;
            }
        }

        return $this->createTransactionAndRecalc(
            employeeLeaveBalanceId: $employeeLeaveBalanceId,
            empId: $empId,
            leaveTypeId: $leaveTypeId,
            transactionTypeCode: 'USE_APPROVED_LEAVE',
            transactionDate: $workDate,
            qty: -abs($qty),
            sourceTypeCode: $sourceTypeCode,
            sourceRefId: $sourceRefId,
            reversesTransactionId: null,
            notes: $notes ?? 'Approved leave usage'
        );
    }

    public function adjustPlus(
        int $empId,
        int $leaveTypeId,
        string $transactionDate,
        float $qty,
        int $employeeLeaveBalanceId,
        ?string $sourceTypeCode = 'MANUAL',
        ?string $sourceRefId = null,
        ?string $notes = null
    ): EmployeeLeaveBalanceTransaction {
        return $this->createTransactionAndRecalc(
            employeeLeaveBalanceId: $employeeLeaveBalanceId,
            empId: $empId,
            leaveTypeId: $leaveTypeId,
            transactionTypeCode: 'ADJUST_PLUS',
            transactionDate: $transactionDate,
            qty: abs($qty),
            sourceTypeCode: $sourceTypeCode,
            sourceRefId: $sourceRefId,
            reversesTransactionId: null,
            notes: $notes ?? 'Manual leave balance adjustment plus'
        );
    }

    public function adjustMinus(
        int $empId,
        int $leaveTypeId,
        string $transactionDate,
        float $qty,
        int $employeeLeaveBalanceId,
        ?string $sourceTypeCode = 'MANUAL',
        ?string $sourceRefId = null,
        ?string $notes = null
    ): EmployeeLeaveBalanceTransaction {
        return $this->createTransactionAndRecalc(
            employeeLeaveBalanceId: $employeeLeaveBalanceId,
            empId: $empId,
            leaveTypeId: $leaveTypeId,
            transactionTypeCode: 'ADJUST_MINUS',
            transactionDate: $transactionDate,
            qty: -abs($qty),
            sourceTypeCode: $sourceTypeCode,
            sourceRefId: $sourceRefId,
            reversesTransactionId: null,
            notes: $notes ?? 'Manual leave balance adjustment minus'
        );
    }

    public function expireBalance(
        int $empId,
        int $leaveTypeId,
        string $transactionDate,
        float $qty,
        int $employeeLeaveBalanceId,
        ?string $sourceTypeCode = 'SYSTEM',
        ?string $sourceRefId = null,
        ?string $notes = null
    ): EmployeeLeaveBalanceTransaction {
        return $this->createTransactionAndRecalc(
            employeeLeaveBalanceId: $employeeLeaveBalanceId,
            empId: $empId,
            leaveTypeId: $leaveTypeId,
            transactionTypeCode: 'EXPIRE',
            transactionDate: $transactionDate,
            qty: -abs($qty),
            sourceTypeCode: $sourceTypeCode,
            sourceRefId: $sourceRefId,
            reversesTransactionId: null,
            notes: $notes ?? 'Leave balance expired'
        );
    }

    public function restoreCancelled(
        int $empId,
        int $leaveTypeId,
        string $transactionDate,
        float $qty,
        int $employeeLeaveBalanceId,
        int $reversesTransactionId,
        ?string $sourceTypeCode = 'APPROVAL',
        ?string $sourceRefId = null,
        ?string $notes = null
    ): EmployeeLeaveBalanceTransaction {
        return $this->createTransactionAndRecalc(
            employeeLeaveBalanceId: $employeeLeaveBalanceId,
            empId: $empId,
            leaveTypeId: $leaveTypeId,
            transactionTypeCode: 'RESTORE_CANCELLED',
            transactionDate: $transactionDate,
            qty: abs($qty),
            sourceTypeCode: $sourceTypeCode,
            sourceRefId: $sourceRefId,
            reversesTransactionId: $reversesTransactionId,
            notes: $notes ?? 'Restore cancelled leave usage'
        );
    }

    public function restoreActiveTransactionBySource(
        string $transactionTypeCode,
        string $sourceRefId,
        string $restoreDate,
        ?string $sourceTypeCode = 'SYSTEM',
        ?string $restoreSourceRefId = null,
        ?string $notes = null
    ): ?EmployeeLeaveBalanceTransaction {
        $existing = $this->findActiveTransactionBySource(
            transactionTypeCode: $transactionTypeCode,
            sourceRefId: $sourceRefId
        );

        if (!$existing) {
            return null;
        }

        return $this->restoreCancelled(
            empId: (int) $existing->emp_id,
            leaveTypeId: (int) $existing->leave_type_id,
            transactionDate: $restoreDate,
            qty: abs((float) $existing->qty),
            employeeLeaveBalanceId: (int) $existing->employee_leave_balance_id,
            reversesTransactionId: (int) $existing->employee_leave_balance_transaction_id,
            sourceTypeCode: $sourceTypeCode,
            sourceRefId: $restoreSourceRefId ?? ($sourceRefId . ':RESTORE'),
            notes: $notes ?? 'Restore active leave balance transaction by source'
        );
    }

    private function findActiveTransactionBySource(
        string $transactionTypeCode,
        string $sourceRefId
    ): ?EmployeeLeaveBalanceTransaction {
        return EmployeeLeaveBalanceTransaction::query()
            ->where('transaction_type_code', $transactionTypeCode)
            ->where('source_ref_id', $sourceRefId)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('employee_leave_balance_transactions as reversal_tx')
                    ->whereColumn(
                        'reversal_tx.reverses_transaction_id',
                        'employee_leave_balance_transactions.employee_leave_balance_transaction_id'
                    );
            })
            ->orderByDesc('employee_leave_balance_transaction_id')
            ->first();
    }

    private function createTransactionAndRecalc(
        int $employeeLeaveBalanceId,
        int $empId,
        int $leaveTypeId,
        string $transactionTypeCode,
        string $transactionDate,
        float $qty,
        ?string $sourceTypeCode,
        ?string $sourceRefId,
        ?int $reversesTransactionId,
        ?string $notes
    ): EmployeeLeaveBalanceTransaction {
        return DB::transaction(function () use (
            $employeeLeaveBalanceId,
            $empId,
            $leaveTypeId,
            $transactionTypeCode,
            $transactionDate,
            $qty,
            $sourceTypeCode,
            $sourceRefId,
            $reversesTransactionId,
            $notes
        ): EmployeeLeaveBalanceTransaction {
            $balance = EmployeeLeaveBalance::query()
                ->lockForUpdate()
                ->find($employeeLeaveBalanceId);

            if (!$balance) {
                throw new RuntimeException('Employee leave balance not found.');
            }

            $tx = EmployeeLeaveBalanceTransaction::query()->create([
                'employee_leave_balance_id' => $employeeLeaveBalanceId,
                'emp_id' => $empId,
                'leave_type_id' => $leaveTypeId,
                'transaction_type_code' => $transactionTypeCode,
                'transaction_date' => $transactionDate,
                'qty' => $qty,
                'source_type_code' => $sourceTypeCode,
                'source_ref_id' => $sourceRefId,
                'reverses_transaction_id' => $reversesTransactionId,
                'notes' => $notes,
            ]);

            $this->recalculateBalance($balance);

            return $tx;
        });
    }

    public function recalculateBalance(EmployeeLeaveBalance $balance): void
    {
        $totals = EmployeeLeaveBalanceTransaction::query()
            ->where('employee_leave_balance_id', $balance->employee_leave_balance_id)
            ->selectRaw("
                COALESCE(SUM(CASE WHEN transaction_type_code = 'OPENING' THEN qty ELSE 0 END), 0) as opening_total,
                COALESCE(SUM(CASE WHEN transaction_type_code = 'GRANT' THEN qty ELSE 0 END), 0) as granted_total,
                COALESCE(SUM(CASE WHEN transaction_type_code IN ('USE_APPROVED_LEAVE', 'USE_AUTO_FORCE') THEN ABS(qty) ELSE 0 END), 0) as used_total,
                COALESCE(SUM(CASE WHEN transaction_type_code IN ('ADJUST_PLUS', 'ADJUST_MINUS', 'RESTORE_CANCELLED') THEN qty ELSE 0 END), 0) as adjustment_total,
                COALESCE(SUM(CASE WHEN transaction_type_code = 'EXPIRE' THEN ABS(qty) ELSE 0 END), 0) as expired_total
            ")
            ->first();

        $opening = (float) ($totals->opening_total ?? 0);
        $granted = (float) ($totals->granted_total ?? 0);
        $used = (float) ($totals->used_total ?? 0);
        $adjustment = (float) ($totals->adjustment_total ?? 0);
        $expired = (float) ($totals->expired_total ?? 0);

        $closing = max(0, $opening + $granted + $adjustment - $used - $expired);

        $balance->update([
            'opening_balance' => $opening,
            'granted_amount' => $granted,
            'used_amount' => $used,
            'adjustment_amount' => $adjustment,
            'expired_amount' => $expired,
            'closing_balance' => $closing,
        ]);
    }

    public function grant(
        int $empId,
        int $leaveTypeId,
        string $transactionDate,
        float $qty,
        int $employeeLeaveBalanceId,
        ?string $sourceTypeCode = 'MANUAL',
        ?string $sourceRefId = null,
        ?string $notes = null
    ): EmployeeLeaveBalanceTransaction {
        if ($sourceRefId) {
            $existing = EmployeeLeaveBalanceTransaction::query()
                ->where('transaction_type_code', 'GRANT')
                ->where('source_ref_id', $sourceRefId)
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        return $this->createTransactionAndRecalc(
            employeeLeaveBalanceId: $employeeLeaveBalanceId,
            empId: $empId,
            leaveTypeId: $leaveTypeId,
            transactionTypeCode: 'GRANT',
            transactionDate: $transactionDate,
            qty: abs($qty),
            sourceTypeCode: $sourceTypeCode,
            sourceRefId: $sourceRefId,
            reversesTransactionId: null,
            notes: $notes ?? 'Manual leave grant'
        );
    }
}