<?php

namespace App\Domains\Summary\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class SummaryRecalculationService
{
    public function __construct(
        protected MonthlySummaryBuilderService $monthlySummaryBuilderService,
    ) {}

    public function recalculateByPayrollPeriodId(int $payrollPeriodId, ?int $empId = null): array
    {
        $period = DB::table('payroll_periods')
            ->where('payroll_period_id', $payrollPeriodId)
            ->first();

        if (! $period) {
            throw new RuntimeException('Payroll period not found.');
        }

        return $this->recalculateByPeriodCode(
            periodCode: (string) $period->period_code,
            empId: $empId,
        );
    }

    public function recalculateByPeriodCode(string $periodCode, ?int $empId = null): array
    {
        $monthly = $this->monthlySummaryBuilderService->buildByPeriodCode(
            periodCode: $periodCode,
            empId: $empId,
        );

        return [
            'period_code' => $periodCode,
            'filtered_emp_id' => $empId,
            'monthly' => $monthly,
            'period_summary' => [
                'skipped' => true,
                'reason' => 'attendance_period_summary_builder_not_implemented_yet',
            ],
            'obligations' => [
                'skipped' => true,
                'reason' => 'employee_period_obligation_builder_not_implemented_yet',
            ],
        ];
    }

    public function recalculateEmployeeWithinPeriod(int $empId, int $payrollPeriodId): array
    {
        $period = DB::table('payroll_periods')
            ->where('payroll_period_id', $payrollPeriodId)
            ->first();

        if (! $period) {
            throw new RuntimeException('Payroll period not found.');
        }

        return $this->recalculateByPeriodCode(
            periodCode: (string) $period->period_code,
            empId: $empId,
        );
    }
}