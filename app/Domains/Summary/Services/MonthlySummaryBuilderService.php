<?php

namespace App\Domains\Summary\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class MonthlySummaryBuilderService
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
            throw new RuntimeException('Payroll period not found.');
        }

        return $this->build(
            dateFrom: $period->period_start_date,
            dateTo: $period->period_end_date,
            year: (int) $period->payroll_year,
            month: (int) $period->payroll_month,
            empId: $empId,
        );
    }

    public function build(
        string $dateFrom,
        string $dateTo,
        int $year,
        int $month,
        ?int $empId = null
    ): array {
        $rowsQuery = DB::table('attendance_daily as ad')
            ->join('branch_calendars as bc', function ($join) {
                $join->on('bc.branch_id', '=', 'ad.branch_id')
                    ->on('bc.work_date', '=', 'ad.work_date');
            })
            ->whereBetween('ad.work_date', [$dateFrom, $dateTo])
            ->select(
                'ad.*',
                'bc.day_type_code',
                'bc.is_workday'
            );

        if ($empId !== null) {
            $rowsQuery->where('ad.emp_id', $empId);
        }

        $rows = $rowsQuery->get();
        $grouped = $rows->groupBy('emp_id');

        $upserted = 0;

        foreach ($grouped as $groupEmpId => $empRows) {
            $summary = $this->aggregateEmployee($empRows);
            $branchId = (int) ($empRows->last()->branch_id ?? $empRows->first()->branch_id ?? 0);

            if ($branchId <= 0) {
                continue;
            }

            $this->persist(
                empId: (int) $groupEmpId,
                year: $year,
                month: $month,
                branchId: $branchId,
                data: $summary,
            );

            $upserted++;
        }

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'period_year' => $year,
            'period_month' => $month,
            'employee_count' => $grouped->count(),
            'upserted_count' => $upserted,
            'filtered_emp_id' => $empId,
        ];
    }

    private function aggregateEmployee($rows): array
    {
        $presentDays = 0;
        $absentDays = 0;
        $leaveDays = 0;
        $sickDays = 0;
        $permissionDays = 0;

        $lateCount = 0;
        $lateMin = 0;

        $earlyOutCount = 0;
        $earlyOutMin = 0;

        $overtimeMin = 0;

        $incompleteCount = 0;

        foreach ($rows as $row) {
            $classification = $this->classifier->classify(
                [
                    'attendance_status_code' => $row->attendance_status_code,
                    'presence_type_code' => $row->presence_type_code,
                    'anomaly_flag' => (bool) $row->anomaly_flag,
                    'leave_flag' => (bool) $row->leave_flag,
                    'exception_flag' => (bool) $row->exception_flag,
                ],
                [
                    'day_type_code' => $row->day_type_code,
                    'is_workday' => (bool) $row->is_workday,
                ]
            );

            $class = $classification['summary_class'];
            $presentValue = (float) $classification['count_present_value'];

            $isValidPresent =
                $row->attendance_status_code === 'PRESENT'
                && !$this->isExcludedFromPayrollPresent($row->review_reason_code);

            if (in_array($class, ['PRESENT_WORKDAY', 'HOLIDAY_WORK', 'OFFDAY_WORK'], true) && $isValidPresent) {
                $presentDays += $presentValue;
            }

            if ($class === 'ABSENT') {
                $absentDays += 1;
            }

            if ($class === 'LEAVE') {
                $leaveDays += 1;
            }

            if ($row->attendance_status_code === 'SICK') {
                $sickDays += 1;
            }

            if ($row->attendance_status_code === 'PERMISSION') {
                $permissionDays += 1;
            }

            if ($isValidPresent) {
                if ((int) $row->late_min > 0) {
                    $lateCount++;
                    $lateMin += (int) $row->late_min;
                }

                if ((int) $row->early_out_min > 0) {
                    $earlyOutCount++;
                    $earlyOutMin += (int) $row->early_out_min;
                }

                $overtimeMin += (int) $row->overtime_min;
            }

            if (in_array($row->attendance_status_code, ['MANUAL_REVIEW', 'INCOMPLETE'], true)) {
                $incompleteCount++;
            }
        }

        return [
            'present_days' => round($presentDays, 2),
            'absent_days' => round($absentDays, 2),
            'leave_days' => round($leaveDays, 2),
            'sick_days' => round($sickDays, 2),
            'permission_days' => round($permissionDays, 2),
            'late_count' => $lateCount,
            'late_min_total' => $lateMin,
            'early_out_count' => $earlyOutCount,
            'early_out_min_total' => $earlyOutMin,
            'overtime_min_total' => $overtimeMin,
            'incomplete_count' => $incompleteCount,
        ];
    }

    private function persist(int $empId, int $year, int $month, int $branchId, array $data): void
    {
        $payload = array_merge($data, [
            'branch_id' => $branchId,
            'calculated_at' => now(),
            'updated_at' => now(),
        ]);

        $existing = DB::table('attendance_monthly_summary')
            ->where('emp_id', $empId)
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->first();

        if ($existing) {
            DB::table('attendance_monthly_summary')
                ->where('summary_id', $existing->summary_id)
                ->update($payload);

            return;
        }

        DB::table('attendance_monthly_summary')->insert(array_merge($payload, [
            'emp_id' => $empId,
            'period_year' => $year,
            'period_month' => $month,
            'created_at' => now(),
        ]));
    }

    private function isExcludedFromPayrollPresent(?string $reviewReasonCode): bool
    {
        return in_array($reviewReasonCode, [
            'SHIFT_MISSING',
            'FORGOT_CHECKIN_APPROVED',
            'FORGOT_CHECKOUT_APPROVED',
        ], true);
    }
}