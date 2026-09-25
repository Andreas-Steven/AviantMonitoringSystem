<?php

namespace App\Http\Controllers\Web\Summary;

use App\Http\Controllers\Controller;
use App\Domains\Master\Models\Branch;
use App\Domains\Summary\Models\EmployeePeriodObligation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EmployeePeriodObligationController extends Controller
{
    public function index(Request $request): View
    {
        $query = EmployeePeriodObligation::query()
            ->with(['employee', 'payrollPeriod', 'workPatternRule'])
            ->orderByDesc('employee_period_obligation_id');

        if ($request->filled('payroll_period_id')) {
            $query->where('payroll_period_id', (int) $request->input('payroll_period_id'));
        }

        if ($request->filled('obligation_type_code')) {
            $query->where('obligation_type_code', $request->string('obligation_type_code')->toString());
        }

        if ($request->filled('fulfilled_flag')) {
            $fulfilled = $request->input('fulfilled_flag');

            if ($fulfilled === '1' || $fulfilled === '0') {
                $query->where('fulfilled_flag', $fulfilled === '1');
            }
        }

        if ($request->filled('q')) {
            $keyword = trim((string) $request->input('q'));

            $query->whereHas('employee', function ($q) use ($keyword): void {
                $q->where('emp_code', 'ilike', "%{$keyword}%")
                    ->orWhere('full_name', 'ilike', "%{$keyword}%")
                    ->orWhere('biometric_code', 'ilike', "%{$keyword}%");
            });
        }

        $user = auth()->user();

        if ($user && !$user->hasRole('SUPER_ADMIN')) {
            $allowedBranchIds = $user->branchAccesses()->pluck('branches.branch_id')->all();

            if (!empty($allowedBranchIds)) {
                $query->whereHas('employee.assignments', function ($q) use ($allowedBranchIds): void {
                    $q->whereIn('branch_id', $allowedBranchIds)
                        ->where(function ($sub): void {
                            $sub->whereNull('effective_end_date')
                                ->orWhereDate('effective_end_date', '>=', now()->toDateString());
                        });
                });
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $rows = $query->paginate(20)->withQueryString();

        return view('summary.obligations.index', [
            'rows' => $rows,
            'payrollPeriods' => DB::table('payroll_periods')
                ->orderByDesc('period_start_date')
                ->get(),
            'obligationTypes' => collect([
                'SATURDAY_MIN',
                'CUSTOM_PERIODIC',
            ]),
        ]);
    }

    public function show(int $employeePeriodObligation): View
    {
        $row = EmployeePeriodObligation::query()
            ->with([
                'employee.assignments.branch',
                'payrollPeriod',
                'workPatternRule',
            ])
            ->findOrFail($employeePeriodObligation);

        $this->authorizeEmployeeBranch($row);

        $period = $row->payrollPeriod;
        $rule = $row->workPatternRule;

        $dailyRows = collect();

        if ($period) {
            $dailyQuery = DB::table('attendance_daily')
                ->where('emp_id', $row->emp_id)
                ->whereBetween('work_date', [
                    $period->period_start_date,
                    $period->period_end_date,
                ]);

            if ($rule && !empty($rule->day_of_week_code)) {
                $isoDow = $this->mapDayOfWeekCodeToIsoDow($rule->day_of_week_code);

                if ($isoDow !== null) {
                    $dailyQuery->whereRaw('EXTRACT(ISODOW FROM work_date) = ?', [$isoDow]);
                }
            }

            if ($rule && !empty($rule->shift_id)) {
                $dailyQuery->where('shift_id', $rule->shift_id);
            }

            $dailyRows = $dailyQuery
                ->orderByDesc('work_date')
                ->orderByDesc('attendance_daily_id')
                ->limit(100)
                ->get();
        }

        $dailyStats = [
            'row_count' => $dailyRows->count(),
            'present_count' => $dailyRows->where('attendance_status_code', 'PRESENT')->count(),
            'anomaly_count' => $dailyRows->where('anomaly_flag', true)->count(),
            'exception_count' => $dailyRows->where('exception_flag', true)->count(),
            'leave_count' => $dailyRows->where('leave_flag', true)->count(),
            'late_count' => $dailyRows->filter(fn ($r) => (int) $r->late_min > 0)->count(),
            'overtime_count' => $dailyRows->filter(fn ($r) => (int) $r->overtime_min > 0)->count(),
        ];

        return view('summary.obligations.show', [
            'row' => $row,
            'dailyRows' => $dailyRows,
            'dailyStats' => $dailyStats,
        ]);
    }

    protected function authorizeEmployeeBranch(EmployeePeriodObligation $row): void
    {
        $user = auth()->user();

        if (!$user || $user->hasRole('SUPER_ADMIN')) {
            return;
        }

        $currentAssignment = $row->employee?->assignments
            ?->sortByDesc('effective_start_date')
            ?->first();

        $branchId = $currentAssignment?->branch_id;

        if (!$branchId || !$user->hasBranchAccess((int) $branchId)) {
            abort(403);
        }
    }

    protected function mapDayOfWeekCodeToIsoDow(?string $dayOfWeekCode): ?int
    {
        return match (strtoupper((string) $dayOfWeekCode)) {
            'MON' => 1,
            'TUE' => 2,
            'WED' => 3,
            'THU' => 4,
            'FRI' => 5,
            'SAT' => 6,
            'SUN' => 7,
            default => null,
        };
    }
}