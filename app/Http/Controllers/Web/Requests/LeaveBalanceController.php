<?php

namespace App\Http\Controllers\Web\Requests;

use App\Domains\Requests\Models\EmployeeLeaveBalance;
use App\Domains\Requests\Models\EmployeeLeaveBalanceTransaction;
use App\Domains\Requests\Models\LeaveType;
use App\Domains\Requests\Services\LeaveBalanceMutationService;
use App\Domains\Master\Models\Employee;
use App\Domains\Master\Models\EmploymentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Requests\GrantLeaveBalanceRequest;
use App\Http\Requests\Requests\AdjustLeaveBalanceRequest;
use App\Http\Requests\Requests\BulkGrantLeaveBalanceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


class LeaveBalanceController extends Controller
{
    public function index(Request $request): View
    {
        $query = EmployeeLeaveBalance::query()
            ->with(['employee.assignments.branch', 'leaveType'])
            ->orderByDesc('active')
            ->orderByDesc('period_end_date')
            ->orderByDesc('employee_leave_balance_id');

        if ($request->filled('q')) {
            $keyword = trim((string) $request->input('q'));

            $query->whereHas('employee', function ($q) use ($keyword): void {
                $q->where('emp_code', 'ilike', "%{$keyword}%")
                    ->orWhere('full_name', 'ilike', "%{$keyword}%")
                    ->orWhere('biometric_code', 'ilike', "%{$keyword}%");
            });
        }

        if ($request->filled('leave_type_id')) {
            $query->where('leave_type_id', (int) $request->input('leave_type_id'));
        }

        if ($request->filled('active')) {
            $query->where('active', (bool) $request->boolean('active'));
        }

        if ($request->filled('period_date')) {
            $query->whereDate('period_start_date', '<=', $request->input('period_date'))
                ->whereDate('period_end_date', '>=', $request->input('period_date'));
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
            'active_rows' => (clone $statsBase)->where('active', true)->count(),
            'annual_rows' => (clone $statsBase)->whereHas('leaveType', function ($q): void {
                $q->where('leave_type_code', 'ANNUAL');
            })->count(),
            'low_balance_rows' => (clone $statsBase)
                ->get()
                ->filter(fn (EmployeeLeaveBalance $balance) => $balance->available_balance <= 1)
                ->count(),
        ];

        $balances = $query->paginate(15)->withQueryString();

        $leaveTypes = LeaveType::query()
            ->where('active', true)
            ->orderBy('leave_type_name')
            ->get();

        $employmentTypes = EmploymentType::query()
            ->where('active', true)
            ->orderBy('employment_type_name')
            ->get();

        return view('requests.leave-balances.index', compact(
            'balances',
            'leaveTypes',
            'employmentTypes',
            'summaryStats',
        ));
    }

    public function show(int $employee_leave_balance): View
    {
        $balance = EmployeeLeaveBalance::query()
            ->with(['employee.assignments.branch', 'leaveType'])
            ->findOrFail($employee_leave_balance);

        $this->authorizeBranch($balance);

        $transactions = EmployeeLeaveBalanceTransaction::query()
            ->with(['transactionType', 'reversedTransaction'])
            ->where('employee_leave_balance_id', $balance->employee_leave_balance_id)
            ->orderByDesc('transaction_date')
            ->orderByDesc('employee_leave_balance_transaction_id')
            ->paginate(20)
            ->withQueryString();

        return view('requests.leave-balances.show', compact('balance', 'transactions'));
    }

