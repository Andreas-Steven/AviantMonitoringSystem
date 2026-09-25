<?php

namespace App\Http\Controllers\Web\Summary;

use App\Http\Controllers\Controller;
use App\Domains\Master\Models\Branch;
use App\Domains\Summary\Models\AttendanceMonthlySummary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AttendanceMonthlySummaryController extends Controller
{
    public function index(Request $request): View
    {
        $query = AttendanceMonthlySummary::query()
            ->with(['employee', 'branch'])
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->orderByDesc('summary_id');

        if ($request->filled('period_year')) {
            $query->where('period_year', (int) $request->input('period_year'));
        }

        if ($request->filled('period_month')) {
            $query->where('period_month', (int) $request->input('period_month'));
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', (int) $request->input('branch_id'));
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
                    $q->where('absent_days', '>', 0)
                        ->orWhere('incomplete_count', '>', 0)
                        ->orWhere('late_count', '>', 0);
                });
            }

            if ($focus === 'late') {
                $query->where('late_count', '>', 0);
            }

            if ($focus === 'incomplete') {
                $query->where('incomplete_count', '>', 0);
            }

            if ($focus === 'absent') {
                $query->where('absent_days', '>', 0);
            }

            if ($focus === 'overtime') {
                $query->where('overtime_min_total', '>', 0);
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

        return view('summary.monthly.index', [
            'rows' => $rows,
            'branches' => $this->availableBranches(),
            'years' => AttendanceMonthlySummary::query()
                ->select('period_year')
                ->distinct()
                ->orderByDesc('period_year')
                ->pluck('period_year'),
            'months' => collect(range(1, 12)),
        ]);
    }

    public function show(int $attendanceMonthlySummary): View
    {
        $row = AttendanceMonthlySummary::query()
            ->with(['employee', 'branch'])
            ->findOrFail($attendanceMonthlySummary);

        $this->authorizeBranch((int) $row->branch_id);

        $dailyRows = DB::table('attendance_daily')
            ->where('emp_id', $row->emp_id)
            ->whereYear('work_date', $row->period_year)
            ->whereMonth('work_date', $row->period_month)
            ->orderByDesc('work_date')
            ->orderByDesc('attendance_daily_id')
            ->limit(100)
            ->get();

        $dailyStats = [
            'row_count' => $dailyRows->count(),
            'present_count' => $dailyRows->where('attendance_status_code', 'PRESENT')->count(),
            'anomaly_count' => $dailyRows->where('anomaly_flag', true)->count(),
            'exception_count' => $dailyRows->where('exception_flag', true)->count(),
            'leave_count' => $dailyRows->where('leave_flag', true)->count(),
            'late_count' => $dailyRows->filter(fn ($r) => (int) $r->late_min > 0)->count(),
            'incomplete_count' => $dailyRows->filter(function ($r) {
                return in_array($r->attendance_status_code, ['INCOMPLETE', 'MANUAL_REVIEW'], true)
                    || !empty($r->review_reason_code);
            })->count(),
        ];

        return view('summary.monthly.show', [
            'row' => $row,
            'dailyRows' => $dailyRows,
            'dailyStats' => $dailyStats,
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