<?php

namespace App\Http\Controllers\Web\Summary;

use App\Http\Controllers\Controller;
use App\Domains\Master\Models\Branch;
use App\Domains\Summary\Models\AttendanceMonthlySummary;
use App\Domains\Summary\Models\AttendancePeriodSummary;
use App\Domains\Summary\Models\EmployeePeriodObligation;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AttendancePeriodSummaryController extends Controller
{
    public function index(Request $request): View
    {
        $query = AttendancePeriodSummary::query()
            ->with(['employee', 'branch', 'payrollPeriod', 'workPattern'])
            ->orderByDesc('attendance_period_summary_id');

        if ($request->filled('payroll_period_id')) {
            $query->where('payroll_period_id', (int) $request->input('payroll_period_id'));
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', (int) $request->input('branch_id'));
        }

        if ($request->filled('summary_basis_type_code')) {
            $query->where('summary_basis_type_code', $request->string('summary_basis_type_code')->toString());
        }

        if ($request->filled('q')) {
            $keyword = trim((string) $request->input('q'));

            $query->whereHas('employee', function ($q) use ($keyword): void {
                $q->where('emp_code', 'ilike', "%{$keyword}%")
                    ->orWhere('full_name', 'ilike', "%{$keyword}%")
                    ->orWhere('biometric_code', 'ilike', "%{$keyword}%");
            });
        }

        if ($request->filled('focus')) {
            $focus = $request->string('focus')->toString();

            if ($focus === 'deficit') {
                $query->where('deficit_count', '>', 0);
            }

            if ($focus === 'excess') {
                $query->where('excess_count', '>', 0);
            }

            if ($focus === 'deduction') {
                $query->where('deduction_day_count', '>', 0);
            }

            if ($focus === 'overtime') {
                $query->where('overtime_min_total', '>', 0);
            }

            if ($focus === 'late') {
                $query->where('late_count', '>', 0);
            }

            if ($focus === 'early_out') {
                $query->where('early_out_count', '>', 0);
            }

            if ($focus === 'incomplete') {
                $query->where('incomplete_count', '>', 0);
            }

            if ($focus === 'needs_attention') {
                $query->where(function ($q): void {
                    $q->where('deficit_count', '>', 0)
                        ->orWhere('deduction_day_count', '>', 0)
                        ->orWhere('late_count', '>', 0)
                        ->orWhere('early_out_count', '>', 0)
                        ->orWhere('incomplete_count', '>', 0);
                });
            }
        }

        $user = auth()->user();

        if ($user && !$user->hasRole('SUPER_ADMIN')) {
            $allowedBranchIds = $user->branchAccesses()->pluck('branches.branch_id')->all();

            if (!empty($allowedBranchIds)) {
                $query->whereIn('branch_id', $allowedBranchIds);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $rows = $query->paginate(20)->withQueryString();

        return view('summary.period.index', [
            'rows' => $rows,
            'branches' => $this->availableBranches(),
            'payrollPeriods' => DB::table('payroll_periods')
                ->orderByDesc('period_start_date')
                ->get(),
            'summaryBasisTypes' => collect(['HEK', 'OBLIGATION']),
        ]);
    }

    public function show(int $attendancePeriodSummary): View
    {
        $row = AttendancePeriodSummary::query()
            ->with(['employee', 'branch', 'payrollPeriod', 'workPattern'])
            ->findOrFail($attendancePeriodSummary);

        $this->authorizeBranch((int) $row->branch_id);

        $period = $row->payrollPeriod;

        $monthlySummary = null;
        $obligationRows = collect();
        $dailyRows = collect();

        if ($period) {
            $monthlySummary = AttendanceMonthlySummary::query()
                ->with(['branch'])
                ->where('emp_id', $row->emp_id)
                ->where('period_year', $period->payroll_year)
                ->where('period_month', $period->payroll_month)
                ->first();

            $obligationRows = EmployeePeriodObligation::query()
                ->with(['workPatternRule'])
                ->where('emp_id', $row->emp_id)
                ->where('payroll_period_id', $row->payroll_period_id)
                ->orderBy('obligation_type_code')
                ->orderBy('work_pattern_rule_id')
                ->get();

            $dailyRows = DB::table('attendance_daily as ad')
                ->leftJoin('shifts as s', 's.shift_id', '=', 'ad.shift_id')
                ->where('ad.emp_id', $row->emp_id)
                ->whereBetween('ad.work_date', [
                    $period->period_start_date,
                    $period->period_end_date,
                ])
                ->orderBy('ad.work_date')
                ->orderBy('ad.attendance_daily_id')
                ->select([
                    'ad.attendance_daily_id',
                    'ad.work_date',
                    'ad.attendance_status_code',
                    'ad.presence_type_code',
                    'ad.review_reason_code',
                    'ad.scheduled_in_datetime',
                    'ad.scheduled_out_datetime',
                    'ad.actual_in_datetime',
                    'ad.actual_out_datetime',
                    'ad.work_min',
                    'ad.late_min',
                    'ad.early_out_min',
                    'ad.overtime_min',
                    'ad.anomaly_flag',
                    'ad.exception_flag',
                    'ad.leave_flag',
                    'ad.notes',
                    DB::raw('COALESCE(s.shift_name, s.shift_code) as shift_label'),
                ])
                ->get();
        }

        $dailyStats = $this->buildDailyStats($dailyRows);

        $reportMetrics = [
            'hek_count' => (float) $row->hek_count,
            'valid_present_count' => (float) $row->valid_present_count,
            'deficit_count' => (float) $row->deficit_count,
            'excess_count' => (float) $row->excess_count,
            'overtime_day_count' => (float) $row->overtime_day_count,
            'overtime_min_total' => (int) $row->overtime_min_total,
            'leave_quota_used_count' => (float) $row->leave_quota_used_count,
            'deduction_day_count' => (float) $row->deduction_day_count,

            'present_days' => $monthlySummary ? (float) $monthlySummary->present_days : (float) $dailyStats['present_count'],
            'absent_days' => $monthlySummary ? (float) $monthlySummary->absent_days : (float) $dailyStats['absent_count'],
            'leave_days' => $monthlySummary ? (float) $monthlySummary->leave_days : (float) $dailyStats['leave_count'],
            'sick_days' => $monthlySummary ? (float) $monthlySummary->sick_days : 0,
            'permission_days' => $monthlySummary ? (float) $monthlySummary->permission_days : 0,
            'late_count' => $monthlySummary ? (int) $monthlySummary->late_count : (int) $dailyStats['late_count'],
            'late_min_total' => $monthlySummary ? (int) $monthlySummary->late_min_total : (int) $dailyStats['late_min_total'],
            'early_out_count' => $monthlySummary ? (int) $monthlySummary->early_out_count : (int) $dailyStats['early_out_count'],
            'early_out_min_total' => $monthlySummary ? (int) $monthlySummary->early_out_min_total : (int) $dailyStats['early_out_min_total'],
            'incomplete_count' => $monthlySummary ? (int) $monthlySummary->incomplete_count : (int) $dailyStats['incomplete_count'],
        ];

        $saturdayRows = $obligationRows
            ->filter(fn ($item) => $item->obligation_type_code === 'SATURDAY_MIN')
            ->values();

        $saturdayObligation = [
            'has_data' => $saturdayRows->isNotEmpty(),
            'required_count' => (float) $saturdayRows->sum(fn ($item) => (float) $item->required_count),
            'actual_count' => (float) $saturdayRows->sum(fn ($item) => (float) $item->actual_count),
            'excess_count' => (float) $saturdayRows->sum(fn ($item) => (float) $item->excess_count),
            'fulfilled_flag' => $saturdayRows->isNotEmpty()
                ? $saturdayRows->every(fn ($item) => (bool) $item->fulfilled_flag)
                : null,
            'rule_names' => $saturdayRows
                ->map(fn ($item) => $item->workPatternRule?->rule_name ?? $item->workPatternRule?->rule_code)
                ->filter()
                ->values(),
            'rows' => $saturdayRows,
        ];

        return view('summary.period.show', [
            'row' => $row,
            'monthlySummary' => $monthlySummary,
            'obligationRows' => $obligationRows,
            'saturdayObligation' => $saturdayObligation,
            'dailyRows' => $dailyRows,
            'dailyStats' => $dailyStats,
            'reportMetrics' => $reportMetrics,
        ]);
    }

    protected function buildDailyStats(Collection $dailyRows): array
    {
        return [
            'row_count' => $dailyRows->count(),
            'present_count' => $dailyRows->where('attendance_status_code', 'PRESENT')->count(),
            'absent_count' => $dailyRows->where('attendance_status_code', 'ABSENT')->count(),
            'leave_count' => $dailyRows->where('leave_flag', true)->count(),
            'anomaly_count' => $dailyRows->where('anomaly_flag', true)->count(),
            'exception_count' => $dailyRows->where('exception_flag', true)->count(),
            'late_count' => $dailyRows->filter(fn ($r) => (int) $r->late_min > 0)->count(),
            'late_min_total' => (int) $dailyRows->sum('late_min'),
            'early_out_count' => $dailyRows->filter(fn ($r) => (int) $r->early_out_min > 0)->count(),
            'early_out_min_total' => (int) $dailyRows->sum('early_out_min'),
            'overtime_count' => $dailyRows->filter(fn ($r) => (int) $r->overtime_min > 0)->count(),
            'overtime_min_total' => (int) $dailyRows->sum('overtime_min'),
            'incomplete_count' => $dailyRows->filter(function ($r) {
                return in_array($r->attendance_status_code, ['INCOMPLETE', 'MANUAL_REVIEW'], true)
                    || !empty($r->review_reason_code);
            })->count(),
            'reviewable_count' => $dailyRows->filter(function ($r) {
                return in_array($r->attendance_status_code, ['INCOMPLETE', 'MANUAL_REVIEW'], true)
                    || !empty($r->review_reason_code);
            })->count(),
        ];
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