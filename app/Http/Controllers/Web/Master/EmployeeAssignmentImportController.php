<?php

namespace App\Http\Controllers\Web\Master;

use App\Domains\Master\Services\EmployeeAssignmentImportService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\ImportEmployeeAssignmentRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class EmployeeAssignmentImportController extends Controller
{
    public function create(): View
    {
        return view('master.employee-assignments.import');
    }

    public function store(
        ImportEmployeeAssignmentRequest $request,
        EmployeeAssignmentImportService $service
    ): RedirectResponse {
        $result = $service->import($request->file('file'));

        return redirect()
            ->route('master.employee-assignments.index')
            ->with(
                'success',
                "Import employee assignment selesai. Created: {$result['created_rows']}, skipped: {$result['skipped_rows']}, failed: {$result['failed_rows']}."
            )
            ->with('import_result', $result);
    }
}