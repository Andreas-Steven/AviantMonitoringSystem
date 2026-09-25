<?php

namespace App\Http\Controllers\Web\Attendance;

use App\Http\Controllers\Controller;
use App\Domains\Attendance\Models\AttendanceDaily;
use App\Domains\Attendance\Services\AttendanceDailyRecalculationService;
use App\Domains\Master\Models\Branch;
use App\Domains\Review\Models\AttendanceReviewCase;
use App\Domains\Scheduling\Models\PayrollPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Carbon\Carbon;

class AttendanceDailyController extends Controller
{
    public function index(Request $request): View
    {
        $query = AttendanceDaily::query()
            ->with(['employee', 'branch', 'shift'])
            ->orderByDesc('work_date')
            ->orderByDesc('attendance_daily_id');

        // =========================================================
        // FILTER: WORK DATE
        // =========================================================
        if ($request->filled('work_date')) {
            $query->whereDate('work_date', $request->work_date);
        }

        // =========================================================
        // FILTER: STATUS
        // =========================================================
        if ($request->filled('attendance_status_code')) {
            $query->where('attendance_status_code', $request->attendance_status_code);
        }

        // =========================================================
        // FILTER: LATE
        // =========================================================
        if ($request->boolean('late')) {
            $query->where('late_min', '>', 0);
        }

        // =========================================================
        // FILTER: ANOMALY
        // =========================================================
        if ($request->boolean('anomaly')) {
            $query->where(function ($q): void {
                $q->where('anomaly_flag', true)
                ->orWhere('attendance_status_code', 'MANUAL_REVIEW')
                ->orWhereNotNull('review_reason_code');
            });
        }

        if ($request->filled('payroll_period_id')) {
            $period = PayrollPeriod::query()->find($request->input('payroll_period_id'));

            if ($period) {
                $query->whereBetween('work_date', [
                    $period->period_start_date->toDateString(),
                    $period->period_end_date->toDateString(),
                ]);
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('work_date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('work_date', '<=', $request->input('date_to'));
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', (int) $request->input('branch_id'));
        }

        if ($request->filled('attendance_status_code')) {
            $query->where('attendance_status_code', $request->string('attendance_status_code')->toString());
        }

        if ($request->filled('presence_type_code')) {
            $query->where('presence_type_code', $request->string('presence_type_code')->toString());
        }

        if ($request->filled('review_reason_code')) {
            $query->where('review_reason_code', $request->string('review_reason_code')->toString());
        }

        if ($request->boolean('anomaly_only')) {
            $query->where('anomaly_flag', true);
        }

        if ($request->boolean('exception_only')) {
            $query->where('exception_flag', true);
        }

        if ($request->boolean('leave_only')) {
            $query->where('leave_flag', true);
        }

        if ($request->boolean('late_only')) {
            $query->where('late_min', '>', 0);
        }

        if ($request->boolean('incomplete_only')) {
            $query->where(function ($q): void {
                $q->where('attendance_status_code', 'INCOMPLETE')
                    ->orWhere('presence_type_code', 'PARTIAL')
                    ->orWhereNotNull('review_reason_code');
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

        $user = auth()->user();

        if ($user && !$user->hasRole('SUPER_ADMIN')) {
            $allowedBranchIds = $user->branchAccesses()->pluck('branches.branch_id')->all();

            if (!empty($allowedBranchIds)) {
                $query->whereIn('branch_id', $allowedBranchIds);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $statsBase = clone $query;

        $summaryStats = [
            'total_rows' => (clone $statsBase)->count(),
            'present_rows' => (clone $statsBase)->where('attendance_status_code', 'PRESENT')->count(),
            'anomaly_rows' => (clone $statsBase)->where('anomaly_flag', true)->count(),
            'exception_rows' => (clone $statsBase)->where('exception_flag', true)->count(),
            'leave_rows' => (clone $statsBase)->where('leave_flag', true)->count(),
            'late_rows' => (clone $statsBase)->where('late_min', '>', 0)->count(),
            'early_out_rows' => (clone $statsBase)->where('early_out_min', '>', 0)->count(),
            'overtime_rows' => (clone $statsBase)->where('overtime_min', '>', 0)->count(),
            'incomplete_rows' => (clone $statsBase)
                ->where(function ($q): void {
                    $q->where('attendance_status_code', 'INCOMPLETE')
                        ->orWhere('presence_type_code', 'PARTIAL')
                        ->orWhereNotNull('review_reason_code');
                })
                ->count(),
        ];

        $metricTotals = (clone $statsBase)
            ->reorder()
            ->selectRaw('
                COALESCE(SUM(work_min), 0) as total_work_min,
                COALESCE(SUM(late_min), 0) as total_late_min,
                COALESCE(SUM(early_out_min), 0) as total_early_out_min,
                COALESCE(SUM(overtime_min), 0) as total_overtime_min,
                COALESCE(SUM(overtime_workday_min), 0) as total_overtime_workday_min,
                COALESCE(SUM(overtime_holiday_min), 0) as total_overtime_holiday_min,
                COALESCE(SUM(overtime_offday_min), 0) as total_overtime_offday_min
            ')
            ->first();

        $rows = $query->paginate(20)->withQueryString();

        return view('attendance.daily.index', [
            'rows' => $rows,
            'summaryStats' => $summaryStats,
            'metricTotals' => $metricTotals,
            'branches' => $this->availableBranches(),
            'payrollPeriods' => PayrollPeriod::query()
                ->orderByDesc('period_start_date')
                ->get(),
            'attendanceStatuses' => collect([
                (object) ['code' => 'PRESENT', 'name' => 'Present'],
                (object) ['code' => 'ABSENT', 'name' => 'Absent'],
                (object) ['code' => 'LEAVE', 'name' => 'Leave'],
                (object) ['code' => 'SICK', 'name' => 'Sick'],
                (object) ['code' => 'PERMISSION', 'name' => 'Permission'],
                (object) ['code' => 'HOLIDAY', 'name' => 'Holiday'],
                (object) ['code' => 'OFF', 'name' => 'Off'],
                (object) ['code' => 'INCOMPLETE', 'name' => 'Incomplete'],
                (object) ['code' => 'MANUAL_REVIEW', 'name' => 'Manual Review'],
            ]),
            'presenceTypes' => collect([
                (object) ['code' => 'FULL_DAY', 'name' => 'Full Day'],
                (object) ['code' => 'HALF_DAY', 'name' => 'Half Day'],
                (object) ['code' => 'OVERTIME_ONLY', 'name' => 'Overtime Only'],
                (object) ['code' => 'NO_SHOW', 'name' => 'No Show'],
                (object) ['code' => 'PARTIAL', 'name' => 'Partial'],
            ]),
            'reviewReasons' => collect([
                'FORGOT_CHECKIN_APPROVED',
                'FORGOT_CHECKOUT_APPROVED',
                'SHIFT_MISSING',
                'MISSING_IN',
                'MISSING_OUT',
                'ANOMALY',
            ]),
        ]);
    }

public function show(int $attendanceDaily): View
{
    $row = AttendanceDaily::query()
        ->with([
            'employee',
            'branch',
            'policy',
            'shift',
            'details' => fn ($q) => $q->orderBy('attendance_daily_detail_id'),
        ])
        ->findOrFail($attendanceDaily);

    $this->authorizeBranch($row);

    [$windowStart, $windowEnd] = $this->resolveInvestigationWindow($row);

    $reviewCases = AttendanceReviewCase::query()
        ->with(['resolver'])
        ->where('emp_id', $row->emp_id)
        ->whereBetween('work_date', [
            $windowStart->copy()->toDateString(),
            $windowEnd->copy()->toDateString(),
        ])
        ->orderByRaw("
            CASE
                WHEN review_status_code IN ('OPEN', 'IN_REVIEW') THEN 1
                ELSE 2
            END
        ")
        ->orderByRaw("
            CASE severity_code
                WHEN 'CRITICAL' THEN 1
                WHEN 'HIGH' THEN 2
                WHEN 'MEDIUM' THEN 3
                WHEN 'LOW' THEN 4
                ELSE 5
            END
        ")
        ->orderByDesc('review_case_id')
        ->get();

    $normalizedLogs = DB::table('attendance_logs_normalized')
        ->where('emp_id', $row->emp_id)
        ->whereBetween('log_datetime', [$windowStart, $windowEnd])
        ->orderBy('log_datetime')
        ->limit(100)
        ->get();

    $rawLogs = DB::table('attendance_logs_raw')
        ->where('emp_id', $row->emp_id)
        ->whereBetween('log_datetime', [$windowStart, $windowEnd])
        ->orderBy('log_datetime')
        ->limit(100)
        ->get();

    $exceptions = DB::table('attendance_exceptions')
        ->where('emp_id', $row->emp_id)
        ->whereBetween('work_date', [
            $windowStart->copy()->toDateString(),
            $windowEnd->copy()->toDateString(),
        ])
        ->orderByDesc('attendance_exception_id')
        ->get();

    $summaryBox = [
        'review_case_count' => $reviewCases->count(),
        'open_review_case_count' => $reviewCases->whereIn('review_status_code', ['OPEN', 'IN_REVIEW'])->count(),
        'exception_count' => $exceptions->count(),
        'raw_log_count' => $rawLogs->count(),
        'normalized_log_count' => $normalizedLogs->count(),
        'detail_step_count' => $row->details->count(),
        'scheduled_minutes' => ($row->scheduled_in_datetime && $row->scheduled_out_datetime)
            ? $row->scheduled_out_datetime->diffInMinutes($row->scheduled_in_datetime)
            : 0,
    ];

    return view('attendance.daily.show', [
        'row' => $row,
        'reviewCases' => $reviewCases,
        'normalizedLogs' => $normalizedLogs,
        'rawLogs' => $rawLogs,
        'exceptions' => $exceptions,
        'summaryBox' => $summaryBox,
        'windowStart' => $windowStart,
        'windowEnd' => $windowEnd,
    ]);
}

    public function recalculate(
        AttendanceDailyRecalculationService $recalculationService,
        int $attendanceDaily
    ): RedirectResponse {
        $row = AttendanceDaily::query()->findOrFail($attendanceDaily);

        $this->authorizeBranch($row);

        $result = $recalculationService->recalculateOneDay(
            empId: (int) $row->emp_id,
            workDate: $row->work_date->toDateString(),
        );

        $targetId = (int) ($result['attendance_daily_id'] ?? $row->attendance_daily_id);

        if (($result['skipped'] ?? false) === true) {
            return redirect()
                ->route('attendance.daily.show', $targetId)
                ->with('warning', $result['message'] ?? 'Daily recalculation skipped.');
        }

        $reviewCreated = (int) ($result['review_case']['created'] ?? 0);
        $reviewUpdated = (int) ($result['review_case']['updated'] ?? 0);
        $reviewClosed = (int) ($result['review_case']['closed'] ?? 0);

        $summaryUpserted = (int) ($result['summary']['monthly']['upserted_count'] ?? 0);

        $messageParts = [
            sprintf('Daily recalculated for %s.', $row->work_date->toDateString()),
        ];

        if ($reviewCreated > 0 || $reviewUpdated > 0 || $reviewClosed > 0) {
            $messageParts[] = sprintf(
                'Review cases synced (created: %d, updated: %d, closed: %d).',
                $reviewCreated,
                $reviewUpdated,
                $reviewClosed
            );
        }

        if ($summaryUpserted > 0) {
            $messageParts[] = 'Monthly summary refreshed.';
        }

        return redirect()
            ->route('attendance.daily.show', $targetId)
            ->with('success', implode(' ', $messageParts));
    }

    protected function authorizeBranch(AttendanceDaily $row): void
    {
        $user = auth()->user();

        if (!$user || $user->hasRole('SUPER_ADMIN')) {
            return;
        }

        if (!$row->branch_id || !$user->hasBranchAccess((int) $row->branch_id)) {
            abort(403);
        }
    }

    protected function availableBranches()
    {
        $user = auth()->user();

        if ($user && $user->hasRole('SUPER_ADMIN')) {
            return Branch::query()
                ->where('active', true)
                ->orderBy('branch_name')
                ->get();
        }

        return $user
            ? $user->branchAccesses()
                ->where('branches.active', true)
                ->orderBy('branch_name')
                ->get()
            : collect();
    }

    protected function resolveInvestigationWindow(AttendanceDaily $row): array
    {
        $candidatesStart = [];
        $candidatesEnd = [];

        if ($row->work_date) {
            $candidatesStart[] = $row->work_date->copy()->startOfDay();
            $candidatesEnd[] = $row->work_date->copy()->endOfDay();
        }

        if ($row->scheduled_in_datetime) {
            $candidatesStart[] = $row->scheduled_in_datetime->copy()->subHours(4);
        }

        if ($row->actual_in_datetime) {
            $candidatesStart[] = $row->actual_in_datetime->copy()->subHours(4);
        }

        if ($row->scheduled_out_datetime) {
            $candidatesEnd[] = $row->scheduled_out_datetime->copy()->addHours(4);
        }

        if ($row->actual_out_datetime) {
            $candidatesEnd[] = $row->actual_out_datetime->copy()->addHours(4);
        }

        $windowStart = $this->minDateTime($candidatesStart)
            ?? now()->startOfDay();

        $windowEnd = $this->maxDateTime($candidatesEnd)
            ?? $windowStart->copy()->endOfDay();

        if ($windowEnd->lt($windowStart)) {
            $windowEnd = $windowStart->copy()->endOfDay();
        }

        return [$windowStart, $windowEnd];
    }

    protected function minDateTime(array $values): ?Carbon
    {
        $selected = null;

        foreach ($values as $value) {
            if (!$value) {
                continue;
            }

            $candidate = $value instanceof Carbon
                ? $value->copy()
                : Carbon::parse($value);

            if ($selected === null || $candidate->lt($selected)) {
                $selected = $candidate;
            }
        }

        return $selected;
    }

    protected function maxDateTime(array $values): ?Carbon
    {
        $selected = null;

        foreach ($values as $value) {
            if (!$value) {
                continue;
            }

            $candidate = $value instanceof Carbon
                ? $value->copy()
                : Carbon::parse($value);

            if ($selected === null || $candidate->gt($selected)) {
                $selected = $candidate;
            }
        }

        return $selected;
    }
}