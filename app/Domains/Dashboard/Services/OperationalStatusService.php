<?php

namespace App\Domains\Dashboard\Services;

use App\Domains\Attendance\Models\AttendanceDaily;
use App\Domains\Master\Models\Employee;
use App\Domains\Scheduling\Models\PayrollPeriod;

class OperationalStatusService
{
    public function activePayrollPeriod(): ?PayrollPeriod
    {
        return PayrollPeriod::query()
            ->where('payroll_period_status_code', 'OPEN')
            ->orderByDesc('period_start_date')
            ->first();
    }

    public function attendanceTodayOverview(): array
    {
        $today = now()->toDateString();

        return [
            'work_date' => $today,

            'employee_count' => Employee::query()
                ->where('active', true)
                ->count(),

            'present_count' => AttendanceDaily::query()
                ->whereDate('work_date', $today)
                ->where('attendance_status_code', 'PRESENT')
                ->count(),

            'late_count' => AttendanceDaily::query()
                ->whereDate('work_date', $today)
                ->where('late_min', '>', 0)
                ->count(),

            'incomplete_count' => AttendanceDaily::query()
                ->whereDate('work_date', $today)
                ->where('attendance_status_code', 'INCOMPLETE')
                ->count(),

            'anomaly_count' => $this->attendanceAnomalyCountForDate($today),
        ];
    }

    public function attendanceAnomalyCountForDate(string $date): int
    {
        return AttendanceDaily::query()
            ->whereDate('work_date', $date)
            ->where(function ($query): void {
                $query->where('anomaly_flag', true)
                    ->orWhere('attendance_status_code', 'MANUAL_REVIEW')
                    ->orWhereNotNull('review_reason_code');
            })
            ->count();
    }

    public function attendanceAnomalyCountForActivePeriod(): int
    {
        $period = $this->activePayrollPeriod();

        if (!$period) {
            return 0;
        }

        return AttendanceDaily::query()
            ->whereBetween('work_date', [
                $period->period_start_date,
                $period->period_end_date,
            ])
            ->where(function ($query): void {
                $query->where('anomaly_flag', true)
                    ->orWhere('attendance_status_code', 'MANUAL_REVIEW')
                    ->orWhereNotNull('review_reason_code');
            })
            ->count();
    }

    public function schedulingCoverage(): array
    {
        $today = now()->toDateString();

        $activeEmployeeCount = \DB::table('employees')
            ->where('active', true)
            ->count();

        $employeesWithShift = \DB::table('employee_shift_assignments as esa')
            ->join('employees as e', 'e.emp_id', '=', 'esa.emp_id')
            ->where('e.active', true)
            ->whereDate('esa.effective_start_date', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('esa.effective_end_date')
                ->orWhereDate('esa.effective_end_date', '>=', $today);
            })
            ->distinct()
            ->count('esa.emp_id');

        $employeesWithPattern = \DB::table('employee_work_pattern_assignments as ewpa')
            ->join('employees as e', 'e.emp_id', '=', 'ewpa.emp_id')
            ->where('e.active', true)
            ->whereDate('ewpa.effective_start_date', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('ewpa.effective_end_date')
                ->orWhereDate('ewpa.effective_end_date', '>=', $today);
            })
            ->distinct()
            ->count('ewpa.emp_id');

        $covered = min($employeesWithShift, $employeesWithPattern);

        $percentage = $activeEmployeeCount > 0
            ? (int) round(($covered / $activeEmployeeCount) * 100)
            : 100;

        return [
            'percentage' => $percentage,
            'missing_shift' => max(0, $activeEmployeeCount - $employeesWithShift),
            'missing_work_pattern' => max(0, $activeEmployeeCount - $employeesWithPattern),
            'total_employee' => $activeEmployeeCount,
            'covered_employee' => $covered,
        ];
    }

    public function branchSetup(): array
    {
        $today = now()->toDateString();
        $period = $this->activePayrollPeriod();

        $missingPolicy = \DB::table('branches as b')
            ->where('b.active', true)
            ->whereNotExists(function ($q) use ($today) {
                $q->select(\DB::raw(1))
                    ->from('branch_policy_assignments as bpa')
                    ->whereColumn('bpa.branch_id', 'b.branch_id')
                    ->whereDate('bpa.effective_start_date', '<=', $today)
                    ->where(function ($sq) use ($today) {
                        $sq->whereNull('bpa.effective_end_date')
                        ->orWhereDate('bpa.effective_end_date', '>=', $today);
                    });
            })
            ->count();

        $missingCalendar = \DB::table('branches as b')
            ->where('b.active', true)
            ->when($period, function ($q) use ($period) {
                $q->whereNotExists(function ($sq) use ($period) {
                    $sq->select(\DB::raw(1))
                        ->from('branch_calendars as bc')
                        ->whereColumn('bc.branch_id', 'b.branch_id')
                        ->whereBetween('bc.work_date', [
                            $period->period_start_date,
                            $period->period_end_date
                        ]);
                });
            })
            ->count();

        return [
            'missing_policy' => $missingPolicy,
            'missing_calendar' => $missingCalendar,
            'active_period_code' => $period?->period_code,
        ];
    }

    public function summaryOverview(): array
    {
        $period = $this->activePayrollPeriod();
        $periodId = $period?->payroll_period_id;

        $employees = \DB::table('employees')
            ->where('active', true)
            ->count();

        $summaries = $periodId
            ? \DB::table('attendance_period_summaries')
                ->where('payroll_period_id', $periodId)
                ->count()
            : 0;

        $results = $periodId
            ? \DB::table('payroll_attendance_results')
                ->where('payroll_period_id', $periodId)
                ->count()
            : 0;

        $amounts = $periodId
            ? \DB::table('payroll_attendance_amounts')
                ->where('payroll_period_id', $periodId)
                ->count()
            : 0;

        return [
            'period_code' => $period?->period_code,
            'employees' => $employees,
            'summaries' => $summaries,
            'results' => $results,
            'amounts' => $amounts,
        ];
    }

    public function payrollReadiness(): array
    {
        $period = $this->activePayrollPeriod();

        $anomaly = $this->attendanceAnomalyCountForActivePeriod();

        $overview = $this->summaryOverview();

        $isReady = $period
            && $anomaly === 0
            && $overview['summaries'] > 0
            && $overview['results'] > 0
            && $overview['amounts'] > 0;

        return [
            'has_active_period' => (bool) $period,
            'attendance_anomaly_count' => $anomaly,
            'is_ready' => $isReady,
        ];
    }
}