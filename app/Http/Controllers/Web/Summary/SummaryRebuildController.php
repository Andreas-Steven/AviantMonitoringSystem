<?php

namespace App\Http\Controllers\Web\Summary;

use App\Http\Controllers\Controller;
use App\Domains\Summary\Services\AttendanceDeficitLeaveConsumptionService;
use App\Domains\Summary\Services\MonthlySummaryBuilderService;
use App\Domains\Summary\Services\PeriodSummaryBuilderService;
use App\Domains\Summary\Services\PeriodObligationBuilderService;
use App\Domains\Summary\Services\PayrollAttendanceCalculatorService;
use App\Domains\Summary\Services\PayrollAttendanceAmountCalculatorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SummaryRebuildController extends Controller
{
    public function index()
    {
        $payrollPeriods = DB::table('payroll_periods')
            ->orderByDesc('period_start_date')
            ->get();

        return view('summary.rebuild.index', compact('payrollPeriods'));
    }

    public function rebuildMonthly(
        Request $request,
        MonthlySummaryBuilderService $monthlySummaryBuilderService
    ): RedirectResponse {
        $period = $this->resolvePayrollPeriod($request);

        $monthlySummaryBuilderService->buildByPeriodCode($period->period_code);

        return redirect()
            ->route('summary.rebuild.index')
            ->with('success', "Monthly summary rebuilt. Period: {$period->period_code}");
    }

    public function rebuildPeriod(
        Request $request,
        PeriodObligationBuilderService $periodObligationBuilderService,
        PeriodSummaryBuilderService $periodSummaryBuilderService,
        AttendanceDeficitLeaveConsumptionService $attendanceDeficitLeaveConsumptionService
    ): RedirectResponse {
        $period = $this->resolvePayrollPeriod($request);

        $periodObligationBuilderService->buildByPeriodCode($period->period_code);
        $periodSummaryBuilderService->buildByPeriodCode($period->period_code);
        $attendanceDeficitLeaveConsumptionService->consumeByPeriodCode($period->period_code);

        return redirect()
            ->route('summary.rebuild.index')
            ->with('success', "Period summary rebuilt. Period: {$period->period_code}");
    }

    public function rebuildObligation(
        Request $request,
        PeriodObligationBuilderService $periodObligationBuilderService
    ): RedirectResponse {
        $period = $this->resolvePayrollPeriod($request);

        $periodObligationBuilderService->buildByPeriodCode($period->period_code);

        return redirect()
            ->route('summary.rebuild.index')
            ->with('success', "Obligation rebuilt. Period: {$period->period_code}");
    }

    public function rebuildPayroll(
        Request $request,
        PeriodObligationBuilderService $periodObligationBuilderService,
        PeriodSummaryBuilderService $periodSummaryBuilderService,
        AttendanceDeficitLeaveConsumptionService $attendanceDeficitLeaveConsumptionService,
        PayrollAttendanceCalculatorService $payrollAttendanceCalculatorService,
        PayrollAttendanceAmountCalculatorService $payrollAttendanceAmountCalculatorService
    ): RedirectResponse {
        $period = $this->resolvePayrollPeriod($request);

        $periodObligationBuilderService->buildByPeriodCode($period->period_code);
        $periodSummaryBuilderService->buildByPeriodCode($period->period_code);
        $attendanceDeficitLeaveConsumptionService->consumeByPeriodCode($period->period_code);
        $payrollAttendanceCalculatorService->calculateByPeriodCode($period->period_code);
        $payrollAttendanceAmountCalculatorService->calculateByPeriodCode($period->period_code);

        return redirect()
            ->route('summary.rebuild.index')
            ->with('success', "Payroll pipeline rebuilt. Period: {$period->period_code}");
    }

    protected function resolvePayrollPeriod(Request $request): object
    {
        $request->validate([
            'payroll_period_id' => ['required', 'integer'],
        ]);

        $period = DB::table('payroll_periods')
            ->where('payroll_period_id', (int) $request->input('payroll_period_id'))
            ->first();

        if (!$period) {
            throw new RuntimeException('Payroll period not found.');
        }

        return $period;
    }
}