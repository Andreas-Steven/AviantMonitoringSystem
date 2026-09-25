<?php

namespace App\Http\Controllers\Web\Scheduling;

use App\Domains\Master\Models\Branch;
use App\Domains\Scheduling\Services\HolidayImportService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Scheduling\PreviewHolidayImportRequest;
use App\Http\Requests\Scheduling\StoreHolidayImportRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class HolidayImportController extends Controller
{
    public function create(): View
    {
        return view('scheduling.holiday-events.import-preview', [
            'year' => now()->year,
            'source' => 'calendarific',
            'rows' => collect(),
            'branches' => $this->branches(),
            'scopeMode' => 'all',
        ]);
    }

    public function preview(
        PreviewHolidayImportRequest $request,
        HolidayImportService $holidayImportService
    ): View {
        $source = $request->input('source', 'calendarific');

        $rows = $holidayImportService->preview(
            source: $source,
            year: (int) $request->input('year')
        );

        return view('scheduling.holiday-events.import-preview', [
            'year' => (int) $request->input('year'),
            'source' => $source,
            'rows' => $rows,
            'branches' => $this->branches(),
            'scopeMode' => 'all',
        ]);
    }

    public function store(
        StoreHolidayImportRequest $request,
        HolidayImportService $holidayImportService
    ): RedirectResponse {
        $result = $holidayImportService->importSelected(
            rows: $request->input('selected_rows', []),
            scopeMode: $request->input('scope_mode', 'all'),
            branchIds: $request->input('branch_ids', []),
        );

        return redirect()
            ->route('scheduling.holiday-events.index')
            ->with(
                'success',
                "Import holiday selesai. {$result['created_count']} holiday dibuat, {$result['skipped_count']} dilewati."
            )
            ->with('holiday_import_result', $result);
    }

    protected function branches()
    {
        return Branch::query()
            ->where('active', true)
            ->orderBy('branch_name')
            ->get();
    }
}