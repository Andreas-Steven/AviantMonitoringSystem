<?php

namespace App\Http\Controllers\Web\Requests;

use App\Domains\Requests\Models\LeaveType;
use App\Domains\Requests\Services\LeaveOpeningBalanceImportService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Requests\ImportLeaveOpeningBalanceRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class LeaveBalanceOpeningImportController extends Controller
{
    public function create(): View
    {
        abort_unless(auth()->user()?->hasPermission('leave.manage'), 403);

        return view('requests.leave-balances.import-opening', [
            'leaveTypes' => LeaveType::query()
                ->where('active', true)
                ->orderBy('leave_type_name')
                ->get(),
        ]);
    }

    public function store(
        ImportLeaveOpeningBalanceRequest $request,
        LeaveOpeningBalanceImportService $service
    ): RedirectResponse {
        $result = $service->import($request->validated());

        return redirect()
            ->route('requests.leave-balances.index')
            ->with('success', "Import opening balance selesai. Success: {$result['success_rows']}, skipped: {$result['skipped_rows']}, failed: {$result['failed_rows']}.")
            ->with('import_result', $result);
    }
}