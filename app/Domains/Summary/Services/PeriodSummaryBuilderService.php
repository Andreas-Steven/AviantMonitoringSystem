<?php

namespace App\Domains\Summary\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PeriodSummaryBuilderService
{
    public function __construct(
        protected DailySummaryClassifierService $classifier,
    ) {}

    public function buildByPeriodCode(string $periodCode, ?int $empId = null): array
    {
        $period = DB::table('payroll_periods')
            ->where('period_code', $periodCode)
            ->first();

        if (! $period) {
            throw new RuntimeException("Payroll period not found: {$periodCode}");
        }

        return $this->build(
            payrollPeriodId: (int) $period->payroll_period_id,
            dateFrom: $period->period_start_date,
            dateTo: $period->period_end_date,
            periodCode: $periodCode,
            empId: $empId,
        );
    }

    public function build(
        int $payrollPeriodId,
        string $dateFrom,
        string $dateTo,
        string $periodCode,
        ?int $empId = null
    ): array {
        return DB::transaction(function () use ($payrollPeriodId, $dateFrom, $dateTo, $periodCode, $empId) {
            $this->clearExistingSummaries(
                payrollPeriodId: $payrollPeriodId,
                empId: $empId
            );

            $rowsQuery = DB::table('attendance_daily as ad')
                ->join('branch_calendars as bc', function ($join) {
                    $join->on('bc.branch_id', '=', 'ad.branch_id')
                        ->on('bc.work_date', '=', 'ad.work_date');
                })
                ->leftJoin('attendance_policies as ap', 'ap.policy_id', '=', 'ad.policy_id')
                ->select(
                    'ad.*',
                    'bc.day_type_code',
                    'bc.is_workday',
                    'ap.min_work_min_full_day as policy_min_work_min_full_day'
                )
                ->whereBetween('ad.work_date', [$dateFrom, $dateTo]);

            if ($empId !== null) {
                $rowsQuery->where('ad.emp_id', $empId);
            }

            $rows = $rowsQuery->get()->groupBy('emp_id');

            $upserted = 0;
            $result = [];

            foreach ($rows as $groupEmpId => $empRows) {
                $context = $this->resolveEmployeePeriodContext(
                    empId: (int) $groupEmpId,
                    payrollPeriodId: $payrollPeriodId,
                    dateFrom: $dateFrom,
                    dateTo: $dateTo,
                    empRows: $empRows
                );

                if ($context === null) {
                    continue;
                }

                $summary = $this->aggregateEmployee(
                    rows: $empRows,
                    payrollPeriodId: $payrollPeriodId,
                    dateFrom: $dateFrom,
                    dateTo: $dateTo,
                    summaryBasisTypeCode: $context['summary_basis_type_code'],
                    branchId: $context['branch_id'],
                    empId: (int) $groupEmpId
                );

                $row = array_merge([
                    'emp_id' => (int) $groupEmpId,
                    'payroll_period_id' => $payrollPeriodId,
                    'work_pattern_id' => $context['work_pattern_id'],
                    'branch_id' => $context['branch_id'],
                    'summary_basis_type_code' => $context['summary_basis_type_code'],
                ], $summary);

                $this->persist($row);

                $result[] = array_merge([
                    'period_code' => $periodCode,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                ], $row);

                $upserted++;
            }

            return [
                'payroll_period_id' => $payrollPeriodId,
                'period_code' => $periodCode,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'employee_count' => $rows->count(),
                'upserted_count' => $upserted,
                'filtered_emp_id' => $empId,
                'rows' => $result,
            ];
        });
    }

