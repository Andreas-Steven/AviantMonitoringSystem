<?php

namespace App\Http\Controllers\Web\Scheduling;

use App\Domains\Scheduling\Services\EmployeeWorkPatternAssignmentImportService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Scheduling\ImportEmployeeWorkPatternAssignmentRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class EmployeeWorkPatternAssignmentImportController extends Controller
{
    public function create(): View
    {
        return view('scheduling.employee-work-pattern-assignments.import');
    }

    public function store(
        ImportEmployeeWorkPatternAssignmentRequest $request,
        EmployeeWorkPatternAssignmentImportService $service
    ): RedirectResponse {
        $result = $service->import($request->file('file'));

        return redirect()
            ->route('scheduling.employee-work-pattern-assignments.index')
            ->with(
                'success',
                "Import employee work pattern assignment selesai. Created: {$result['created_rows']}, skipped: {$result['skipped_rows']}, failed: {$result['failed_rows']}."
            )
            ->with('import_result', $result);
    }
}