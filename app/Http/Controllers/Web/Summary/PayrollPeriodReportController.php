<?php

namespace App\Http\Controllers\Web\Summary;

use App\Http\Controllers\Controller;
use App\Domains\Master\Models\Branch;
use App\Domains\Summary\Models\AttendanceMonthlySummary;
use App\Domains\Summary\Models\AttendancePeriodSummary;
use App\Domains\Summary\Models\EmployeePeriodObligation;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PayrollPeriodReportController extends Controller
{
    public function index(Request $request): View
    {
        $query = AttendancePeriodSummary::query()
            ->select('attendance_period_summaries.*')
            ->with(['employee', 'branch', 'payrollPeriod', 'workPattern'])
            ->addSelect([
                'obligation_total_count' => DB::table('employee_period_obligations as epo')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('epo.emp_id', 'attendance_period_summaries.emp_id')
                    ->whereColumn('epo.payroll_period_id', 'attendance_period_summaries.payroll_period_id'),
                'obligation_unfulfilled_count' => DB::table('employee_period_obligations as epo')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('epo.emp_id', 'attendance_period_summaries.emp_id')
                    ->whereColumn('epo.payroll_period_id', 'attendance_period_summaries.payroll_period_id')
                    ->where('epo.fulfilled_flag', false),
                'daily_anomaly_count' => DB::table('attendance_daily as ad')
                    ->join('payroll_periods as pp', 'pp.payroll_period_id', '=', 'attendance_period_summaries.payroll_period_id')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('ad.emp_id', 'attendance_period_summaries.emp_id')
                    ->whereRaw('ad.work_date between pp.period_start_date and pp.period_end_date')
                    ->where(function (Builder $q): void {
                        $q->where('ad.anomaly_flag', true)
                            ->orWhere('ad.exception_flag', true)
                            ->orWhereNotNull('ad.review_reason_code')
                            ->orWhereIn('ad.attendance_status_code', ['INCOMPLETE', 'MANUAL_REVIEW']);
                    }),
                'daily_late_count' => DB::table('attendance_daily as ad')
                    ->join('payroll_periods as pp', 'pp.payroll_period_id', '=', 'attendance_period_summaries.payroll_period_id')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('ad.emp_id', 'attendance_period_summaries.emp_id')
                    ->whereRaw('ad.work_date between pp.period_start_date and pp.period_end_date')
                    ->where('ad.late_min', '>', 0),
            ])
            ->orderByDesc('attendance_period_summaries.attendance_period_summary_id');

        if ($request->filled('payroll_period_id')) {
            $query->where('attendance_period_summaries.payroll_period_id', (int) $request->input('payroll_period_id'));
        }

        if ($request->filled('branch_id')) {
            $query->where('attendance_period_summaries.branch_id', (int) $request->input('branch_id'));
        }

        if ($request->filled('summary_basis_type_code')) {
            $query->where(
                'attendance_period_summaries.summary_basis_type_code',
                $request->string('summary_basis_type_code')->toString()
            );
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

            if ($focus === 'needs_attention') {
                $query->where(function ($q): void {
                    $q->where('attendance_period_summaries.deficit_count', '>', 0)
                        ->orWhere('attendance_period_summaries.deduction_day_count', '>', 0)
                        ->orWhereExists(function ($sub): void {
                            $sub->selectRaw('1')
                                ->from('employee_period_obligations as epo')
                                ->whereColumn('epo.emp_id', 'attendance_period_summaries.emp_id')
                                ->whereColumn('epo.payroll_period_id', 'attendance_period_summaries.payroll_period_id')
                                ->where('epo.fulfilled_flag', false);
                        })
                        ->orWhereExists(function ($sub): void {
                            $sub->selectRaw('1')
                                ->from('attendance_daily as ad')
                                ->join('payroll_periods as pp', 'pp.payroll_period_id', '=', 'attendance_period_summaries.payroll_period_id')
                                ->whereColumn('ad.emp_id', 'attendance_period_summaries.emp_id')
                                ->whereRaw('ad.work_date between pp.period_start_date and pp.period_end_date')
                                ->where(function ($inner): void {
                                    $inner->where('ad.anomaly_flag', true)
                                        ->orWhere('ad.exception_flag', true)
                                        ->orWhereNotNull('ad.review_reason_code')
                                        ->orWhereIn('ad.attendance_status_code', ['INCOMPLETE', 'MANUAL_REVIEW']);
                                });
                        });
                });
            }

            if ($focus === 'deficit') {
                $query->where('attendance_period_summaries.deficit_count', '>', 0);
            }

            if ($focus === 'deduction') {
                $query->where('attendance_period_summaries.deduction_day_count', '>', 0);
            }

            if ($focus === 'overtime') {
                $query->where('attendance_period_summaries.overtime_min_total', '>', 0);
            }

            if ($focus === 'unfulfilled_obligation') {
                $query->whereExists(function ($sub): void {
                    $sub->selectRaw('1')
                        ->from('employee_period_obligations as epo')
                        ->whereColumn('epo.emp_id', 'attendance_period_summaries.emp_id')
                        ->whereColumn('epo.payroll_period_id', 'attendance_period_summaries.payroll_period_id')
                        ->where('epo.fulfilled_flag', false);
                });
            }
        }

        $user = auth()->user();

        if ($user && !$user->hasRole('SUPER_ADMIN')) {
            $allowedBranchIds = $user->branchAccesses()->pluck('branches.branch_id')->all();

            if (!empty($allowedBranchIds)) {
                $query->whereIn('attendance_period_summaries.branch_id', $allowedBranchIds);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $statsQuery = clone $query;

        $summaryStats = [
            'total_rows' => (clone $statsQuery)->count(),
            'deficit_rows' => (clone $statsQuery)->where('attendance_period_summaries.deficit_count', '>', 0)->count(),
            'deduction_rows' => (clone $statsQuery)->where('attendance_period_summaries.deduction_day_count', '>', 0)->count(),
            'overtime_rows' => (clone $statsQuery)->where('attendance_period_summaries.overtime_min_total', '>', 0)->count(),
            'unfulfilled_obligation_rows' => (clone $statsQuery)->whereExists(function ($sub): void {
                $sub->selectRaw('1')
                    ->from('employee_period_obligations as epo')
                    ->whereColumn('epo.emp_id', 'attendance_period_summaries.emp_id')
                    ->whereColumn('epo.payroll_period_id', 'attendance_period_summaries.payroll_period_id')
                    ->where('epo.fulfilled_flag', false);
            })->count(),
            'overtime_min_total' => (int) (clone $statsQuery)->sum('attendance_period_summaries.overtime_min_total'),
            'deduction_day_total' => (float) (clone $statsQuery)->sum('attendance_period_summaries.deduction_day_count'),
        ];

        $rows = $query->paginate(20)->withQueryString();

        return view('summary.payroll.reports.index', [
            'rows' => $rows,
            'summaryStats' => $summaryStats,
            'branches' => $this->availableBranches(),
            'payrollPeriods' => DB::table('payroll_periods')
                ->orderByDesc('period_start_date')
                ->get(),
            'summaryBasisTypes' => collect(['HEK', 'OBLIGATION']),
            'focusOptions' => collect([
                'needs_attention' => 'Needs attention',
                'deficit' => 'Has deficit',
                'deduction' => 'Has deduction',
                'overtime' => 'Has overtime',
                'unfulfilled_obligation' => 'Unfulfilled obligation',
            ]),
        ]);
    }

    public function show(int $report): View
    {
        $row = AttendancePeriodSummary::query()
            ->with(['employee', 'branch', 'payrollPeriod', 'workPattern'])
            ->findOrFail($report);

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
                    'ad.overtime_workday_min',
                    'ad.overtime_holiday_min',
                    'ad.overtime_offday_min',
                    'ad.anomaly_flag',
                    'ad.exception_flag',
                    'ad.leave_flag',
                    'ad.notes',
                    DB::raw('COALESCE(s.shift_name, s.shift_code) as shift_label'),
                ])
                ->get();
        }

        $dailyStats = $this->buildDailyStats($dailyRows);

        $obligationSummary = [
            'total_rows' => $obligationRows->count(),
            'fulfilled_rows' => $obligationRows->where('fulfilled_flag', true)->count(),
            'unfulfilled_rows' => $obligationRows->where('fulfilled_flag', false)->count(),
            'required_total' => (float) $obligationRows->sum(fn ($item) => (float) $item->required_count),
            'actual_total' => (float) $obligationRows->sum(fn ($item) => (float) $item->actual_count),
            'excess_total' => (float) $obligationRows->sum(fn ($item) => (float) $item->excess_count),
        ];

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

        $amount = \DB::table('payroll_attendance_amounts')
            ->where('emp_id', $row->emp_id)
            ->where('payroll_period_id', $row->payroll_period_id)
            ->first();

        $primaryMetrics = [];
        $secondaryMetrics = [];

        if ($row->summary_basis_type_code === 'HEK') {
            $primaryMetrics = [
                ['label' => 'HEK', 'value' => $reportMetrics['hek_count']],
                ['label' => 'Valid Present', 'value' => $reportMetrics['valid_present_count']],
                ['label' => 'Deficit', 'value' => $reportMetrics['deficit_count']],
                ['label' => 'Excess', 'value' => $reportMetrics['excess_count']],
                ['label' => 'Deduction Days', 'value' => $row->deduction_day_count],
                ['label' => 'OT Minutes', 'value' => $reportMetrics['overtime_min_total']],
            ];

            $secondaryMetrics = [
                ['label' => 'Present Days', 'value' => $reportMetrics['present_days']],
                ['label' => 'Late Count', 'value' => $reportMetrics['late_count']],
                ['label' => 'Incomplete', 'value' => $reportMetrics['incomplete_count']],
                ['label' => 'Leave Quota Used', 'value' => $row->leave_quota_used_count],
            ];
        } else {
            $primaryMetrics = [
                ['label' => 'Required', 'value' => $obligationSummary['required_total']],
                ['label' => 'Actual', 'value' => $obligationSummary['actual_total']],
                ['label' => 'Unfulfilled Rules', 'value' => $obligationSummary['unfulfilled_rows']],
                ['label' => 'Deduction Days', 'value' => $row->deduction_day_count],
                ['label' => 'OT Minutes', 'value' => $reportMetrics['overtime_min_total']],
            ];

            $secondaryMetrics = [
                ['label' => 'HEK (Info)', 'value' => $reportMetrics['hek_count']],
                ['label' => 'Valid Present (Info)', 'value' => $reportMetrics['valid_present_count']],
                ['label' => 'Present Days', 'value' => $reportMetrics['present_days']],
                ['label' => 'Late Count', 'value' => $reportMetrics['late_count']],
            ];
        }

        $attentionFlags = collect([
            [
                'label' => 'Has deficit',
                'active' => (float) $row->deficit_count > 0,
                'tone' => 'warning',
            ],
            [
                'label' => 'Has deduction',
                'active' => (float) $row->deduction_day_count > 0,
                'tone' => 'danger',
            ],
            [
                'label' => 'Has overtime',
                'active' => (int) $row->overtime_min_total > 0,
                'tone' => 'success',
            ],
            [
                'label' => 'Unfulfilled obligation',
                'active' => $obligationSummary['unfulfilled_rows'] > 0,
                'tone' => 'danger',
            ],
            [
                'label' => 'Daily anomaly / review',
                'active' => ($dailyStats['anomaly_count'] + $dailyStats['exception_count'] + $dailyStats['review_count']) > 0,
                'tone' => 'warning',
            ],
        ]);

        return view('summary.payroll.reports.show', [
            'row' => $row,
            'monthlySummary' => $monthlySummary,
            'obligationRows' => $obligationRows,
            'obligationSummary' => $obligationSummary,
            'primaryMetrics' => $primaryMetrics,
            'secondaryMetrics' => $secondaryMetrics,
            'dailyRows' => $dailyRows,
            'dailyStats' => $dailyStats,
            'reportMetrics' => $reportMetrics,
            'attentionFlags' => $attentionFlags,
            'amount' => $amount,
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
            'review_count' => $dailyRows->filter(function ($r) {
                return !empty($r->review_reason_code)
                    || in_array($r->attendance_status_code, ['INCOMPLETE', 'MANUAL_REVIEW'], true);
            })->count(),
            'late_count' => $dailyRows->filter(fn ($r) => (int) $r->late_min > 0)->count(),
            'late_min_total' => (int) $dailyRows->sum('late_min'),
            'early_out_count' => $dailyRows->filter(fn ($r) => (int) $r->early_out_min > 0)->count(),
            'early_out_min_total' => (int) $dailyRows->sum('early_out_min'),
            'overtime_count' => $dailyRows->filter(fn ($r) => (int) $r->overtime_min > 0)->count(),
            'overtime_min_total' => (int) $dailyRows->sum('overtime_min'),
            'overtime_workday_min_total' => (int) $dailyRows->sum('overtime_workday_min'),
            'overtime_holiday_min_total' => (int) $dailyRows->sum('overtime_holiday_min'),
            'overtime_offday_min_total' => (int) $dailyRows->sum('overtime_offday_min'),
            'incomplete_count' => $dailyRows->filter(function ($r) {
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