    private function resolveEmployeePeriodContext(
        int $empId,
        int $payrollPeriodId,
        string $dateFrom,
        string $dateTo,
        Collection $empRows
    ): ?array {
        $assignment = DB::table('employee_assignments')
            ->where('emp_id', $empId)
            ->whereDate('effective_start_date', '<=', $dateTo)
            ->where(function ($q) use ($dateFrom) {
                $q->whereNull('effective_end_date')
                    ->orWhereDate('effective_end_date', '>=', $dateFrom);
            })
            ->orderByDesc('effective_start_date')
            ->first();

        $workPatternAssignment = DB::table('employee_work_pattern_assignments as ewpa')
            ->join('work_patterns as wp', 'wp.work_pattern_id', '=', 'ewpa.work_pattern_id')
            ->where('ewpa.emp_id', $empId)
            ->whereDate('ewpa.effective_start_date', '<=', $dateTo)
            ->where(function ($q) use ($dateFrom) {
                $q->whereNull('ewpa.effective_end_date')
                    ->orWhereDate('ewpa.effective_end_date', '>=', $dateFrom);
            })
            ->orderByDesc('ewpa.effective_start_date')
            ->select('ewpa.work_pattern_id', 'wp.evaluation_mode_code')
            ->first();

        $branchId = (int) ($assignment->branch_id ?? $empRows->last()->branch_id ?? 0);

        if ($branchId <= 0 || ! $workPatternAssignment) {
            return null;
        }

        $summaryBasisTypeCode = match ($workPatternAssignment->evaluation_mode_code) {
            'TARGET_HEK' => 'HEK',
            'OFFICE_OBLIGATION' => 'OBLIGATION',
            default => 'HEK',
        };

        return [
            'payroll_period_id' => $payrollPeriodId,
            'work_pattern_id' => (int) $workPatternAssignment->work_pattern_id,
            'branch_id' => $branchId,
            'summary_basis_type_code' => $summaryBasisTypeCode,
        ];
    }

