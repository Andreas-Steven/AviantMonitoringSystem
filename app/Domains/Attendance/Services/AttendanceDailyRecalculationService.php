<?php

namespace App\Domains\Attendance\Services;

use App\Domains\Review\Services\AttendanceReviewCaseSyncService;
use App\Domains\Summary\Services\SummaryRecalculationService;
use Illuminate\Support\Facades\DB;

class AttendanceDailyRecalculationService
{
    public function __construct(
        protected AttendanceDailyBuilderService $attendanceDailyBuilderService,
        protected AttendanceReviewCaseSyncService $attendanceReviewCaseSyncService,
        protected SummaryRecalculationService $summaryRecalculationService,
    ) {}

    public function recalculateOneDay(int $empId, string $workDate): array
    {
        $dailyResult = $this->attendanceDailyBuilderService->buildOneDay($empId, $workDate);

        if ($dailyResult === null) {
            return [
                'skipped' => true,
                'emp_id' => $empId,
                'work_date' => $workDate,
                'attendance_daily_id' => null,
                'message' => 'No active assignment / branch policy found for this employee on this date.',
                'review_case' => [
                    'skipped' => true,
                    'reason' => 'daily_not_built',
                ],
                'summary' => [
                    'skipped' => true,
                    'reason' => 'daily_not_built',
                ],
            ];
        }

        $reviewCaseResult = $this->attendanceReviewCaseSyncService->syncForEmployeeDay(
            empId: $empId,
            workDate: $workDate,
        );

        $payrollPeriod = DB::table('payroll_periods')
            ->whereDate('period_start_date', '<=', $workDate)
            ->whereDate('period_end_date', '>=', $workDate)
            ->orderByDesc('period_start_date')
            ->first();

        $summaryResult = [
            'skipped' => true,
            'reason' => 'period_not_found',
        ];

        if ($payrollPeriod) {
            $summaryResult = $this->summaryRecalculationService->recalculateEmployeeWithinPeriod(
                empId: $empId,
                payrollPeriodId: (int) $payrollPeriod->payroll_period_id,
            );
        }

        return [
            'skipped' => false,
            'emp_id' => $empId,
            'work_date' => $workDate,
            'attendance_daily_id' => $dailyResult['attendance_daily_id'],
            'details_count' => $dailyResult['details_count'],
            'review_case' => $reviewCaseResult,
            'summary' => $summaryResult,
        ];
    }
}