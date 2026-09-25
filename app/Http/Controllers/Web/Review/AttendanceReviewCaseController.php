<?php

namespace App\Http\Controllers\Web\Review;

use App\Http\Controllers\Controller;
use App\Domains\Attendance\Models\AttendanceDaily;
use App\Domains\Review\Models\AttendanceReviewCase;
use App\Domains\Scheduling\Models\PayrollPeriod;
use App\Http\Requests\Review\UpdateAttendanceReviewCaseRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Carbon\Carbon;

class AttendanceReviewCaseController extends Controller
{
    public function index(Request $request): View
    {
        $query = AttendanceReviewCase::query()
            ->with(['employee', 'resolver'])
            ->whereHas('employee.assignments', function ($q): void {
                $q->where(function ($sub): void {
                    $sub->whereNull('effective_end_date')
                        ->orWhereDate('effective_end_date', '>=', now()->toDateString());
                });
            })
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
            ->orderByDesc('work_date')
            ->orderByDesc('review_case_id');

        $selectedPeriod = null;

        if ($request->filled('payroll_period_id')) {
            $selectedPeriod = PayrollPeriod::query()->find($request->input('payroll_period_id'));

            if ($selectedPeriod) {
                $query->whereBetween('work_date', [
                    $selectedPeriod->period_start_date->toDateString(),
                    $selectedPeriod->period_end_date->toDateString(),
                ]);
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('work_date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('work_date', '<=', $request->input('date_to'));
        }

        if ($request->filled('case_type_code')) {
            $query->where('case_type_code', $request->string('case_type_code')->toString());
        }

        if ($request->filled('severity_code')) {
            $query->where('severity_code', $request->string('severity_code')->toString());
        }

        if ($request->filled('review_status_code')) {
            $query->where('review_status_code', $request->string('review_status_code')->toString());
        }

        if ($request->filled('resolution_type_code')) {
            $query->where('resolution_type_code', $request->string('resolution_type_code')->toString());
        }

        if ($request->boolean('open_only')) {
            $query->whereIn('review_status_code', ['OPEN', 'IN_REVIEW']);
        }

        if ($request->boolean('unresolved_only')) {
            $query->whereNull('resolved_at');
        }

        if ($request->filled('focus')) {
            $focus = $request->string('focus')->toString();

            if ($focus === 'critical_open') {
                $query->where('severity_code', 'CRITICAL')
                    ->whereIn('review_status_code', ['OPEN', 'IN_REVIEW']);
            }

            if ($focus === 'missing_logs') {
                $query->whereIn('case_type_code', ['MISSING_IN', 'MISSING_OUT']);
            }

            if ($focus === 'stale_open') {
                $query->whereIn('review_status_code', ['OPEN', 'IN_REVIEW'])
                    ->whereDate('detected_at', '<=', now()->subDays(3)->toDateString());
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

        $summaryBaseQuery = AttendanceReviewCase::query()
            ->whereHas('employee.assignments', function ($q): void {
                $q->where(function ($sub): void {
                    $sub->whereNull('effective_end_date')
                        ->orWhereDate('effective_end_date', '>=', now()->toDateString());
                });
            });

        if ($selectedPeriod) {
            $summaryBaseQuery->whereBetween('work_date', [
                $selectedPeriod->period_start_date->toDateString(),
                $selectedPeriod->period_end_date->toDateString(),
            ]);
        }

        if ($request->filled('date_from')) {
            $summaryBaseQuery->whereDate('work_date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $summaryBaseQuery->whereDate('work_date', '<=', $request->input('date_to'));
        }

        if ($user && !$user->hasRole('SUPER_ADMIN')) {
            $allowedBranchIds = $user->branchAccesses()->pluck('branches.branch_id')->all();

            if (!empty($allowedBranchIds)) {
                $summaryBaseQuery->whereHas('employee.assignments', function ($q) use ($allowedBranchIds): void {
                    $q->whereIn('branch_id', $allowedBranchIds)
                        ->where(function ($sub): void {
                            $sub->whereNull('effective_end_date')
                                ->orWhereDate('effective_end_date', '>=', now()->toDateString());
                        });
                });
            } else {
                $summaryBaseQuery->whereRaw('1 = 0');
            }
        }

        $summary = [
            'total' => (clone $summaryBaseQuery)->count(),
            'open' => (clone $summaryBaseQuery)->where('review_status_code', 'OPEN')->count(),
            'in_review' => (clone $summaryBaseQuery)->where('review_status_code', 'IN_REVIEW')->count(),
            'resolved' => (clone $summaryBaseQuery)->where('review_status_code', 'RESOLVED')->count(),
        ];

        return view('review.attendance-cases.index', [
            'rows' => $rows,
            'summary' => $summary,
            'selectedDateFrom' => $request->input('date_from'),
            'selectedDateTo' => $request->input('date_to'),
            'selectedPayrollPeriodId' => $selectedPeriod?->payroll_period_id,
            'payrollPeriods' => PayrollPeriod::query()
                ->orderByDesc('period_start_date')
                ->get(),
            'caseTypes' => collect([
                'DOUBLE_TAP',
                'MISSING_IN',
                'MISSING_OUT',
                'UNMATCHED_LOG',
                'SHIFT_MISMATCH',
                'OUTSIDE_BRANCH',
            ]),
            'severityLevels' => collect(['LOW', 'MEDIUM', 'HIGH', 'CRITICAL']),
            'reviewStatuses' => collect(['OPEN', 'IN_REVIEW', 'RESOLVED', 'REJECTED', 'CLOSED']),
            'resolutionTypes' => collect([
                'NO_ACTION',
                'MANUAL_CORRECTION',
                'APPROVED_OVERRIDE',
                'REJECTED_CASE',
                'SYSTEM_ADJUSTMENT',
            ]),
        ]);
    }

    public function show(int $attendanceReviewCase): View
    {
        $row = AttendanceReviewCase::query()
            ->with(['employee.assignments.branch', 'resolver'])
            ->findOrFail($attendanceReviewCase);

        $this->authorizeBranch($row);

        $daily = AttendanceDaily::query()
            ->with(['branch', 'shift', 'policy'])
            ->where('emp_id', $row->emp_id)
            ->whereDate('work_date', $row->work_date)
            ->first();

        [$windowStart, $windowEnd] = $this->resolveInvestigationWindow($row, $daily);

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

        $relatedCases = AttendanceReviewCase::query()
            ->where('emp_id', $row->emp_id)
            ->whereBetween('work_date', [
                $windowStart->copy()->toDateString(),
                $windowEnd->copy()->toDateString(),
            ])
            ->where('review_case_id', '!=', $row->review_case_id)
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

        return view('review.attendance-cases.show', [
            'row' => $row,
            'daily' => $daily,
            'normalizedLogs' => $normalizedLogs,
            'rawLogs' => $rawLogs,
            'relatedCases' => $relatedCases,
            'windowStart' => $windowStart,
            'windowEnd' => $windowEnd,
            'resolutionTypes' => collect([
                'NO_ACTION',
                'MANUAL_CORRECTION',
                'APPROVED_OVERRIDE',
                'REJECTED_CASE',
                'SYSTEM_ADJUSTMENT',
            ]),
            'reviewStatuses' => collect(['OPEN', 'IN_REVIEW', 'RESOLVED', 'REJECTED', 'CLOSED']),
        ]);
    }

    public function update(UpdateAttendanceReviewCaseRequest $request, AttendanceReviewCase $attendanceReviewCase): RedirectResponse
    {
        $this->authorizeBranch($attendanceReviewCase);

        $user = auth()->user();

        $resolvedStatuses = ['RESOLVED', 'REJECTED', 'CLOSED'];
        $nextStatus = (string) $request->input('review_status_code');

        $payload = [
            'review_status_code' => $nextStatus,
            'resolution_type_code' => $request->input('resolution_type_code') ?: null,
            'notes' => $request->input('notes'),
        ];

        if (in_array($nextStatus, $resolvedStatuses, true)) {
            $payload['resolved_at'] = now();
            $payload['resolved_by'] = $user?->employee_id ?: null;
        } else {
            $payload['resolved_at'] = null;
            $payload['resolved_by'] = null;
        }

        $attendanceReviewCase->update($payload);

        return redirect()
            ->route('review.attendance-cases.show', $attendanceReviewCase->review_case_id)
            ->with('success', 'Review case berhasil diperbarui.');
    }

    protected function authorizeBranch(AttendanceReviewCase $row): void
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

    protected function resolveInvestigationWindow(AttendanceReviewCase $row, ?AttendanceDaily $daily = null): array
    {
        $candidatesStart = [];
        $candidatesEnd = [];

        if ($row->work_date) {
            $candidatesStart[] = $row->work_date->copy()->startOfDay();
            $candidatesEnd[] = $row->work_date->copy()->endOfDay();
        }

        if ($row->detected_at) {
            $candidatesStart[] = $row->detected_at->copy()->subHours(4);
            $candidatesEnd[] = $row->detected_at->copy()->addHours(4);
        }

        if ($daily?->scheduled_in_datetime) {
            $candidatesStart[] = $daily->scheduled_in_datetime->copy()->subHours(4);
        }

        if ($daily?->actual_in_datetime) {
            $candidatesStart[] = $daily->actual_in_datetime->copy()->subHours(4);
        }

        if ($daily?->scheduled_out_datetime) {
            $candidatesEnd[] = $daily->scheduled_out_datetime->copy()->addHours(4);
        }

        if ($daily?->actual_out_datetime) {
            $candidatesEnd[] = $daily->actual_out_datetime->copy()->addHours(4);
        }

        $windowStart = $this->minDateTime($candidatesStart) ?? now()->startOfDay();
        $windowEnd = $this->maxDateTime($candidatesEnd) ?? $windowStart->copy()->endOfDay();

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

            $candidate = $value instanceof Carbon ? $value->copy() : Carbon::parse($value);

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

            $candidate = $value instanceof Carbon ? $value->copy() : Carbon::parse($value);

            if ($selected === null || $candidate->gt($selected)) {
                $selected = $candidate;
            }
        }

        return $selected;
    }
}