    private function aggregateEmployee(
        Collection $rows,
        int $payrollPeriodId,
        string $dateFrom,
        string $dateTo,
        string $summaryBasisTypeCode,
        int $branchId,
        int $empId
    ): array {
        $validPresentCount = 0.0;
        $overtimeDayCount = 0.0;
        $overtimeMinTotal = 0;
        $leaveQuotaUsedCount = 0.0;
        $deductionDayCount = 0.0;

        $lateCount = 0;
        $lateMinTotal = 0;
        $earlyOutCount = 0;
        $earlyOutMinTotal = 0;
        $incompleteCount = 0;

        foreach ($rows as $row) {
            if ($this->isValidPresentForHek($row)) {
                $validPresentCount += 1.0;
            }

            if ((int) $row->overtime_min > 0) {
                $overtimeDayCount += 1.0;
                $overtimeMinTotal += (int) $row->overtime_min;
            }

            if ($this->isQuotaLeaveDay($row)) {
                $leaveQuotaUsedCount += 1.0;
            }

            $lateMin = (int) ($row->late_min ?? 0);
            $earlyOutMin = (int) ($row->early_out_min ?? 0);

            if ($lateMin > 0) {
                $lateCount++;
                $lateMinTotal += $lateMin;
            }

            if ($earlyOutMin > 0) {
                $earlyOutCount++;
                $earlyOutMinTotal += $earlyOutMin;
            }

            if (
                in_array((string) $row->attendance_status_code, ['INCOMPLETE', 'MANUAL_REVIEW'], true)
                || ! empty($row->review_reason_code)
                || (bool) ($row->anomaly_flag ?? false)
            ) {
                $incompleteCount++;
            }

            // if ($summaryBasisTypeCode === 'HEK') {
            //     $minWorkMinutes = $this->resolveMinimumWorkMinutesForDeduction($row);
            //     $deductionDayCount += $this->resolveDailyDeductionForHek($row, $minWorkMinutes);
            // }
            // HEK basis uses period-level target evaluation.
            // Daily ABSENT / low work_min should not directly create deduction days.
            // Deduction for HEK is derived later from deficit_count.
        }

        if ($summaryBasisTypeCode === 'OBLIGATION') {
            $calendarRows = $this->resolveCalendarRows($branchId, $dateFrom, $dateTo);

            $hasSaturdayAllowance = DB::table('employee_period_obligations as epo')
                ->join('work_pattern_rules as wpr', 'wpr.work_pattern_rule_id', '=', 'epo.work_pattern_rule_id')
                ->where('epo.emp_id', $empId)
                ->where('epo.payroll_period_id', $payrollPeriodId)
                ->where('wpr.rule_code', 'OSA_SAT_ALLOWANCE')
                ->exists();

            if ($hasSaturdayAllowance) {
                $informativeHekCount = $this->resolveOfficeSaturdayAllowanceHek(
                    empId: $empId,
                    payrollPeriodId: $payrollPeriodId,
                    calendarRows: $calendarRows
                );
            } else {
                $informativeHekCount = $this->resolveInformativeHekCountForObligation($calendarRows);
            }

            $obligationTotals = $this->resolveObligationTotals(
                empId: $empId,
                payrollPeriodId: $payrollPeriodId
            );

            $rawDeficitCount = (float) $obligationTotals['deficit_count'];
            $compensatingExcessCount = $this->resolveNonObligationExcessDays($rows);
            $netDeficitCount = max($rawDeficitCount - $compensatingExcessCount, 0);

            return [
                'hek_count' => round($informativeHekCount, 2),
                'valid_present_count' => round($validPresentCount, 2),
                'deficit_count' => round($rawDeficitCount, 2),
                'excess_count' => round($compensatingExcessCount, 2),
                'overtime_day_count' => round($overtimeDayCount, 2),
                'overtime_min_total' => $overtimeMinTotal,
                'late_count' => $lateCount,
                'late_min_total' => $lateMinTotal,
                'early_out_count' => $earlyOutCount,
                'early_out_min_total' => $earlyOutMinTotal,
                'incomplete_count' => $incompleteCount,
                'leave_quota_used_count' => round($leaveQuotaUsedCount, 2),
                'deduction_day_count' => round($netDeficitCount, 2),
                'notes' => 'OBLIGATION basis summary. Deficit is raw obligation shortage. Excess is compensating attendance on non-obligation days such as Sunday, national holiday, or company holiday. Deduction follows net deficit after compensation and leave coverage.',
            ];
        }

        $calendarRows = $this->resolveCalendarRows($branchId, $dateFrom, $dateTo);
        $hekCount = $this->resolveHekCountForHekBasis($calendarRows);

        $deficitCount = max($hekCount - $validPresentCount, 0);
        $excessCount = max($validPresentCount - $hekCount, 0);

        return [
            'hek_count' => round($hekCount, 2),
            'valid_present_count' => round($validPresentCount, 2),
            'deficit_count' => round($deficitCount, 2),
            'excess_count' => round($excessCount, 2),
            'overtime_day_count' => round($overtimeDayCount, 2),
            'overtime_min_total' => $overtimeMinTotal,
            'late_count' => $lateCount,
            'late_min_total' => $lateMinTotal,
            'early_out_count' => $earlyOutCount,
            'early_out_min_total' => $earlyOutMinTotal,
            'incomplete_count' => $incompleteCount,
            'leave_quota_used_count' => round($leaveQuotaUsedCount, 2),
            'deduction_day_count' => round($deficitCount, 2),
            'notes' => sprintf(
                'HEK basis summary generated from attendance_daily + payroll calendar rule. HEK=total days - Sundays - HOLIDAY_NATIONAL - HOLIDAY_COMPANY. Deduction follows HEK deficit only. Daily ABSENT / low work_min are treated as attendance quality issues, not direct deduction.',
            ),
        ];
    }

    private function resolveCalendarRows(int $branchId, string $dateFrom, string $dateTo): Collection
    {
        return DB::table('branch_calendars')
            ->where('branch_id', $branchId)
            ->whereBetween('work_date', [$dateFrom, $dateTo])
            ->orderBy('work_date')
            ->get(['work_date', 'day_type_code', 'is_workday']);
    }

    private function resolveHekCountForHekBasis(Collection $calendarRows): float
    {
        $totalDays = $calendarRows->count();

        $sundayCount = $calendarRows->filter(function ($row) {
            return (int) Carbon::parse($row->work_date)->dayOfWeekIso === 7;
        })->count();

        $nationalHolidayCount = $calendarRows->where('day_type_code', 'HOLIDAY_NATIONAL')->count();
        $companyHolidayCount = $calendarRows->where('day_type_code', 'HOLIDAY_COMPANY')->count();

        return (float) max(
            $totalDays - $sundayCount - $nationalHolidayCount - $companyHolidayCount,
            0
        );
    }

