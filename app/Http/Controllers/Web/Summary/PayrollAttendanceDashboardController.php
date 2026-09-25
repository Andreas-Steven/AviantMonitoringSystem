<?php

namespace App\Http\Controllers\Web\Summary;

use App\Domains\Master\Models\Branch;
use App\Domains\Scheduling\Models\PayrollPeriod;
use App\Domains\Summary\Services\PayrollAttendanceAmountCalculatorService;
use App\Domains\Summary\Services\PayrollAttendanceCalculatorService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PayrollAttendanceDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $payrollPeriods = PayrollPeriod::query()
            ->orderByDesc('period_start_date')
            ->get();

        $selectedPayrollPeriodId = $request->input('payroll_period_id')
            ?: $payrollPeriods->first()?->payroll_period_id;

        $selectedPeriod = $selectedPayrollPeriodId
            ? PayrollPeriod::query()->find($selectedPayrollPeriodId)
            : null;

        $selectedBranchId = $request->input('branch_id');

        $allowedBranchIds = $this->allowedBranchIds();

        $summaryBase = DB::table('attendance_period_summaries as aps');
        $resultBase = DB::table('payroll_attendance_results as par');
        $amountBase = DB::table('payroll_attendance_amounts as paa')
            ->leftJoin('payroll_attendance_results as par', function ($join): void {
                $join->on('par.emp_id', '=', 'paa.emp_id')
                    ->on('par.payroll_period_id', '=', 'paa.payroll_period_id');
            });

        if ($selectedPeriod) {
            $summaryBase->where('aps.payroll_period_id', $selectedPeriod->payroll_period_id);
            $resultBase->where('par.payroll_period_id', $selectedPeriod->payroll_period_id);
            $amountBase->where('paa.payroll_period_id', $selectedPeriod->payroll_period_id);
        }

        if ($selectedBranchId) {
            $summaryBase->where('aps.branch_id', (int) $selectedBranchId);
            $resultBase->where('par.branch_id', (int) $selectedBranchId);
            $amountBase->where('par.branch_id', (int) $selectedBranchId);
        }

        if ($allowedBranchIds !== null) {
            $summaryBase->whereIn('aps.branch_id', $allowedBranchIds);
            $resultBase->whereIn('par.branch_id', $allowedBranchIds);
            $amountBase->whereIn('par.branch_id', $allowedBranchIds);
        }

        $summaryCount = (clone $summaryBase)->count();
        $resultCount = (clone $resultBase)->count();
        $amountCount = (clone $amountBase)->count('paa.payroll_attendance_amount_id');

        $missingResultCount = DB::table('attendance_period_summaries as aps')
            ->leftJoin('payroll_attendance_results as par', function ($join): void {
                $join->on('par.emp_id', '=', 'aps.emp_id')
                    ->on('par.payroll_period_id', '=', 'aps.payroll_period_id');
            })
            ->when($selectedPeriod, fn ($q) => $q->where('aps.payroll_period_id', $selectedPeriod->payroll_period_id))
            ->when($selectedBranchId, fn ($q) => $q->where('aps.branch_id', (int) $selectedBranchId))
            ->when($allowedBranchIds !== null, fn ($q) => $q->whereIn('aps.branch_id', $allowedBranchIds))
            ->whereNull('par.payroll_attendance_result_id')
            ->count();

        $missingAmountCount = DB::table('payroll_attendance_results as par')
            ->leftJoin('payroll_attendance_amounts as paa', function ($join): void {
                $join->on('paa.emp_id', '=', 'par.emp_id')
                    ->on('paa.payroll_period_id', '=', 'par.payroll_period_id');
            })
            ->when($selectedPeriod, fn ($q) => $q->where('par.payroll_period_id', $selectedPeriod->payroll_period_id))
            ->when($selectedBranchId, fn ($q) => $q->where('par.branch_id', (int) $selectedBranchId))
            ->when($allowedBranchIds !== null, fn ($q) => $q->whereIn('par.branch_id', $allowedBranchIds))
            ->whereNull('paa.payroll_attendance_amount_id')
            ->count();

        $orphanAmountCount = DB::table('payroll_attendance_amounts as paa')
            ->leftJoin('payroll_attendance_results as par', function ($join): void {
                $join->on('par.emp_id', '=', 'paa.emp_id')
                    ->on('par.payroll_period_id', '=', 'paa.payroll_period_id');
            })
            ->when($selectedPeriod, fn ($q) => $q->where('paa.payroll_period_id', $selectedPeriod->payroll_period_id))
            ->whereNull('par.payroll_attendance_result_id')
            ->count();

        $amountTotals = (clone $amountBase)
            ->selectRaw('
                COALESCE(SUM(paa.overtime_amount), 0) as overtime_amount_total,
                COALESCE(SUM(paa.deduction_amount), 0) as deduction_amount_total,
                COALESCE(SUM(paa.net_attendance_amount), 0) as net_attendance_amount_total
            ')
            ->first();

        $resultTotals = (clone $resultBase)
            ->selectRaw('
                COALESCE(SUM(par.overtime_min_payable), 0) as overtime_min_payable_total,
                COALESCE(SUM(par.overtime_workday_min_payable), 0) as overtime_workday_min_total,
                COALESCE(SUM(par.overtime_holiday_min_payable), 0) as overtime_holiday_min_total,
                COALESCE(SUM(par.overtime_offday_min_payable), 0) as overtime_offday_min_total,
                COALESCE(SUM(par.deduction_day_payable), 0) as deduction_day_payable_total
            ')
            ->first();

        $branchCoverage = DB::table('payroll_attendance_results as par')
            ->join('branches as b', 'b.branch_id', '=', 'par.branch_id')
            ->when($selectedPeriod, fn ($q) => $q->where('par.payroll_period_id', $selectedPeriod->payroll_period_id))
            ->when($selectedBranchId, fn ($q) => $q->where('par.branch_id', (int) $selectedBranchId))
            ->when($allowedBranchIds !== null, fn ($q) => $q->whereIn('par.branch_id', $allowedBranchIds))
            ->leftJoin('payroll_attendance_amounts as paa', function ($join): void {
                $join->on('paa.emp_id', '=', 'par.emp_id')
                    ->on('paa.payroll_period_id', '=', 'par.payroll_period_id');
            })
            ->groupBy('b.branch_name')
            ->orderBy('b.branch_name')
            ->selectRaw('
                b.branch_name,
                COUNT(par.payroll_attendance_result_id) as result_count,
                COUNT(paa.payroll_attendance_amount_id) as amount_count,
                COALESCE(SUM(par.overtime_min_payable), 0) as overtime_min_payable_total,
                COALESCE(SUM(paa.net_attendance_amount), 0) as net_attendance_amount_total
            ')
            ->get();

        $recentRows = DB::table('payroll_attendance_results as par')
            ->join('employees as e', 'e.emp_id', '=', 'par.emp_id')
            ->join('branches as b', 'b.branch_id', '=', 'par.branch_id')
            ->join('payroll_periods as pp', 'pp.payroll_period_id', '=', 'par.payroll_period_id')
            ->leftJoin('payroll_attendance_amounts as paa', function ($join): void {
                $join->on('paa.emp_id', '=', 'par.emp_id')
                    ->on('paa.payroll_period_id', '=', 'par.payroll_period_id');
            })
            ->when($selectedPeriod, fn ($q) => $q->where('par.payroll_period_id', $selectedPeriod->payroll_period_id))
            ->when($selectedBranchId, fn ($q) => $q->where('par.branch_id', (int) $selectedBranchId))
            ->when($allowedBranchIds !== null, fn ($q) => $q->whereIn('par.branch_id', $allowedBranchIds))
            ->orderByDesc('par.calculated_at')
            ->orderByDesc('par.payroll_attendance_result_id')
            ->limit(12)
            ->select([
                'par.payroll_attendance_result_id',
                'par.emp_id',
                'par.payroll_period_id',
                'pp.period_code',
                'e.emp_code',
                'e.full_name',
                'b.branch_name',
                'par.summary_basis_type_code',
                'par.overtime_min_payable',
                'par.deduction_day_payable',
                'paa.payroll_attendance_amount_id',
                'paa.net_attendance_amount',
                'par.calculated_at',
            ])
            ->get();

        return view('summary.payroll.dashboard', [
            'payrollPeriods' => $payrollPeriods,
            'branches' => $this->availableBranches(),
            'selectedPeriod' => $selectedPeriod,
            'selectedBranchId' => $selectedBranchId,
            'stats' => [
                'summary_count' => $summaryCount,
                'result_count' => $resultCount,
                'amount_count' => $amountCount,
                'missing_result_count' => $missingResultCount,
                'missing_amount_count' => $missingAmountCount,
                'orphan_amount_count' => $orphanAmountCount,
                'overtime_min_payable_total' => (int) ($resultTotals->overtime_min_payable_total ?? 0),
                'overtime_workday_min_total' => (int) ($resultTotals->overtime_workday_min_total ?? 0),
                'overtime_holiday_min_total' => (int) ($resultTotals->overtime_holiday_min_total ?? 0),
                'overtime_offday_min_total' => (int) ($resultTotals->overtime_offday_min_total ?? 0),
                'deduction_day_payable_total' => (float) ($resultTotals->deduction_day_payable_total ?? 0),
                'overtime_amount_total' => (float) ($amountTotals->overtime_amount_total ?? 0),
                'deduction_amount_total' => (float) ($amountTotals->deduction_amount_total ?? 0),
                'net_attendance_amount_total' => (float) ($amountTotals->net_attendance_amount_total ?? 0),
            ],
            'branchCoverage' => $branchCoverage,
            'recentRows' => $recentRows,
        ]);
    }

    public function calculateResults(
        Request $request,
        PayrollAttendanceCalculatorService $calculatorService
    ): RedirectResponse {
        $period = $this->resolvePeriod($request);

        $result = $calculatorService->calculateByPeriodCode($period->period_code);

        return redirect()
            ->route('summary.payroll.dashboard', ['payroll_period_id' => $period->payroll_period_id])
            ->with('success', sprintf(
                'Payroll result calculation selesai. Period: %s. Employee count: %d. Upserted: %d.',
                $period->period_code,
                (int) ($result['employee_count'] ?? 0),
                (int) ($result['upserted_count'] ?? 0),
            ));
    }

    public function calculateAmounts(
        Request $request,
        PayrollAttendanceAmountCalculatorService $calculatorService
    ): RedirectResponse {
        $period = $this->resolvePeriod($request);

        $result = $calculatorService->calculateByPeriodCode($period->period_code);

        return redirect()
            ->route('summary.payroll.dashboard', ['payroll_period_id' => $period->payroll_period_id])
            ->with('success', sprintf(
                'Payroll amount calculation selesai. Period: %s. Employee count: %d. Upserted: %d.',
                $period->period_code,
                (int) ($result['employee_count'] ?? 0),
                (int) ($result['upserted_count'] ?? 0),
            ));
    }

    protected function resolvePeriod(Request $request): PayrollPeriod
    {
        $validated = $request->validate([
            'payroll_period_id' => ['required', 'integer', 'exists:payroll_periods,payroll_period_id'],
        ]);

        return PayrollPeriod::query()->findOrFail((int) $validated['payroll_period_id']);
    }

    protected function availableBranches()
    {
        $user = auth()->user();

        if ($user && $user->hasRole('SUPER_ADMIN')) {
            return Branch::query()->where('active', true)->orderBy('branch_name')->get();
        }

        return $user
            ? $user->branchAccesses()->where('branches.active', true)->orderBy('branch_name')->get()
            : collect();
    }

    protected function allowedBranchIds(): ?array
    {
        $user = auth()->user();

        if (!$user || $user->hasRole('SUPER_ADMIN')) {
            return null;
        }

        return $user->branchAccesses()->pluck('branches.branch_id')->all();
    }
}