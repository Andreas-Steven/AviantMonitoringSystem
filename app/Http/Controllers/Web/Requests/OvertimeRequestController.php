<?php

namespace App\Http\Controllers\Web\Requests;

use App\Domains\Master\Models\Employee;
use App\Domains\Requests\Models\OvertimeRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\Requests\ApproveOvertimeRequestRequest;
use App\Http\Requests\Requests\CancelOvertimeRequestRequest;
use App\Http\Requests\Requests\RejectOvertimeRequestRequest;
use App\Http\Requests\Requests\StoreOvertimeRequestRequest;
use App\Http\Requests\Requests\UpdateOvertimeRequestRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class OvertimeRequestController extends Controller
{
    public function index(Request $request): View
    {
        $query = OvertimeRequest::query()
            ->with(['employee', 'approver'])
            ->orderByRaw("
                CASE request_status_code
                    WHEN 'PENDING' THEN 0
                    WHEN 'APPROVED' THEN 1
                    WHEN 'REJECTED' THEN 2
                    WHEN 'CANCELLED' THEN 3
                    ELSE 9
                END
            ")
            ->orderByDesc('work_date')
            ->orderByDesc('overtime_request_id');

        if ($request->filled('q')) {
            $keyword = trim((string) $request->input('q'));

            $query->whereHas('employee', function ($q) use ($keyword): void {
                $q->where('emp_code', 'ilike', "%{$keyword}%")
                    ->orWhere('full_name', 'ilike', "%{$keyword}%");
            });
        }

        if ($request->filled('request_status_code')) {
            $query->where('request_status_code', $request->input('request_status_code'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('work_date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('work_date', '<=', $request->input('date_to'));
        }

        $requests = $query->paginate(15)->withQueryString();

        $statuses = collect([
            ['code' => 'PENDING', 'name' => 'Pending'],
            ['code' => 'APPROVED', 'name' => 'Approved'],
            ['code' => 'REJECTED', 'name' => 'Rejected'],
            ['code' => 'CANCELLED', 'name' => 'Cancelled'],
        ]);

        return view('requests.overtime-requests.index', compact('requests', 'statuses'));
    }

    public function create(): View
    {
        return view('requests.overtime-requests.create', $this->formData());
    }

    public function store(StoreOvertimeRequestRequest $request): RedirectResponse
    {
        OvertimeRequest::query()->create([
            'emp_id' => (int) $request->input('emp_id'),
            'work_date' => $request->input('work_date'),
            'planned_start_datetime' => $request->input('planned_start_datetime'),
            'planned_end_datetime' => $request->input('planned_end_datetime'),
            'actual_start_datetime' => $request->input('actual_start_datetime'),
            'actual_end_datetime' => $request->input('actual_end_datetime'),
            'reason' => $request->input('reason'),
            'request_status_code' => 'PENDING',
            'approved_by' => null,
            'approved_at' => null,
            'notes' => $request->input('notes'),
        ]);

        return redirect()
            ->route('requests.overtime-requests.index')
            ->with('success', 'Overtime request berhasil ditambahkan.');
    }

    public function show(int $overtime_request): View
    {
        $overtimeRequest = OvertimeRequest::query()
            ->with(['employee', 'approver'])
            ->findOrFail($overtime_request);

        return view('requests.overtime-requests.show', compact('overtimeRequest'));
    }

    public function edit(int $overtime_request): View
    {
        $overtimeRequest = OvertimeRequest::query()
            ->with(['employee', 'approver'])
            ->findOrFail($overtime_request);

        return view('requests.overtime-requests.edit', array_merge(
            ['overtimeRequest' => $overtimeRequest],
            $this->formData()
        ));
    }

    public function update(UpdateOvertimeRequestRequest $request, int $overtime_request): RedirectResponse
    {
        $overtimeRequest = OvertimeRequest::query()->findOrFail($overtime_request);

        if ($overtimeRequest->request_status_code === 'APPROVED') {
            return redirect()
                ->route('requests.overtime-requests.show', $overtimeRequest->overtime_request_id)
                ->with('error', 'Overtime request yang sudah approved tidak bisa diedit dari form biasa.');
        }

        $overtimeRequest->update([
            'emp_id' => (int) $request->input('emp_id'),
            'work_date' => $request->input('work_date'),
            'planned_start_datetime' => $request->input('planned_start_datetime'),
            'planned_end_datetime' => $request->input('planned_end_datetime'),
            'actual_start_datetime' => $request->input('actual_start_datetime'),
            'actual_end_datetime' => $request->input('actual_end_datetime'),
            'reason' => $request->input('reason'),
            'notes' => $request->input('notes'),
        ]);

        return redirect()
            ->route('requests.overtime-requests.index')
            ->with('success', 'Overtime request berhasil diperbarui.');
    }

    public function approve(ApproveOvertimeRequestRequest $request, int $overtime_request): RedirectResponse
    {
        $overtimeRequest = OvertimeRequest::query()->findOrFail($overtime_request);
        $approverEmployeeId = $this->currentApproverEmployeeIdOrNull();

        if (!$approverEmployeeId) {
            return redirect()
                ->route('requests.overtime-requests.show', $overtimeRequest->overtime_request_id)
                ->with('error', 'User login ini belum terhubung ke data employee, sehingga tidak bisa melakukan approval.');
        }

        if ($overtimeRequest->request_status_code === 'APPROVED') {
            return redirect()
                ->route('requests.overtime-requests.show', $overtimeRequest->overtime_request_id)
                ->with('error', 'Overtime request ini sudah berstatus approved.');
        }

        $overtimeRequest->update([
            'request_status_code' => 'APPROVED',
            'approved_by' => $approverEmployeeId,
            'approved_at' => $request->input('approved_at')
                ? Carbon::parse($request->input('approved_at'))
                : now(),
            'notes' => $this->mergeNotes(
                $overtimeRequest->notes,
                $request->input('approval_note')
            ),
        ]);

        return redirect()
            ->route('requests.overtime-requests.show', $overtimeRequest->overtime_request_id)
            ->with('success', 'Overtime request berhasil di-approve.');
    }

    public function reject(RejectOvertimeRequestRequest $request, int $overtime_request): RedirectResponse
    {
        $overtimeRequest = OvertimeRequest::query()->findOrFail($overtime_request);
        $approverEmployeeId = $this->currentApproverEmployeeIdOrNull();

        if (!$approverEmployeeId) {
            return redirect()
                ->route('requests.overtime-requests.show', $overtimeRequest->overtime_request_id)
                ->with('error', 'User login ini belum terhubung ke data employee, sehingga tidak bisa melakukan approval.');
        }

        if ($overtimeRequest->request_status_code === 'APPROVED') {
            return redirect()
                ->route('requests.overtime-requests.show', $overtimeRequest->overtime_request_id)
                ->with('error', 'Overtime request yang sudah approved tidak bisa di-reject dari workflow ini.');
        }

        $overtimeRequest->update([
            'request_status_code' => 'REJECTED',
            'approved_by' => $approverEmployeeId,
            'approved_at' => $request->input('approved_at')
                ? Carbon::parse($request->input('approved_at'))
                : now(),
            'notes' => $this->mergeNotes(
                $overtimeRequest->notes,
                '[REJECTED] ' . trim((string) $request->input('rejection_reason'))
            ),
        ]);

        return redirect()
            ->route('requests.overtime-requests.show', $overtimeRequest->overtime_request_id)
            ->with('success', 'Overtime request berhasil di-reject.');
    }

    public function cancel(CancelOvertimeRequestRequest $request, int $overtime_request): RedirectResponse
    {
        $overtimeRequest = OvertimeRequest::query()->findOrFail($overtime_request);

        if ($overtimeRequest->request_status_code === 'APPROVED') {
            return redirect()
                ->route('requests.overtime-requests.show', $overtimeRequest->overtime_request_id)
                ->with('error', 'Overtime request yang sudah approved tidak bisa di-cancel dari form ini.');
        }

        $overtimeRequest->update([
            'request_status_code' => 'CANCELLED',
            'approved_by' => null,
            'approved_at' => null,
            'notes' => $this->mergeNotes(
                $overtimeRequest->notes,
                '[CANCELLED] ' . trim((string) $request->input('cancel_reason'))
            ),
        ]);

        return redirect()
            ->route('requests.overtime-requests.show', $overtimeRequest->overtime_request_id)
            ->with('success', 'Overtime request berhasil di-cancel.');
    }

    protected function formData(): array
    {
        return [
            'employees' => Employee::query()
                ->where('active', true)
                ->orderBy('full_name')
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
}