    private function isValidPresentForHek(object $row): bool
    {
        return $row->attendance_status_code === 'PRESENT';
    }

    private function isQuotaLeaveDay(object $row): bool
    {
        if ($row->attendance_status_code !== 'LEAVE') {
            return false;
        }

        if ((bool) ($row->leave_flag ?? false) === true) {
            return true;
        }

        return $this->isQuotaLeave((int) $row->emp_id, (string) $row->work_date);
    }

    // private function resolveDailyDeductionForHek(object $row, int $minWorkMinutes): int
    // {
    //     // =========================================
    //     // 1. Skip hari yang tidak boleh dihukum
    //     // =========================================
    //     $dayType = (string) ($row->day_type_code ?? '');

    //     if (in_array($dayType, ['HOLIDAY_NATIONAL'], true)) {
    //         return 0;
    //     }

    //     // Minggu (ISO 7)
    //     if (\Carbon\Carbon::parse($row->work_date)->dayOfWeekIso === 7) {
    //         return 0;
    //     }

    //     // =========================================
    //     // 2. Leave → tidak dihukum
    //     // =========================================
    //     if ($this->isQuotaLeaveDay($row)) {
    //         return 0;
    //     }

    //     // =========================================
    //     // 3. ABSENT → dihukum (hari kerja saja)
    //     // =========================================
    //     if ($row->attendance_status_code === 'ABSENT') {
    //         return 1;
    //     }

    //     // =========================================
    //     // 4. Selain PRESENT → tidak dihukum
    //     // =========================================
    //     if ($row->attendance_status_code !== 'PRESENT') {
    //         return 0;
    //     }

    //     // =========================================
    //     // 5. PRESENT tapi parsial → tidak dihukum
    //     // =========================================
    //     $hasActualIn = ! empty($row->actual_in_datetime);
    //     $hasActualOut = ! empty($row->actual_out_datetime);

    //     if (! $hasActualIn || ! $hasActualOut) {
    //         return 0;
    //     }

    //     // =========================================
    //     // 6. PRESENT lengkap tapi kurang jam kerja
    //     // =========================================
    //     if ($minWorkMinutes > 0 && (int) $row->work_min < $minWorkMinutes) {
    //         return 1;
    //     }

    //     return 0;
    // }

    private function resolveMinimumWorkMinutesForDeduction(object $row): int
    {
        return max((int) ($row->policy_min_work_min_full_day ?? 0), 0);
    }

    private function isQuotaLeave(int $empId, string $workDate): bool
    {
        return DB::table('employee_leave_requests as lr')
            ->join('leave_types as lt', 'lt.leave_type_id', '=', 'lr.leave_type_id')
            ->where('lr.emp_id', $empId)
            ->where('lr.request_status_code', 'APPROVED')
            ->whereDate('lr.start_date', '<=', $workDate)
            ->whereDate('lr.end_date', '>=', $workDate)
            ->where('lt.deduct_quota', true)
            ->exists();
    }

    private function resolveOfficeSaturdayAllowanceHek(
        int $empId,
        int $payrollPeriodId,
        Collection $calendarRows
    ): float {
        // 1. Hitung Senin–Jumat (weekday)
        $weekdayCount = $calendarRows
            ->filter(function ($row) {
                $isoDow = (int) Carbon::parse($row->work_date)->isoWeekday();

                return (bool) $row->is_workday
                    && $isoDow >= 1
                    && $isoDow <= 5;
            })
            ->count();

        // 2. Ambil required Saturday dari obligation
        $requiredSaturday = DB::table('employee_period_obligations as epo')
            ->join('work_pattern_rules as wpr', 'wpr.work_pattern_rule_id', '=', 'epo.work_pattern_rule_id')
            ->where('epo.emp_id', $empId)
            ->where('epo.payroll_period_id', $payrollPeriodId)
            ->where('wpr.rule_code', 'OSA_SAT_ALLOWANCE')
            ->value('epo.required_count') ?? 0;

        return (float) ($weekdayCount + $requiredSaturday);
    }

