<?php

namespace App\Http\Controllers\Web\Master;

use App\Domains\Master\Services\EmployeeImportService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\ImportEmployeeRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class EmployeeImportController extends Controller
{
    public function create(): View
    {
        return view('master.employees.import');
    }

    public function store(
        ImportEmployeeRequest $request,
        EmployeeImportService $service
    ): RedirectResponse {
        $result = $service->import($request->file('file'));

        return redirect()
            ->route('master.employees.index')
            ->with('success', "Import employee selesai. Created: {$result['created_rows']}, updated: {$result['updated_rows']}, skipped: {$result['skipped_rows']}, failed: {$result['failed_rows']}.")
            ->with('import_result', $result);
    }
}