<?php

namespace App\Http\Controllers\Web\Scheduling;

use App\Domains\Scheduling\Services\EmployeeShiftAssignmentImportService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Scheduling\ImportEmployeeShiftAssignmentRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class EmployeeShiftAssignmentImportController extends Controller
{
    public function create(): View
    {
        return view('scheduling.employee-shift-assignments.import');
    }

    public function store(
        ImportEmployeeShiftAssignmentRequest $request,
        EmployeeShiftAssignmentImportService $service
    ): RedirectResponse {
        $result = $service->import($request->file('file'));

        return redirect()
            ->route('scheduling.employee-shift-assignments.index')
            ->with(
                'success',
                "Import employee shift assignment selesai. Created: {$result['created_rows']}, skipped: {$result['skipped_rows']}, failed: {$result['failed_rows']}."
            )
            ->with('import_result', $result);
    }
}