<?php

namespace App\Domains\Summary\Services;

use App\Domains\Requests\Services\LeaveBalanceMutationService;
use App\Domains\Requests\Services\LeaveBalanceResolverService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AttendanceDeficitLeaveConsumptionService
{
    public function __construct(
        protected LeaveBalanceResolverService $leaveBalanceResolverService,
        protected LeaveBalanceMutationService $leaveBalanceMutationService,
    ) {}

    public function consumeByPeriodCode(string $periodCode, ?int $empId = null): array
    {
        $period = DB::table('payroll_periods')
            ->where('period_code', $periodCode)
            ->first();

        if (!$period) {
            throw new RuntimeException("Payroll period not found: {$periodCode}");
        }

        return $this->consume(
            payrollPeriodId: (int) $period->payroll_period_id,
            periodCode: (string) $period->period_code,
            dateFrom: (string) $period->period_start_date,
            dateTo: (string) $period->period_end_date,
            empId: $empId,
        );
    }

    public function consume(
        int $payrollPeriodId,
        string $periodCode,
        string $dateFrom,
        string $dateTo,
        ?int $empId = null
    ): array {
        return DB::transaction(function () use ($payrollPeriodId, $periodCode, $dateFrom, $dateTo, $empId) {
            $query = DB::table('attendance_period_summaries as aps')
                ->where('aps.payroll_period_id', $payrollPeriodId)
                ->where('aps.summary_basis_type_code', 'OBLIGATION')
                ->where('aps.deficit_count', '>', 0);

            if ($empId !== null) {
                $query->where('aps.emp_id', $empId);
            }

            $summaries = $query
                ->orderBy('aps.emp_id')
                ->get();

            $processed = 0;
            $periodAutoLeaveTotal = 0.0;
            $deductionDayTotal = 0.0;
            $rows = [];

            foreach ($summaries as $summary) {
                $summaryEmpId = (int) $summary->emp_id;
                $branchId = (int) $summary->branch_id;

                if (!$this->hasSaturdayAllowanceRule($summaryEmpId, $payrollPeriodId)) {
                    continue;
                }

                $sourceRefId = sprintf(
                    'AUTO_LEAVE:PERIOD:%s:EMP:%d',
                    $periodCode,
                    $summaryEmpId
                );

                /*
                 * Rebuild-safe:
                 * Restore period-level auto leave lama dulu supaya available balance kembali netral.
                 * Daily auto force dan approved leave tidak direstore di sini.
                 */
                $this->leaveBalanceMutationService->restoreActiveTransactionBySource(
                    transactionTypeCode: 'USE_AUTO_FORCE',
                    sourceRefId: $sourceRefId,
                    restoreDate: $dateTo,
                    sourceTypeCode: 'SYSTEM',
                    restoreSourceRefId: $sourceRefId . ':RESTORE',
                    notes: sprintf(
                        'Restore previous period auto leave before recalculation. Period=%s, emp_id=%d.',
                        $periodCode,
                        $summaryEmpId
                    )
                );

                $rawDeficit = (float) $summary->deficit_count;
                $compensatingExcess = (float) ($summary->excess_count ?? 0);
                $netDeficit = max($rawDeficit - $compensatingExcess, 0);

                /*
                 * Existing cover:
                 * - approved leave yang memang jatuh di Sabtu eligible
                 * - auto force harian yang sudah dipakai pada Sabtu eligible
                 *
                 * Ini mencegah period auto leave memotong cuti lagi untuk kekurangan yang
                 * sebenarnya sudah tertutup oleh leave harian/approved.
                 */
                $approvedLeaveCover = $this->countApprovedQuotaLeaveCoverForOsaSaturday(
                    empId: $summaryEmpId,
                    branchId: $branchId,
                    dateFrom: $dateFrom,
                    dateTo: $dateTo
                );

                $dailyAutoForceCover = $this->countDailyAutoForceCoverForOsaSaturday(
                    empId: $summaryEmpId,
                    branchId: $branchId,
                    dateFrom: $dateFrom,
                    dateTo: $dateTo
                );

                $existingLeaveCover = min(
                    $netDeficit,
                    $approvedLeaveCover + $dailyAutoForceCover
                );

                $remainingDeficit = max($netDeficit - $existingLeaveCover, 0);

                $periodAutoLeave = 0.0;
                $deductionDays = $remainingDeficit;

                if ($remainingDeficit > 0) {
                    $resolvedBalance = $this->leaveBalanceResolverService->resolveAvailableBalance(
                        empId: $summaryEmpId,
                        leaveTypeCode: 'ANNUAL',
                        workDate: $dateTo
                    );

                    if ($resolvedBalance) {
                        $availableLeave = (float) $resolvedBalance['available_balance'];
                        $periodAutoLeave = min($remainingDeficit, $availableLeave);
                        $deductionDays = max($remainingDeficit - $periodAutoLeave, 0);

                        if ($periodAutoLeave > 0) {
                            $this->leaveBalanceMutationService->consumeAutoForce(
                                empId: $summaryEmpId,
                                leaveTypeId: (int) $resolvedBalance['leave_type_id'],
                                workDate: $dateTo,
                                qty: $periodAutoLeave,
                                employeeLeaveBalanceId: (int) $resolvedBalance['employee_leave_balance_id'],
                                sourceTypeCode: 'SYSTEM',
                                sourceRefId: $sourceRefId,
                                notes: sprintf(
                                    'Auto leave used to cover period obligation net deficit. Period=%s, raw_deficit=%.2f, compensating_excess=%.2f, net_deficit=%.2f, approved_cover=%.2f, daily_auto_cover=%.2f, period_auto=%.2f.',
                                    $periodCode,
                                    $rawDeficit,
                                    $compensatingExcess,
                                    $netDeficit,
                                    $approvedLeaveCover,
                                    $dailyAutoForceCover,
                                    $periodAutoLeave
                                )
                            );
                        }
                    }
                }

                $finalLeaveUsed = $existingLeaveCover + $periodAutoLeave;

                DB::table('attendance_period_summaries')
                    ->where('attendance_period_summary_id', $summary->attendance_period_summary_id)
                    ->update([
                        'leave_quota_used_count' => round($finalLeaveUsed, 2),
                        'deduction_day_count' => round($deductionDays, 2),
                        'notes' => $this->appendConsumptionNote(
                            notes: (string) $summary->notes,
                            rawDeficit: $rawDeficit,
                            compensatingExcess: $compensatingExcess,
                            netDeficit: $netDeficit,
                            approvedLeaveCover: $approvedLeaveCover,
                            dailyAutoForceCover: $dailyAutoForceCover,
                            periodAutoLeave: $periodAutoLeave,
                            deductionDays: $deductionDays
                        ),
                        'updated_at' => now(),
                    ]);

                $processed++;
                $periodAutoLeaveTotal += $periodAutoLeave;
                $deductionDayTotal += $deductionDays;

                $rows[] = [
                    'emp_id' => $summaryEmpId,
                    'period_code' => $periodCode,
                    'raw_deficit' => round($rawDeficit, 2),
                    'compensating_excess' => round($compensatingExcess, 2),
                    'net_deficit' => round($netDeficit, 2),
                    'approved_leave_cover' => round($approvedLeaveCover, 2),
                    'daily_auto_force_cover' => round($dailyAutoForceCover, 2),
                    'period_auto_leave' => round($periodAutoLeave, 2),
                    'final_leave_used' => round($finalLeaveUsed, 2),
                    'deduction_days' => round($deductionDays, 2),
                    'source_ref_id' => $sourceRefId,
                ];
            }

            return [
                'period_code' => $periodCode,
                'payroll_period_id' => $payrollPeriodId,
                'employee_count' => $summaries->count(),
                'processed_count' => $processed,
                'period_auto_leave_total' => round($periodAutoLeaveTotal, 2),
                'deduction_day_total' => round($deductionDayTotal, 2),
                'rows' => $rows,
            ];
        });
    }

    private function hasSaturdayAllowanceRule(int $empId, int $payrollPeriodId): bool
    {
        return DB::table('employee_period_obligations as epo')
            ->join('work_pattern_rules as wpr', 'wpr.work_pattern_rule_id', '=', 'epo.work_pattern_rule_id')
            ->where('epo.emp_id', $empId)
            ->where('epo.payroll_period_id', $payrollPeriodId)
            ->where('wpr.rule_code', 'OSA_SAT_ALLOWANCE')
            ->exists();
    }

    private function countApprovedQuotaLeaveCoverForOsaSaturday(
        int $empId,
        int $branchId,
        string $dateFrom,
        string $dateTo
    ): float {
        $rows = DB::table('attendance_daily as ad')
            ->join('branch_calendars as bc', function ($join) {
                $join->on('bc.branch_id', '=', 'ad.branch_id')
                    ->on('bc.work_date', '=', 'ad.work_date');
            })
            ->where('ad.emp_id', $empId)
            ->where('ad.branch_id', $branchId)
            ->whereBetween('ad.work_date', [$dateFrom, $dateTo])
            ->where('ad.attendance_status_code', 'LEAVE')
            ->where('ad.leave_flag', true)
            ->whereNotIn('bc.day_type_code', ['HOLIDAY_NATIONAL', 'HOLIDAY_COMPANY'])
            ->whereRaw('EXTRACT(ISODOW FROM ad.work_date) = 6')
            ->get(['ad.work_date']);

        $covered = 0.0;

        foreach ($rows as $row) {
            $hasQuotaLeave = DB::table('employee_leave_requests as lr')
                ->join('leave_types as lt', 'lt.leave_type_id', '=', 'lr.leave_type_id')
                ->where('lr.emp_id', $empId)
                ->where('lr.request_status_code', 'APPROVED')
                ->where('lt.deduct_quota', true)
                ->whereDate('lr.start_date', '<=', $row->work_date)
                ->whereDate('lr.end_date', '>=', $row->work_date)
                ->exists();

            if ($hasQuotaLeave) {
                $covered += 1.0;
            }
        }

        return $covered;
    }

    private function countDailyAutoForceCoverForOsaSaturday(
        int $empId,
        int $branchId,
        string $dateFrom,
        string $dateTo
    ): float {
        $rows = DB::table('employee_leave_balance_transactions as tx')
            ->join('branch_calendars as bc', function ($join) use ($branchId) {
                $join->where('bc.branch_id', '=', $branchId)
                    ->on('bc.work_date', '=', 'tx.transaction_date');
            })
            ->where('tx.emp_id', $empId)
            ->where('tx.transaction_type_code', 'USE_AUTO_FORCE')
            ->whereBetween('tx.transaction_date', [$dateFrom, $dateTo])
            ->where('tx.source_ref_id', 'LIKE', 'AUTO_FORCE_LEAVE|%')
            ->whereNotIn('bc.day_type_code', ['HOLIDAY_NATIONAL', 'HOLIDAY_COMPANY'])
            ->whereRaw('EXTRACT(ISODOW FROM tx.transaction_date) = 6')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('employee_leave_balance_transactions as reversal_tx')
                    ->whereColumn(
                        'reversal_tx.reverses_transaction_id',
                        'tx.employee_leave_balance_transaction_id'
                    );
            })
            ->get(['tx.qty']);

        return (float) $rows->sum(function ($row) {
            return abs((float) $row->qty);
        });
    }

        private function appendConsumptionNote(
        string $notes,
        float $rawDeficit,
        float $compensatingExcess,
        float $netDeficit,
        float $approvedLeaveCover,
        float $dailyAutoForceCover,
        float $periodAutoLeave,
        float $deductionDays
    ): string {
        $baseNotes = preg_replace('/\s*Period leave consumption applied:.*$/', '', $notes);

        return trim($baseNotes) . sprintf(
            ' Period leave consumption applied: raw_deficit=%.2f, compensating_excess=%.2f, net_deficit=%.2f, approved_cover=%.2f, daily_auto_cover=%.2f, period_auto=%.2f, deduction=%.2f.',
            $rawDeficit,
            $compensatingExcess,
            $netDeficit,
            $approvedLeaveCover,
            $dailyAutoForceCover,
            $periodAutoLeave,
            $deductionDays
        );
    }
}