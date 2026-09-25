<?php

namespace App\Http\Controllers\Web\Requests;

use App\Domains\Master\Models\Employee;
use App\Domains\Requests\Models\EmployeeLeaveRequest;
use App\Domains\Requests\Models\LeaveType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Requests\ApproveLeaveRequestRequest;
use App\Http\Requests\Requests\CancelLeaveRequestRequest;
use App\Http\Requests\Requests\RejectLeaveRequestRequest;
use App\Http\Requests\Requests\StoreLeaveRequestRequest;
use App\Http\Requests\Requests\UpdateLeaveRequestRequest;
use App\Domains\Requests\Services\LeaveBalanceMutationService;
use App\Domains\Requests\Services\LeaveBalanceResolverService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class LeaveRequestController extends Controller
{
    public function index(Request $request): View
    {
        $query = EmployeeLeaveRequest::query()
            ->with(['employee', 'leaveType', 'approver'])
            ->orderByRaw("
                CASE request_status_code
                    WHEN 'PENDING' THEN 0
                    WHEN 'APPROVED' THEN 1
                    WHEN 'REJECTED' THEN 2
                    WHEN 'CANCELLED' THEN 3
                    ELSE 9
                END
            ")
            ->orderByDesc('start_date')
            ->orderByDesc('leave_request_id');

        if ($request->filled('q')) {
            $keyword = trim((string) $request->input('q'));

            $query->whereHas('employee', function ($q) use ($keyword): void {
                $q->where('emp_code', 'ilike', "%{$keyword}%")
                    ->orWhere('full_name', 'ilike', "%{$keyword}%");
            });
        }

        if ($request->filled('leave_type_id')) {
            $query->where('leave_type_id', (int) $request->input('leave_type_id'));
        }

        if ($request->filled('request_status_code')) {
            $query->where('request_status_code', $request->input('request_status_code'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('start_date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('end_date', '<=', $request->input('date_to'));
        }

        $requests = $query->paginate(15)->withQueryString();

        $leaveTypes = LeaveType::query()
            ->where('active', true)
            ->orderBy('leave_type_name')
            ->get();

        $statuses = collect([
            ['code' => 'PENDING', 'name' => 'Pending'],
            ['code' => 'APPROVED', 'name' => 'Approved'],
            ['code' => 'REJECTED', 'name' => 'Rejected'],
            ['code' => 'CANCELLED', 'name' => 'Cancelled'],
        ]);

        return view('requests.leave-requests.index', compact('requests', 'leaveTypes', 'statuses'));
    }

    public function create(): View
    {
        return view('requests.leave-requests.create', $this->formData());
    }

    public function store(StoreLeaveRequestRequest $request): RedirectResponse
    {
        EmployeeLeaveRequest::query()->create([
            'emp_id' => (int) $request->input('emp_id'),
            'leave_type_id' => (int) $request->input('leave_type_id'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'partial_day_flag' => $request->boolean('partial_day_flag'),
            'partial_start_time' => $request->boolean('partial_day_flag') ? $request->input('partial_start_time') : null,
            'partial_end_time' => $request->boolean('partial_day_flag') ? $request->input('partial_end_time') : null,
            'reason' => $request->input('reason'),
            'attachment_url' => $request->input('attachment_url'),
            'request_status_code' => 'PENDING',
            'approved_by' => null,
            'approved_at' => null,
            'notes' => $request->input('notes'),
        ]);

        return redirect()
            ->route('requests.leave-requests.index')
            ->with('success', 'Leave request berhasil ditambahkan.');
    }

    public function show(int $leave_request): View
    {
        $leaveRequest = EmployeeLeaveRequest::query()
            ->with(['employee', 'leaveType', 'approver'])
            ->findOrFail($leave_request);

        return view('requests.leave-requests.show', compact('leaveRequest'));
    }

    public function edit(int $leave_request): View
    {
        $leaveRequest = EmployeeLeaveRequest::query()
            ->with(['employee', 'leaveType', 'approver'])
            ->findOrFail($leave_request);

        return view('requests.leave-requests.edit', array_merge(
            ['leaveRequest' => $leaveRequest],
            $this->formData()
        ));
    }

    public function update(UpdateLeaveRequestRequest $request, int $leave_request): RedirectResponse
    {
        $leaveRequest = EmployeeLeaveRequest::query()->findOrFail($leave_request);

        if ($leaveRequest->request_status_code === 'APPROVED') {
            return redirect()
                ->route('requests.leave-requests.show', $leaveRequest->leave_request_id)
                ->with('error', 'Leave request yang sudah approved tidak bisa diedit dari form biasa.');
        }

        $leaveRequest->update([
            'emp_id' => (int) $request->input('emp_id'),
            'leave_type_id' => (int) $request->input('leave_type_id'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'partial_day_flag' => $request->boolean('partial_day_flag'),
            'partial_start_time' => $request->boolean('partial_day_flag') ? $request->input('partial_start_time') : null,
            'partial_end_time' => $request->boolean('partial_day_flag') ? $request->input('partial_end_time') : null,
            'reason' => $request->input('reason'),
            'attachment_url' => $request->input('attachment_url'),
            'notes' => $request->input('notes'),
        ]);

        return redirect()
            ->route('requests.leave-requests.index')
            ->with('success', 'Leave request berhasil diperbarui.');
    }

    public function approve(
        ApproveLeaveRequestRequest $request,
        int $leave_request,
        LeaveBalanceResolverService $leaveBalanceResolverService,
        LeaveBalanceMutationService $leaveBalanceMutationService
    ): RedirectResponse {
        $leaveRequest = EmployeeLeaveRequest::query()
            ->with('leaveType')
            ->findOrFail($leave_request);

        $approverEmployeeId = $this->currentApproverEmployeeIdOrNull();

        if (!$approverEmployeeId) {
            return redirect()
                ->route('requests.leave-requests.show', $leaveRequest->leave_request_id)
                ->with('error', 'User login ini belum terhubung ke data employee, sehingga tidak bisa melakukan approval.');
        }

        if ($leaveRequest->request_status_code === 'APPROVED') {
            return redirect()
                ->route('requests.leave-requests.show', $leaveRequest->leave_request_id)
                ->with('error', 'Leave request ini sudah berstatus approved.');
        }

        $leaveType = $leaveRequest->leaveType;

        if ($leaveType && (bool) $leaveType->deduct_quota) {
            $qty = $this->calculateLeaveUsageQty($leaveRequest);
            $workDate = $leaveRequest->start_date->toDateString();

            $resolvedBalance = $leaveBalanceResolverService->resolveAvailableBalance(
                empId: (int) $leaveRequest->emp_id,
                leaveTypeCode: (string) $leaveType->leave_type_code,
                workDate: $workDate
            );

            if (!$resolvedBalance) {
                return redirect()
                    ->route('requests.leave-requests.show', $leaveRequest->leave_request_id)
                    ->with('error', 'Leave balance aktif tidak ditemukan untuk employee dan jenis cuti ini.');
            }

            if ((float) $resolvedBalance['available_balance'] < $qty) {
                return redirect()
                    ->route('requests.leave-requests.show', $leaveRequest->leave_request_id)
                    ->with('error', sprintf(
                        'Saldo cuti tidak cukup. Dibutuhkan %.2f hari, tersedia %.2f hari.',
                        $qty,
                        (float) $resolvedBalance['available_balance']
                    ));
            }
        }

        DB::transaction(function () use (
            $request,
            $leaveRequest,
            $approverEmployeeId,
            $leaveType,
            $leaveBalanceResolverService,
            $leaveBalanceMutationService
        ): void {
            $leaveRequest->update([
                'request_status_code' => 'APPROVED',
                'approved_by' => $approverEmployeeId,
                'approved_at' => $request->input('approved_at')
                    ? Carbon::parse($request->input('approved_at'))
                    : now(),
                'notes' => $this->mergeNotes(
                    $leaveRequest->notes,
                    $request->input('approval_note')
                ),
            ]);

            if (!$leaveType || !(bool) $leaveType->deduct_quota) {
                return;
            }

            $qty = $this->calculateLeaveUsageQty($leaveRequest);
            $workDate = $leaveRequest->start_date->toDateString();

            $resolvedBalance = $leaveBalanceResolverService->resolveAvailableBalance(
                empId: (int) $leaveRequest->emp_id,
                leaveTypeCode: (string) $leaveType->leave_type_code,
                workDate: $workDate
            );

            if (!$resolvedBalance) {
                return;
            }

            $leaveBalanceMutationService->consumeApprovedLeave(
                empId: (int) $leaveRequest->emp_id,
                leaveTypeId: (int) $leaveRequest->leave_type_id,
                workDate: $workDate,
                qty: $qty,
                employeeLeaveBalanceId: (int) $resolvedBalance['employee_leave_balance_id'],
                sourceTypeCode: 'APPROVAL',
                sourceRefId: 'LEAVE_REQUEST:' . $leaveRequest->leave_request_id,
                notes: sprintf(
                    'Approved leave request #%d consumed %.2f day(s).',
                    $leaveRequest->leave_request_id,
                    $qty
                )
            );
        });

        return redirect()
            ->route('requests.leave-requests.show', $leaveRequest->leave_request_id)
            ->with('success', 'Leave request berhasil di-approve dan saldo cuti sudah dipotong jika jenis cuti deduct quota.');
    }

    public function reject(RejectLeaveRequestRequest $request, int $leave_request): RedirectResponse
    {
        $leaveRequest = EmployeeLeaveRequest::query()->findOrFail($leave_request);
        $approverEmployeeId = $this->currentApproverEmployeeIdOrNull();

        if (!$approverEmployeeId) {
            return redirect()
                ->route('requests.leave-requests.show', $leaveRequest->leave_request_id)
                ->with('error', 'User login ini belum terhubung ke data employee, sehingga tidak bisa melakukan approval.');
        }

        if ($leaveRequest->request_status_code === 'APPROVED') {
            return redirect()
                ->route('requests.leave-requests.show', $leaveRequest->leave_request_id)
                ->with('error', 'Leave request yang sudah approved tidak bisa di-reject dari workflow ini.');
        }

        $leaveRequest->update([
            'request_status_code' => 'REJECTED',
            'approved_by' => $approverEmployeeId,
            'approved_at' => $request->input('approved_at')
                ? Carbon::parse($request->input('approved_at'))
                : now(),
            'notes' => $this->mergeNotes(
                $leaveRequest->notes,
                '[REJECTED] ' . trim((string) $request->input('rejection_reason'))
            ),
        ]);

        return redirect()
            ->route('requests.leave-requests.show', $leaveRequest->leave_request_id)
            ->with('success', 'Leave request berhasil di-reject.');
    }

    public function cancel(CancelLeaveRequestRequest $request, int $leave_request): RedirectResponse
    {
        $leaveRequest = EmployeeLeaveRequest::query()->findOrFail($leave_request);

        if ($leaveRequest->request_status_code === 'APPROVED') {
            return redirect()
                ->route('requests.leave-requests.show', $leaveRequest->leave_request_id)
                ->with('error', 'Leave request yang sudah approved tidak bisa di-cancel dari form ini.');
        }

        $leaveRequest->update([
            'request_status_code' => 'CANCELLED',
            'approved_by' => null,
            'approved_at' => null,
            'notes' => $this->mergeNotes(
                $leaveRequest->notes,
                '[CANCELLED] ' . trim((string) $request->input('cancel_reason'))
            ),
        ]);

        return redirect()
            ->route('requests.leave-requests.show', $leaveRequest->leave_request_id)
            ->with('success', 'Leave request berhasil di-cancel.');
    }

    protected function formData(): array
    {
        return [
            'employees' => Employee::query()
                ->where('active', true)
                ->orderBy('full_name')
                ->get(),

            'leaveTypes' => LeaveType::query()
                ->where('active', true)
                ->orderBy('leave_type_name')
                ->get(),
        ];
    }

    protected function mergeNotes(?string $existingNotes, ?string $newLine): ?string
    {
        $existing = trim((string) $existingNotes);
        $incoming = trim((string) $newLine);

        if ($incoming === '') {
            return $existing !== '' ? $existing : null;
        }

        if ($existing === '') {
            return $incoming;
        }

        return $existing . "\n" . $incoming;
    }

    protected function currentApproverEmployeeIdOrNull(): ?int
    {
        $employeeId = auth()->user()?->employee_id;

        return $employeeId ? (int) $employeeId : null;
    }

    protected function calculateLeaveUsageQty($leaveRequest): float
    {
        // Full day
        if (!(bool) $leaveRequest->partial_day_flag) {
            $start = \Carbon\Carbon::parse($leaveRequest->start_date);
            $end = \Carbon\Carbon::parse($leaveRequest->end_date);

            return (float) ($start->diffInDays($end) + 1);
        }

        // Partial day
        return 0.5;
    }
}