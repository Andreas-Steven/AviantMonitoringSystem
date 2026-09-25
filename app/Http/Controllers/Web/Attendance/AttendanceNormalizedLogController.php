<?php

namespace App\Http\Controllers\Web\Attendance;

use App\Http\Controllers\Controller;
use App\Domains\Attendance\Models\AttendanceDaily;
use App\Domains\Attendance\Models\AttendanceNormalizedLog;
use App\Domains\Master\Models\Branch;
use App\Domains\Scheduling\Models\PayrollPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AttendanceNormalizedLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = AttendanceNormalizedLog::query()
            ->with(['employee', 'rawLog.branch', 'rawLog.importBatch'])
            ->orderByDesc('log_datetime')
            ->orderByDesc('normalized_log_id');

        if ($request->filled('payroll_period_id')) {
            $period = PayrollPeriod::query()->find($request->input('payroll_period_id'));

            if ($period) {
                $query->whereBetween('log_datetime', [
                    $period->period_start_date->startOfDay(),
                    $period->period_end_date->endOfDay(),
                ]);
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('log_datetime', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('log_datetime', '<=', $request->input('date_to'));
        }

        if ($request->filled('derived_event_type_code')) {
            $query->where('derived_event_type_code', $request->string('derived_event_type_code')->toString());
        }

        if ($request->filled('normalized_status_code')) {
            $query->where('normalized_status_code', $request->string('normalized_status_code')->toString());
        }

        if ($request->boolean('duplicates_only')) {
            $query->where('is_duplicate_candidate', true);
        }

        if ($request->filled('duplicate_group_key')) {
            $query->where('duplicate_group_key', $request->string('duplicate_group_key')->toString());
        }

        if ($request->filled('branch_id')) {
            $branchId = (int) $request->input('branch_id');

            $query->whereHas('rawLog', function ($q) use ($branchId): void {
                $q->where('location_branch_id', $branchId);
            });
        }

        if ($request->filled('q')) {
            $keyword = trim((string) $request->input('q'));

            $query->where(function ($q) use ($keyword): void {
                $q->whereHas('employee', function ($sub) use ($keyword): void {
                    $sub->where('emp_code', 'ilike', "%{$keyword}%")
                        ->orWhere('full_name', 'ilike', "%{$keyword}%")
                        ->orWhere('biometric_code', 'ilike', "%{$keyword}%");
                })->orWhereHas('rawLog', function ($sub) use ($keyword): void {
                    $sub->where('device_user_id', 'ilike', "%{$keyword}%")
                        ->orWhere('device_id', 'ilike', "%{$keyword}%")
                        ->orWhere('source_system', 'ilike', "%{$keyword}%");
                });
            });
        }

        $user = auth()->user();

        if ($user && !$user->hasRole('SUPER_ADMIN')) {
            $allowedBranchIds = $user->branchAccesses()->pluck('branches.branch_id')->all();

            if (!empty($allowedBranchIds)) {
                $query->whereHas('rawLog', function ($q) use ($allowedBranchIds): void {
                    $q->whereIn('location_branch_id', $allowedBranchIds);
                });
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $rows = $query->paginate(20)->withQueryString();

        $this->attachDailyMatches($rows->getCollection());

        return view('attendance.normalized-logs.index', [
            'rows' => $rows,
            'branches' => $this->availableBranches(),
            'payrollPeriods' => PayrollPeriod::query()
                ->orderByDesc('period_start_date')
                ->get(),
            'derivedEventTypes' => collect(['IN', 'OUT', 'UNKNOWN', 'DUPLICATE']),
            'normalizedStatuses' => collect(['VALID', 'INVALID', 'DUPLICATE', 'SUSPICIOUS', 'IGNORED']),
        ]);
    }

    public function show(int $normalizedLog): View
    {
        $row = AttendanceNormalizedLog::query()
            ->with(['employee', 'rawLog.branch', 'rawLog.importBatch'])
            ->findOrFail($normalizedLog);

        $this->authorizeBranch($row);

        $matchedDaily = null;

        if ($row->emp_id && $row->log_datetime) {
            $matchedDaily = AttendanceDaily::query()
                ->where('emp_id', $row->emp_id)
                ->whereDate('work_date', $row->log_datetime->toDateString())
                ->first();
        }

        return view('attendance.normalized-logs.show', [
            'row' => $row,
            'matchedDaily' => $matchedDaily,
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

    protected function authorizeBranch(AttendanceNormalizedLog $row): void
    {
        $user = auth()->user();

        if (!$user || $user->hasRole('SUPER_ADMIN')) {
            return;
        }

        $branchId = $row->rawLog?->location_branch_id;

        if (!$branchId || !$user->hasBranchAccess((int) $branchId)) {
            abort(403);
        }
    }

    protected function attachDailyMatches(Collection $rows): void
    {
        if ($rows->isEmpty()) {
            return;
        }

        $empIds = $rows->pluck('emp_id')->filter()->unique()->values();
        $workDates = $rows
            ->map(fn ($row) => optional($row->log_datetime)?->toDateString())
            ->filter()
            ->unique()
            ->values();

        if ($empIds->isEmpty() || $workDates->isEmpty()) {
            foreach ($rows as $row) {
                $row->matched_daily_id = null;
            }

            return;
        }

        $dailyRows = AttendanceDaily::query()
            ->select(['attendance_daily_id', 'emp_id', 'work_date'])
            ->whereIn('emp_id', $empIds)
            ->whereIn('work_date', $workDates)
            ->get();

        $dailyMap = $dailyRows->keyBy(function ($daily) {
            return $daily->emp_id . '|' . optional($daily->work_date)->format('Y-m-d');
        });

        foreach ($rows as $row) {
            $key = $row->emp_id . '|' . optional($row->log_datetime)?->toDateString();
            $row->matched_daily_id = $dailyMap->get($key)?->attendance_daily_id;
        }
    }
}