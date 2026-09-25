<?php

namespace App\Http\Controllers\Web\Summary;

use App\Domains\Master\Models\Branch;
use App\Domains\Scheduling\Models\PayrollPeriod;
use App\Domains\Summary\Models\AttendancePeriodSummary;
use App\Domains\Summary\Models\EmployeePeriodObligation;
use App\Domains\Summary\Models\PayrollAttendanceResult;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PayrollAttendanceResultController extends Controller
{
    public function index(Request $request): View
    {
        $query = PayrollAttendanceResult::query()
            ->with(['employee', 'branch', 'payrollPeriod', 'workPattern'])
            ->orderByDesc('calculated_at')
            ->orderByDesc('payroll_attendance_result_id');

        if ($request->filled('payroll_period_id')) {
            $query->where('payroll_period_id', (int) $request->input('payroll_period_id'));
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', (int) $request->input('branch_id'));
        }

        if ($request->filled('summary_basis_type_code')) {
            $query->where('summary_basis_type_code', $request->string('summary_basis_type_code')->toString());
        }

        if ($request->boolean('missing_amount_only')) {
            $query->whereNotExists(function ($q): void {
                $q->selectRaw('1')
                    ->from('payroll_attendance_amounts as paa')
                    ->whereColumn('paa.emp_id', 'payroll_attendance_results.emp_id')
                    ->whereColumn('paa.payroll_period_id', 'payroll_attendance_results.payroll_period_id');
            });
        }

        if ($request->filled('q')) {
            $keyword = trim((string) $request->input('q'));

            $query->whereHas('employee', function ($q) use ($keyword): void {
                $q->where('emp_code', 'ilike', "%{$keyword}%")
                    ->orWhere('full_name', 'ilike', "%{$keyword}%")
                    ->orWhere('biometric_code', 'ilike', "%{$keyword}%");
            });
        }

        $allowedBranchIds = $this->allowedBranchIds();

        if ($allowedBranchIds !== null) {
            if (!empty($allowedBranchIds)) {
                $query->whereIn('branch_id', $allowedBranchIds);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $statsBase = clone $query;

        $summaryStats = [
            'total_rows' => (clone $statsBase)->count(),
            'missing_amount_rows' => (clone $statsBase)->whereNotExists(function ($q): void {
                $q->selectRaw('1')
                    ->from('payroll_attendance_amounts as paa')
                    ->whereColumn('paa.emp_id', 'payroll_attendance_results.emp_id')
                    ->whereColumn('paa.payroll_period_id', 'payroll_attendance_results.payroll_period_id');
            })->count(),
            'overtime_min_payable_total' => (int) ((clone $statsBase)->sum('overtime_min_payable')),
            'deduction_day_payable_total' => (float) ((clone $statsBase)->sum('deduction_day_payable')),
        ];

        $rows = $query->paginate(20)->withQueryString();

        return view('summary.payroll.results.index', [
            'rows' => $rows,
            'summaryStats' => $summaryStats,
            'branches' => $this->availableBranches(),
            'payrollPeriods' => PayrollPeriod::query()->orderByDesc('period_start_date')->get(),
            'summaryBasisTypes' => collect(['HEK', 'OBLIGATION']),
        ]);
    }

    public function show(int $result): View
    {
        $row = PayrollAttendanceResult::query()
            ->with(['employee', 'branch', 'payrollPeriod', 'workPattern'])
            ->findOrFail($result);

        $this->authorizeBranch((int) $row->branch_id);

        $amountRow = DB::table('payroll_attendance_amounts')
            ->where('emp_id', $row->emp_id)
            ->where('payroll_period_id', $row->payroll_period_id)
            ->first();

        $periodSummary = AttendancePeriodSummary::query()
            ->with(['workPattern'])
            ->where('emp_id', $row->emp_id)
            ->where('payroll_period_id', $row->payroll_period_id)
            ->first();

        $obligations = EmployeePeriodObligation::query()
            ->with(['workPatternRule'])
            ->where('emp_id', $row->emp_id)
            ->where('payroll_period_id', $row->payroll_period_id)
            ->orderByDesc('employee_period_obligation_id')
            ->get();

        $period = $row->payrollPeriod;

        $dailyRows = collect();

        if ($period) {
            $dailyRows = DB::table('attendance_daily')
                ->where('emp_id', $row->emp_id)
                ->whereBetween('work_date', [$period->period_start_date, $period->period_end_date])
                ->orderByDesc('work_date')
                ->orderByDesc('attendance_daily_id')
                ->limit(100)
                ->get();
        }

        $auditStats = [
            'daily_overtime_total' => (int) $dailyRows->sum('overtime_min'),
            'daily_overtime_workday_total' => (int) $dailyRows->sum('overtime_workday_min'),
            'daily_overtime_holiday_total' => (int) $dailyRows->sum('overtime_holiday_min'),
            'daily_overtime_offday_total' => (int) $dailyRows->sum('overtime_offday_min'),
            'result_overtime_total' => (int) $row->overtime_min_payable,
            'result_deduction_day' => (float) $row->deduction_day_payable,
            'amount_exists' => $amountRow !== null,
        ];

        return view('summary.payroll.results.show', [
            'row' => $row,
            'amountRow' => $amountRow,
            'periodSummary' => $periodSummary,
            'obligations' => $obligations,
            'dailyRows' => $dailyRows,
            'auditStats' => $auditStats,
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

        if (!$user->hasBranchAccess($branchId)) {
            abort(403);
        }
    }
}