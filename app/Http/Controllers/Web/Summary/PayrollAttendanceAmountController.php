<?php

namespace App\Http\Controllers\Web\Summary;

use App\Domains\Master\Models\Branch;
use App\Domains\Scheduling\Models\PayrollPeriod;
use App\Domains\Summary\Models\PayrollAttendanceAmount;
use App\Domains\Summary\Services\PayrollRatePolicyResolverService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PayrollAttendanceAmountController extends Controller
{
    public function __construct(
        protected PayrollRatePolicyResolverService $policyResolver,
    ) {}

    public function index(Request $request): View
    {
        $query = DB::table('payroll_attendance_amounts as paa')
            ->join('employees as e', 'e.emp_id', '=', 'paa.emp_id')
            ->join('payroll_periods as pp', 'pp.payroll_period_id', '=', 'paa.payroll_period_id')
            ->leftJoin('payroll_attendance_results as par', function ($join): void {
                $join->on('par.emp_id', '=', 'paa.emp_id')
                    ->on('par.payroll_period_id', '=', 'paa.payroll_period_id');
            })
            ->leftJoin('branches as b', 'b.branch_id', '=', 'par.branch_id')
            ->orderByDesc('paa.calculated_at')
            ->orderByDesc('paa.payroll_attendance_amount_id')
            ->select([
                'paa.*',
                'e.emp_code',
                'e.full_name',
                'pp.period_code',
                'par.branch_id',
                'par.summary_basis_type_code',
                'b.branch_name',
            ]);

        if ($request->filled('payroll_period_id')) {
            $query->where('paa.payroll_period_id', (int) $request->input('payroll_period_id'));
        }

        if ($request->filled('branch_id')) {
            $query->where('par.branch_id', (int) $request->input('branch_id'));
        }

        if ($request->boolean('negative_net_only')) {
            $query->where('paa.net_attendance_amount', '<', 0);
        }

        if ($request->filled('q')) {
            $keyword = trim((string) $request->input('q'));

            $query->where(function ($q) use ($keyword): void {
                $q->where('e.emp_code', 'ilike', "%{$keyword}%")
                    ->orWhere('e.full_name', 'ilike', "%{$keyword}%");
            });
        }

        $allowedBranchIds = $this->allowedBranchIds();

        if ($allowedBranchIds !== null) {
            if (!empty($allowedBranchIds)) {
                $query->whereIn('par.branch_id', $allowedBranchIds);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $statsBase = clone $query;

        $summaryStats = [
            'total_rows' => (clone $statsBase)->count(),
            'overtime_amount_total' => (float) ((clone $statsBase)->sum('paa.overtime_amount')),
            'deduction_amount_total' => (float) ((clone $statsBase)->sum('paa.deduction_amount')),
            'net_attendance_amount_total' => (float) ((clone $statsBase)->sum('paa.net_attendance_amount')),
        ];

        $rows = $query->paginate(20)->withQueryString();

        return view('summary.payroll.amounts.index', [
            'rows' => $rows,
            'summaryStats' => $summaryStats,
            'branches' => $this->availableBranches(),
            'payrollPeriods' => PayrollPeriod::query()->orderByDesc('period_start_date')->get(),
        ]);
    }

    public function show(int $amount): View
    {
        $row = PayrollAttendanceAmount::query()
            ->with(['employee', 'payrollPeriod'])
            ->findOrFail($amount);

        $resultRow = DB::table('payroll_attendance_results')
            ->where('emp_id', $row->emp_id)
            ->where('payroll_period_id', $row->payroll_period_id)
            ->first();

        $branchId = (int) ($resultRow->branch_id ?? 0);
        $this->authorizeBranch($branchId);

        $period = $row->payrollPeriod;

        $policy = null;

        if ($period && $resultRow) {
            $policy = $this->policyResolver->resolve(
                branchId: (int) $resultRow->branch_id,
                summaryBasisTypeCode: (string) $resultRow->summary_basis_type_code,
                dateFrom: $period->period_start_date->toDateString(),
                dateTo: $period->period_end_date->toDateString(),
            );
        }

        $dailyRows = collect();

        if ($period) {
            $dailyRows = DB::table('attendance_daily')
                ->where('emp_id', $row->emp_id)
                ->whereBetween('work_date', [$period->period_start_date, $period->period_end_date])
                ->where('overtime_min', '>', 0)
                ->orderByDesc('work_date')
                ->orderByDesc('attendance_daily_id')
                ->limit(100)
                ->get();
        }

        $expectedOvertimeAmount = null;
        $expectedDeductionAmount = null;
        $expectedNetAmount = null;

        if ($policy && $resultRow) {
            $expectedOvertimeAmount = round(
                ((int) ($resultRow->overtime_workday_min_payable ?? 0) * (float) $policy->overtime_rate_per_min * (float) $policy->workday_ot_multiplier)
                + ((int) ($resultRow->overtime_holiday_min_payable ?? 0) * (float) $policy->overtime_rate_per_min * (float) $policy->holiday_ot_multiplier)
                + ((int) ($resultRow->overtime_offday_min_payable ?? 0) * (float) $policy->overtime_rate_per_min * (float) $policy->offday_ot_multiplier),
                2
            );

            $expectedDeductionAmount = round(
                (float) ($row->deduction_day_payable ?? 0) * (float) $policy->deduction_rate_per_day,
                2
            );

            $expectedNetAmount = round($expectedOvertimeAmount - $expectedDeductionAmount, 2);
        }

        return view('summary.payroll.amounts.show', [
            'row' => $row,
            'resultRow' => $resultRow,
            'policy' => $policy,
            'dailyRows' => $dailyRows,
            'expectedOvertimeAmount' => $expectedOvertimeAmount,
            'expectedDeductionAmount' => $expectedDeductionAmount,
            'expectedNetAmount' => $expectedNetAmount,
        ]);
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

    protected function authorizeBranch(int $branchId): void
    {
        $user = auth()->user();

        if (!$user || $user->hasRole('SUPER_ADMIN')) {
            return;
        }

        if (!$branchId || !$user->hasBranchAccess($branchId)) {
            abort(403);
        }
    }
}