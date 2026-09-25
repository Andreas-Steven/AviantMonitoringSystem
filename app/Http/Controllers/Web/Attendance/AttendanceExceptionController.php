<?php

namespace App\Http\Controllers\Web\Attendance;

use App\Domains\Attendance\Models\AttendanceDaily;
use App\Domains\Master\Models\Employee;
use App\Domains\Review\Models\AttendanceException;
use App\Domains\Review\Models\AttendanceReviewCase;
use App\Domains\Scheduling\Models\Shift;
use App\Http\Controllers\Controller;
use App\Http\Requests\Review\StoreAttendanceExceptionRequest;
use App\Http\Requests\Review\UpdateAttendanceExceptionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AttendanceExceptionController extends Controller
{
    public function index(Request $request): View
    {
        $query = AttendanceException::query()
            ->with(['employee.assignments.branch', 'shift', 'approver'])
            ->orderByDesc('work_date')
            ->orderByDesc('attendance_exception_id');

        if ($request->filled('q')) {
            $keyword = trim((string) $request->input('q'));

            $query->whereHas('employee', function ($q) use ($keyword): void {
                $q->where('emp_code', 'ilike', "%{$keyword}%")
                    ->orWhere('full_name', 'ilike', "%{$keyword}%")
                    ->orWhere('biometric_code', 'ilike', "%{$keyword}%");
            });
        }

        if ($request->filled('exception_type_code')) {
            $query->where('exception_type_code', $request->string('exception_type_code')->toString());
        }

        if ($request->filled('status_value_code')) {
            $query->where('status_value_code', $request->string('status_value_code')->toString());
        }

        if ($request->filled('source_type_code')) {
            $query->where('source_type_code', $request->string('source_type_code')->toString());
        }

        if ($request->filled('date_from')) {
            $query->whereDate('work_date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('work_date', '<=', $request->input('date_to'));
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

        $statsBase = clone $query;

        $summaryStats = [
            'total_rows' => (clone $statsBase)->count(),
            'approved_rows' => (clone $statsBase)->whereNotNull('approved_at')->count(),
            'manual_rows' => (clone $statsBase)->whereIn('exception_type_code', ['MANUAL_IN', 'MANUAL_OUT'])->count(),
            'forgot_rows' => (clone $statsBase)->whereIn('exception_type_code', ['FORGOT_CHECKIN_APPROVAL', 'FORGOT_CHECKOUT_APPROVAL'])->count(),
            'override_rows' => (clone $statsBase)->whereIn('exception_type_code', ['FORCE_PRESENT', 'FORCE_ABSENT', 'SHIFT_OVERRIDE', 'OVERTIME_OVERRIDE'])->count(),
        ];

        $exceptions = $query->paginate(15)->withQueryString();

        return view('attendance.attendance-exceptions.index', array_merge(
            [
                'exceptions' => $exceptions,
                'summaryStats' => $summaryStats,
            ],
            $this->formOptions()
        ));
    }

    public function create(Request $request): View
    {
        $prefill = [
            'emp_id' => $request->input('emp_id'),
            'work_date' => $request->input('work_date'),
            'exception_type_code' => $request->input('exception_type_code'),
            'source_type_code' => $request->input('source_type_code'),
            'source_ref_id' => $request->input('source_ref_id'),
        ];

        return view('attendance.attendance-exceptions.create', array_merge(
            $this->formOptions(),
            [
                'prefill' => $prefill,
                'returnTo' => $request->input('return_to'),
            ]
        ));
    }

    public function store(StoreAttendanceExceptionRequest $request): RedirectResponse
    {
        $payload = $this->payload($request);
        $attendanceException = AttendanceException::query()->create($payload);

        $returnTo = $request->input('return_to');

        if (filled($returnTo)) {
            return redirect($returnTo)
                ->with('success', 'Attendance exception berhasil ditambahkan.');
        }

        return redirect()
            ->route('attendance.attendance-exceptions.show', $attendanceException->attendance_exception_id)
            ->with('success', 'Attendance exception berhasil ditambahkan.');
    }

    public function show(int $attendance_exception): View
    {
        $attendanceException = AttendanceException::query()
            ->with(['employee.assignments.branch', 'shift', 'approver'])
            ->findOrFail($attendance_exception);

        $this->authorizeBranch($attendanceException);

        $daily = AttendanceDaily::query()
            ->with(['branch', 'shift', 'policy'])
            ->where('emp_id', $attendanceException->emp_id)
            ->whereDate('work_date', $attendanceException->work_date)
            ->first();

        $reviewCases = AttendanceReviewCase::query()
            ->with(['resolver'])
            ->where('emp_id', $attendanceException->emp_id)
            ->whereDate('work_date', $attendanceException->work_date)
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

        $sameDayExceptions = AttendanceException::query()
            ->where('emp_id', $attendanceException->emp_id)
            ->whereDate('work_date', $attendanceException->work_date)
            ->where('attendance_exception_id', '!=', $attendanceException->attendance_exception_id)
            ->orderByDesc('attendance_exception_id')
            ->get();

        return view('attendance.attendance-exceptions.show', compact(
            'attendanceException',
            'daily',
            'reviewCases',
            'sameDayExceptions',
        ));
    }

    public function edit(int $attendance_exception): View
    {
        $attendanceException = AttendanceException::query()
            ->with(['employee', 'shift', 'approver'])
            ->findOrFail($attendance_exception);

        $this->authorizeBranch($attendanceException);

        return view('attendance.attendance-exceptions.edit', array_merge(
            ['attendanceException' => $attendanceException],
            $this->formOptions()
        ));
    }

    public function update(UpdateAttendanceExceptionRequest $request, int $attendance_exception): RedirectResponse
    {
        $attendanceException = AttendanceException::query()->findOrFail($attendance_exception);

        $this->authorizeBranch($attendanceException);

        $attendanceException->update($this->payload($request));

        return redirect()
            ->route('attendance.attendance-exceptions.show', $attendanceException->attendance_exception_id)
            ->with('success', 'Attendance exception berhasil diperbarui.');
    }

    protected function payload(Request $request): array
    {
        return [
            'emp_id' => (int) $request->input('emp_id'),
            'work_date' => $request->input('work_date'),
            'exception_type_code' => $request->input('exception_type_code'),
            'minutes_value' => $request->filled('minutes_value')
                ? (int) $request->input('minutes_value')
                : null,
            'time_value' => $request->input('time_value') ?: null,
            'shift_id_value' => $request->input('shift_id_value') ?: null,
            'status_value_code' => $request->input('status_value_code') ?: null,
            'reason' => $request->input('reason'),
            'approved_by' => $request->input('approved_by') ?: null,
            'approved_at' => $request->input('approved_at') ?: null,
            'source_type_code' => $request->input('source_type_code') ?: null,
            'source_ref_id' => $request->input('source_ref_id'),
            'notes' => $request->input('notes'),
        ];
    }

    protected function formOptions(): array
    {
        $user = auth()->user();

        $employeeQuery = Employee::query()
            ->where('active', true)
            ->orderBy('full_name');

        if ($user && !$user->hasRole('SUPER_ADMIN')) {
            $allowedBranchIds = $user->branchAccesses()->pluck('branches.branch_id')->all();

            if (!empty($allowedBranchIds)) {
                $employeeQuery->whereHas('assignments', function ($q) use ($allowedBranchIds): void {
                    $q->whereIn('branch_id', $allowedBranchIds)
                        ->where(function ($sub): void {
                            $sub->whereNull('effective_end_date')
                                ->orWhereDate('effective_end_date', '>=', now()->toDateString());
                        });
                });
            } else {
                $employeeQuery->whereRaw('1 = 0');
            }
        }

        return [
            'employees' => $employeeQuery->get(),

            'shifts' => Shift::query()
                ->where('active', true)
                ->orderBy('shift_name')
                ->get(),

            'exceptionTypes' => DB::table('attendance_exception_types')
                ->orderBy('exception_type_name')
                ->get(),

            'attendanceStatuses' => DB::table('attendance_statuses')
                ->orderBy('attendance_status_name')
                ->get(),

            'sourceTypes' => DB::table('source_types')
                ->orderBy('source_type_name')
                ->get(),
        ];
    }

    protected function authorizeBranch(AttendanceException $attendanceException): void
    {
        $user = auth()->user();

        if (!$user || $user->hasRole('SUPER_ADMIN')) {
            return;
        }

        $allowedBranchIds = $user->branchAccesses()->pluck('branches.branch_id')->all();

        $branchId = optional(
            $attendanceException->employee?->assignments
                ?->first(function ($assignment) use ($attendanceException) {
                    return is_null($assignment->effective_end_date)
                        || $assignment->effective_end_date?->toDateString() >= $attendanceException->work_date?->toDateString();
                })
        )->branch_id;

        abort_unless($branchId && in_array($branchId, $allowedBranchIds, true), 403);
    }
}