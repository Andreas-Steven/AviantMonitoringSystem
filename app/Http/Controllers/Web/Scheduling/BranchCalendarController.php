<?php

namespace App\Http\Controllers\Web\Scheduling;

use App\Domains\Master\Models\Branch;
use App\Domains\Scheduling\Actions\GenerateBranchCalendarAction;
use App\Domains\Scheduling\DTOs\GenerateBranchCalendarData;
use App\Domains\Scheduling\Models\BranchCalendar;
use App\Domains\Scheduling\Models\DayType;
use App\Domains\Scheduling\Services\BranchCalendarIndexViewService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Scheduling\GenerateBranchCalendarRequest;
use App\Http\Requests\Scheduling\StoreBranchCalendarRequest;
use App\Http\Requests\Scheduling\UpdateBranchCalendarDayRequest;
use App\Http\Requests\Scheduling\UpdateBranchCalendarRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BranchCalendarController extends Controller
{
    public function index(
        Request $request,
        BranchCalendarIndexViewService $viewService
    ): View {
        $payload = $viewService->build([
            'branch_id' => $request->input('branch_id'),
            'period_code' => $request->input('period_code'),
            'view_mode' => $request->input('view_mode', 'grid'),
            'selected_date' => $request->input('selected_date'),
            'show_notes_only' => $request->boolean('show_notes_only'),
            'show_non_workday_only' => $request->boolean('show_non_workday_only'),
            'edit_day' => $request->boolean('edit_day'),
        ]);

        return view('scheduling.branch-calendars.index', $payload);
    }

    public function generate(
        GenerateBranchCalendarRequest $request,
        GenerateBranchCalendarAction $action
    ): RedirectResponse {
        $action->execute(new GenerateBranchCalendarData(
            branchId: (int) $request->input('branch_id'),
            payrollPeriodId: (int) $request->input('payroll_period_id'),
            overwriteExisting: (bool) $request->boolean('overwrite_existing')
        ));

        return redirect()
            ->route('scheduling.branch-calendars.index', [
                'branch_id' => $request->input('branch_id'),
                'period_code' => $request->input('period_code'),
                'view_mode' => $request->input('view_mode', 'grid'),
            ])
            ->with('success', 'Branch calendar generated successfully.');
    }

    public function updateDay(
        UpdateBranchCalendarDayRequest $request,
        BranchCalendar $branchCalendar
    ): RedirectResponse {
        $branchCalendar->update([
            'day_type_code' => $request->input('day_type_code'),
            'is_workday' => $request->boolean('is_workday'),
            'notes' => $request->input('notes'),
        ]);

        return redirect()
            ->route('scheduling.branch-calendars.index', [
                'branch_id' => $branchCalendar->branch_id,
                'period_code' => $request->input('period_code'),
                'view_mode' => $request->input('view_mode', 'grid'),
                'selected_date' => optional($branchCalendar->work_date)->format('Y-m-d'),
            ])
            ->with('success', 'Calendar day updated successfully.');
    }

    public function create(): View
    {
        return view('scheduling.branch-calendars.create', $this->formData());
    }

    public function store(StoreBranchCalendarRequest $request): RedirectResponse
    {
        BranchCalendar::create($request->validated());

        return redirect()
            ->route('scheduling.branch-calendars.index')
            ->with('success', 'Branch calendar berhasil ditambahkan.');
    }

    public function show(BranchCalendar $branchCalendar): View
    {
        $calendar = $branchCalendar->load(['branch', 'dayType']);

        return view('scheduling.branch-calendars.show', compact('calendar'));
    }

    public function edit(BranchCalendar $branchCalendar): View
    {
        return view('scheduling.branch-calendars.edit', array_merge(
            ['calendar' => $branchCalendar],
            $this->formData()
        ));
    }

    public function update(
        UpdateBranchCalendarRequest $request,
        BranchCalendar $branchCalendar
    ): RedirectResponse {
        $branchCalendar->update($request->validated());

        return redirect()
            ->route('scheduling.branch-calendars.index')
            ->with('success', 'Branch calendar berhasil diperbarui.');
    }

    protected function formData(): array
    {
        return [
            'branches' => Branch::where('active', true)->orderBy('branch_name')->get(),
            'dayTypes' => DayType::orderBy('day_type_name')->get(),
        ];
    }
}