    public function adjust(
        AdjustLeaveBalanceRequest $request,
        int $employee_leave_balance,
        LeaveBalanceMutationService $mutationService
    ): RedirectResponse {
        $balance = EmployeeLeaveBalance::query()
            ->with(['employee', 'leaveType'])
            ->findOrFail($employee_leave_balance);

        $this->authorizeBranch($balance);

        $qty = (float) $request->input('qty');
        $transactionDate = $request->input('transaction_date');
        $notes = $request->input('notes');
        $sourceRefId = $request->input('source_ref_id');

        if ($request->input('adjustment_mode') === 'PLUS') {
            $mutationService->adjustPlus(
                empId: (int) $balance->emp_id,
                leaveTypeId: (int) $balance->leave_type_id,
                transactionDate: $transactionDate,
                qty: $qty,
                employeeLeaveBalanceId: (int) $balance->employee_leave_balance_id,
                sourceTypeCode: 'MANUAL',
                sourceRefId: $sourceRefId,
                notes: $notes
            );
        } else {
            $mutationService->adjustMinus(
                empId: (int) $balance->emp_id,
                leaveTypeId: (int) $balance->leave_type_id,
                transactionDate: $transactionDate,
                qty: $qty,
                employeeLeaveBalanceId: (int) $balance->employee_leave_balance_id,
                sourceTypeCode: 'MANUAL',
                sourceRefId: $sourceRefId,
                notes: $notes
            );
        }

        return redirect()
            ->route('requests.leave-balances.show', $balance->employee_leave_balance_id)
            ->with('success', 'Leave balance adjustment berhasil disimpan.');
    }

    protected function authorizeBranch(EmployeeLeaveBalance $balance): void
    {
        $user = auth()->user();

        if (!$user || $user->hasRole('SUPER_ADMIN')) {
            return;
        }

        $allowedBranchIds = $user->branchAccesses()->pluck('branches.branch_id')->all();

        $branchId = optional(
            $balance->employee?->assignments
                ?->first(function ($assignment) use ($balance) {
                    return is_null($assignment->effective_end_date)
                        || $assignment->effective_end_date?->toDateString() >= $balance->period_start_date?->toDateString();
                })
        )->branch_id;

        abort_unless($branchId && in_array($branchId, $allowedBranchIds, true), 403);
    }

    public function grant(
        GrantLeaveBalanceRequest $request,
        int $employee_leave_balance,
        LeaveBalanceMutationService $mutationService
    ): RedirectResponse {
        $balance = EmployeeLeaveBalance::query()
            ->with(['employee', 'leaveType'])
            ->findOrFail($employee_leave_balance);

        $this->authorizeBranch($balance);

        $mutationService->grant(
            empId: (int) $balance->emp_id,
            leaveTypeId: (int) $balance->leave_type_id,
            transactionDate: $request->input('transaction_date'),
            qty: (float) $request->input('qty'),
            employeeLeaveBalanceId: (int) $balance->employee_leave_balance_id,
            sourceTypeCode: 'MANUAL',
            sourceRefId: $request->input('source_ref_id'),
            notes: $request->input('notes') ?: 'Manual leave grant'
        );

        return redirect()
            ->route('requests.leave-balances.show', $balance->employee_leave_balance_id)
            ->with('success', 'Leave balance grant berhasil disimpan.');
    }

