<?php

namespace App\Console\Commands;

use App\Domains\Summary\Services\AttendanceDeficitLeaveConsumptionService;
use App\Domains\Summary\Services\PayrollAttendanceAmountCalculatorService;
use App\Domains\Summary\Services\PayrollAttendanceCalculatorService;
use App\Domains\Summary\Services\PeriodObligationBuilderService;
use App\Domains\Summary\Services\PeriodSummaryBuilderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RebuildPayrollPipelineCommand extends Command
{
    protected $signature = 'payroll:rebuild
                            {period-code : Payroll period code, example: 2025-03}
                            {--emp-id= : Optional employee id for partial rebuild}';

    protected $description = 'Rebuild payroll pipeline: obligation -> period summary -> leave consumption -> payroll result -> payroll amount';

    public function handle(
        PeriodObligationBuilderService $periodObligationBuilderService,
        PeriodSummaryBuilderService $periodSummaryBuilderService,
        AttendanceDeficitLeaveConsumptionService $attendanceDeficitLeaveConsumptionService,
        PayrollAttendanceCalculatorService $payrollAttendanceCalculatorService,
        PayrollAttendanceAmountCalculatorService $payrollAttendanceAmountCalculatorService
    ): int {
        $periodCode = (string) $this->argument('period-code');
        $empIdOption = $this->option('emp-id');
        $empId = $empIdOption !== null ? (int) $empIdOption : null;

        $period = DB::table('payroll_periods')
            ->where('period_code', $periodCode)
            ->first();

        if (!$period) {
            throw new RuntimeException("Payroll period not found: {$periodCode}");
        }

        $this->info("Rebuilding payroll pipeline for period {$periodCode}" . ($empId ? " (emp_id={$empId})" : '') . ' ...');
        $this->newLine();

        $this->line('[1/5] Rebuild employee period obligations');
        $obligationResult = $periodObligationBuilderService->buildByPeriodCode($periodCode, $empId);
        $this->line(sprintf(
            '      employee_count=%d, upserted_count=%d',
            (int) ($obligationResult['employee_count'] ?? 0),
            (int) ($obligationResult['upserted_count'] ?? 0),
        ));

        $this->line('[2/5] Rebuild period summary');
        $summaryResult = $periodSummaryBuilderService->buildByPeriodCode($periodCode, $empId);
        $this->line(sprintf(
            '      employee_count=%d, upserted_count=%d',
            (int) ($summaryResult['employee_count'] ?? 0),
            (int) ($summaryResult['upserted_count'] ?? 0),
        ));

        $this->line('[3/5] Consume deficit with leave balance');
        $leaveConsumptionResult = $attendanceDeficitLeaveConsumptionService->consumeByPeriodCode($periodCode, $empId);
        $this->line(sprintf(
            '      processed_count=%d, auto_leave_used_total=%.2f, deduction_day_total=%.2f',
            (int) ($leaveConsumptionResult['processed_count'] ?? 0),
            (float) ($leaveConsumptionResult['auto_leave_used_total'] ?? 0),
            (float) ($leaveConsumptionResult['deduction_day_total'] ?? 0),
        ));

        $this->line('[4/5] Rebuild payroll attendance results');
        $resultResult = $payrollAttendanceCalculatorService->calculateByPeriodCode($periodCode, $empId);
        $this->line(sprintf(
            '      employee_count=%d, upserted_count=%d',
            (int) ($resultResult['employee_count'] ?? 0),
            (int) ($resultResult['upserted_count'] ?? 0),
        ));

        $this->line('[5/5] Rebuild payroll attendance amounts');
        $amountResult = $payrollAttendanceAmountCalculatorService->calculateByPeriodCode($periodCode, $empId);
        $this->line(sprintf(
            '      employee_count=%d, upserted_count=%d',
            (int) ($amountResult['employee_count'] ?? 0),
            (int) ($amountResult['upserted_count'] ?? 0),
        ));

        $this->newLine();
        $this->info('Payroll pipeline rebuild completed successfully.');

        return self::SUCCESS;
    }
}