    private function resolveObligationTotals(int $empId, int $payrollPeriodId): array
    {
        $row = DB::table('employee_period_obligations as epo')
            ->where('epo.emp_id', $empId)
            ->where('epo.payroll_period_id', $payrollPeriodId)
            ->selectRaw('
                COALESCE(SUM(GREATEST(epo.required_count - epo.actual_count, 0)), 0) as deficit_count,
                COALESCE(SUM(epo.excess_count), 0) as excess_count
            ')
            ->first();

        return [
            'deficit_count' => (float) ($row->deficit_count ?? 0),
            'excess_count' => (float) ($row->excess_count ?? 0),
        ];
    }

    private function persist(array $row): void
    {
        $payload = [
            'work_pattern_id' => $row['work_pattern_id'],
            'branch_id' => $row['branch_id'],
            'summary_basis_type_code' => $row['summary_basis_type_code'],

            'hek_count' => $row['hek_count'],
            'valid_present_count' => $row['valid_present_count'],
            'deficit_count' => $row['deficit_count'],
            'excess_count' => $row['excess_count'],

            'overtime_day_count' => $row['overtime_day_count'],
            'overtime_min_total' => $row['overtime_min_total'],
            'late_count' => $row['late_count'] ?? 0,
            'late_min_total' => $row['late_min_total'] ?? 0,
            'early_out_count' => $row['early_out_count'] ?? 0,
            'early_out_min_total' => $row['early_out_min_total'] ?? 0,
            'incomplete_count' => $row['incomplete_count'] ?? 0,
            'leave_quota_used_count' => $row['leave_quota_used_count'],
            'deduction_day_count' => $row['deduction_day_count'],

            'calculated_at' => now(),
            'notes' => $row['notes'],
            'updated_at' => now(),
        ];

        $existing = DB::table('attendance_period_summaries')
            ->where('emp_id', $row['emp_id'])
            ->where('payroll_period_id', $row['payroll_period_id'])
            ->where('summary_basis_type_code', $row['summary_basis_type_code'])
            ->first();

        if ($existing) {
            DB::table('attendance_period_summaries')
                ->where('attendance_period_summary_id', $existing->attendance_period_summary_id)
                ->update($payload);

            return;
        }

        DB::table('attendance_period_summaries')->insert(array_merge($payload, [
            'emp_id' => $row['emp_id'],
            'payroll_period_id' => $row['payroll_period_id'],
            'created_at' => now(),
        ]));
    }

    private function resolveInformativeHekCountForObligation(Collection $calendarRows): float
    {
        return (float) $calendarRows->filter(function ($row) {
            return (bool) $row->is_workday === true;
        })->count();
    }

    private function clearExistingSummaries(int $payrollPeriodId, ?int $empId = null): void
    {
        $query = DB::table('attendance_period_summaries')
            ->where('payroll_period_id', $payrollPeriodId);

        if ($empId !== null) {
            $query->where('emp_id', $empId);
        }

        $query->delete();
    }

    private function isExcludedFromPayrollPresent(?string $reviewReasonCode): bool
    {
        if ($reviewReasonCode === null || $reviewReasonCode === '') {
            return false;
        }

        return in_array($reviewReasonCode, [
            'IGNORED',
            'EXCLUDED_FROM_PAYROLL',
        ], true);
    }

    private function resolveNonObligationExcessDays(Collection $rows): float
    {
        $count = 0.0;

        foreach ($rows as $row) {
            $dayType = (string) ($row->day_type_code ?? '');
            $isSunday = (int) Carbon::parse($row->work_date)->isoWeekday() === 7;

            $isHoliday = in_array($dayType, [
                'HOLIDAY_NATIONAL',
                'HOLIDAY_COMPANY',
            ], true);

            if (! $isSunday && ! $isHoliday) {
                continue;
            }

            $isValidPresent =
                $row->attendance_status_code === 'PRESENT'
                && ! $this->isExcludedFromPayrollPresent($row->review_reason_code ?? null);

            if ($isValidPresent) {
                $count += 1.0;
            }
        }

        return $count;
    }
}