    public function bulkGrant(
        BulkGrantLeaveBalanceRequest $request,
        LeaveBalanceMutationService $mutationService
    ): RedirectResponse {
        $leaveTypeId = (int) $request->input('leave_type_id');
        $transactionDate = Carbon::parse($request->input('transaction_date'));
        $qty = (float) $request->input('qty');
        $employmentTypeIds = array_map('intval', $request->input('employment_type_ids', []));
        $notes = $request->input('notes');

        $leaveType = LeaveType::query()->findOrFail($leaveTypeId);

        $periodStartDate = $transactionDate->copy()->startOfYear()->toDateString();
        $periodEndDate = $transactionDate->copy()->endOfYear()->toDateString();
        $transactionDateString = $transactionDate->toDateString();

        $employeesQuery = Employee::query()
            ->with('employmentType')
            ->where('active', true)
            ->whereIn('employment_type_id', $employmentTypeIds)
            ->orderBy('emp_code');

        $user = auth()->user();

        if ($user && !$user->hasRole('SUPER_ADMIN')) {
            $allowedBranchIds = $user->branchAccesses()->pluck('branches.branch_id')->all();

            if (!empty($allowedBranchIds)) {
                $employeesQuery->whereHas('assignments', function ($q) use ($allowedBranchIds, $transactionDateString): void {
                    $q->whereIn('branch_id', $allowedBranchIds)
                        ->whereDate('effective_start_date', '<=', $transactionDateString)
                        ->where(function ($sub) use ($transactionDateString): void {
                            $sub->whereNull('effective_end_date')
                                ->orWhereDate('effective_end_date', '>=', $transactionDateString);
                        });
                });
            } else {
                $employeesQuery->whereRaw('1 = 0');
            }
        }

        $employees = $employeesQuery->get();

        $createdBuckets = 0;
        $granted = 0;
        $skipped = 0;

        DB::transaction(function () use (
            $employees,
            $mutationService,
            $leaveType,
            $leaveTypeId,
            $transactionDateString,
            $periodStartDate,
            $periodEndDate,
            $qty,
            $notes,
            &$createdBuckets,
            &$granted,
            &$skipped
        ): void {
            foreach ($employees as $employee) {
                $balance = EmployeeLeaveBalance::query()
                    ->where('emp_id', $employee->emp_id)
                    ->where('leave_type_id', $leaveTypeId)
                    ->whereDate('period_start_date', $periodStartDate)
                    ->whereDate('period_end_date', $periodEndDate)
                    ->first();

                if (!$balance) {
                    $balance = EmployeeLeaveBalance::query()->create([
                        'emp_id' => $employee->emp_id,
                        'leave_type_id' => $leaveTypeId,
                        'period_start_date' => $periodStartDate,
                        'period_end_date' => $periodEndDate,
                        'opening_balance' => 0,
                        'granted_amount' => 0,
                        'used_amount' => 0,
                        'adjustment_amount' => 0,
                        'expired_amount' => 0,
                        'closing_balance' => 0,
                        'active' => true,
                        'notes' => sprintf(
                            'Auto-created by bulk grant. Leave type=%s, period=%s to %s.',
                            $leaveType->leave_type_code,
                            $periodStartDate,
                            $periodEndDate
                        ),
                    ]);

                    $createdBuckets++;
                }

                if (!(bool) $balance->active) {
                    $skipped++;
                    continue;
                }

                $sourceRefId = sprintf(
                    'HR-BULK-GRANT:%s:%s:%s',
                    $leaveType->leave_type_code,
                    Carbon::parse($transactionDateString)->format('Y'),
                    $employee->emp_code
                );

                $alreadyExists = DB::table('employee_leave_balance_transactions')
                    ->where('transaction_type_code', 'GRANT')
                    ->where('source_ref_id', $sourceRefId)
                    ->exists();

                $mutationService->grant(
                    empId: (int) $employee->emp_id,
                    leaveTypeId: $leaveTypeId,
                    transactionDate: $transactionDateString,
                    qty: $qty,
                    employeeLeaveBalanceId: (int) $balance->employee_leave_balance_id,
                    sourceTypeCode: 'MANUAL',
                    sourceRefId: $sourceRefId,
                    notes: $notes ?: sprintf(
                        'Bulk grant %s %.2f day(s) for %s.',
                        $leaveType->leave_type_code,
                        $qty,
                        Carbon::parse($transactionDateString)->format('Y')
                    )
                );

                if ($alreadyExists) {
                    $skipped++;
                } else {
                    $granted++;
                }
            }
        });

        return redirect()
            ->route('requests.leave-balances.index')
            ->with(
                'success',
                sprintf(
                    'Bulk grant selesai. Employee matched: %d, bucket dibuat: %d, grant baru: %d, skipped/idempotent: %d.',
                    $employees->count(),
                    $createdBuckets,
                    $granted,
                    $skipped
                )
            );
    }  
}