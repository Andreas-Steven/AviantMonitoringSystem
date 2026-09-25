<?php

namespace App\Http\Controllers\Web\Attendance;

use App\Http\Controllers\Controller;
use App\Domains\Attendance\Models\AttendanceLogRaw;
use App\Domains\Master\Models\Branch;
use App\Domains\Scheduling\Models\PayrollPeriod;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceRawLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = AttendanceLogRaw::query()
            ->with(['employee', 'branch', 'normalizedLog', 'importBatch'])
            ->orderByDesc('log_datetime')
            ->orderByDesc('log_id');

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
            $query->whereDate('log_date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('log_date', '<=', $request->input('date_to'));
        }

        if ($request->filled('branch_id')) {
            $query->where('location_branch_id', (int) $request->input('branch_id'));
        }

        if ($request->filled('source_system')) {
            $query->where('source_system', $request->string('source_system')->toString());
        }

        if ($request->filled('device_id')) {
            $query->where('device_id', 'ilike', '%' . trim((string) $request->input('device_id')) . '%');
        }

        if ($request->filled('device_user_id')) {
            $query->where('device_user_id', 'ilike', '%' . trim((string) $request->input('device_user_id')) . '%');
        }

        if ($request->filled('q')) {
            $keyword = trim((string) $request->input('q'));

            $query->where(function ($q) use ($keyword): void {
                $q->where('device_user_id', 'ilike', "%{$keyword}%")
                    ->orWhere('device_id', 'ilike', "%{$keyword}%")
                    ->orWhere('source_system', 'ilike', "%{$keyword}%")
                    ->orWhereHas('employee', function ($employeeQuery) use ($keyword): void {
                        $employeeQuery->where('emp_code', 'ilike', "%{$keyword}%")
                            ->orWhere('full_name', 'ilike', "%{$keyword}%")
                            ->orWhere('biometric_code', 'ilike', "%{$keyword}%");
                    });
            });
        }

        $user = auth()->user();

        if ($user && !$user->hasRole('SUPER_ADMIN')) {
            $allowedBranchIds = $user->branchAccesses()->pluck('branches.branch_id')->all();

            if (!empty($allowedBranchIds)) {
                $query->whereIn('location_branch_id', $allowedBranchIds);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $logs = $query->paginate(20)->withQueryString();

        return view('attendance.raw-logs.index', [
            'logs' => $logs,
            'branches' => $this->availableBranches(),
            'payrollPeriods' => PayrollPeriod::query()
                ->orderByDesc('period_start_date')
                ->get(),
            'sourceSystems' => AttendanceLogRaw::query()
                ->select('source_system')
                ->whereNotNull('source_system')
                ->distinct()
                ->orderBy('source_system')
                ->pluck('source_system'),
        ]);
    }

    public function show(int $rawLog): View
    {
        $log = AttendanceLogRaw::query()
            ->with(['employee', 'branch', 'normalizedLog', 'importBatch'])
            ->findOrFail($rawLog);

        $this->authorizeBranch($log);

        return view('attendance.raw-logs.show', [
            'rawLog' => $log
        ]);
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

    protected function authorizeBranch(AttendanceLogRaw $log): void
    {
        $user = auth()->user();

        if (!$user || $user->hasRole('SUPER_ADMIN')) {
            return;
        }

        if (!$log->location_branch_id || !$user->hasBranchAccess((int) $log->location_branch_id)) {
            abort(403);
        }
    }
}