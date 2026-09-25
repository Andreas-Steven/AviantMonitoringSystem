<?php

namespace App\Http\Controllers\Web\Scheduling;

use App\Http\Controllers\Controller;
use App\Http\Requests\Scheduling\StoreEmployeeShiftRosterRequest;
use App\Http\Requests\Scheduling\UpdateEmployeeShiftRosterRequest;
use App\Domains\Scheduling\Services\EmployeeShiftRosterCoverageService;
use App\Domains\Scheduling\Models\EmployeeShiftRoster;
use App\Domains\Scheduling\Models\Shift;
use App\Domains\Scheduling\Models\SourceType;
use App\Domains\Master\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeShiftRosterController extends Controller
{
    public function index(Request $request): View
    {
        $query = EmployeeShiftRoster::with(['employee', 'shift', 'sourceType'])
            ->orderByDesc('work_date')
            ->orderByDesc('roster_id');

        if ($request->filled('q')) {
            $keyword = $request->string('q');
            $query->whereHas('employee', function ($q) use ($keyword) {
                $q->where('emp_code', 'ilike', "%{$keyword}%")
                  ->orWhere('full_name', 'ilike', "%{$keyword}%");
            });
        }

        if ($request->filled('shift_id')) {
            $query->where('shift_id', (int) $request->shift_id);
        }

        if ($request->filled('source_type_code')) {
            $query->where('source_type_code', $request->string('source_type_code'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('work_date', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('work_date', '<=', $request->date('date_to'));
        }

        $rosters = $query->paginate(20)->withQueryString();

        $shifts = Shift::where('active', true)->orderBy('shift_name')->get();
        $sourceTypes = SourceType::orderBy('source_type_name')->get();

        return view('scheduling.employee-shift-rosters.index', compact('rosters', 'shifts', 'sourceTypes'));
    }

    public function create(): View
    {
        return view('scheduling.employee-shift-rosters.create', $this->formData());
    }

    public function store(StoreEmployeeShiftRosterRequest $request): RedirectResponse
    {
        EmployeeShiftRoster::create($request->validated());

        return redirect()
            ->route('scheduling.employee-shift-rosters.index')
            ->with('success', 'Employee shift roster berhasil ditambahkan.');
    }

    public function show(int $employee_shift_roster): View
    {
        $roster = EmployeeShiftRoster::with(['employee', 'shift', 'sourceType'])
            ->findOrFail($employee_shift_roster);

        return view('scheduling.employee-shift-rosters.show', compact('roster'));
    }

    public function edit(int $employee_shift_roster): View
    {
        $roster = EmployeeShiftRoster::findOrFail($employee_shift_roster);

        return view('scheduling.employee-shift-rosters.edit', array_merge(
            ['roster' => $roster],
            $this->formData()
        ));
    }

    public function update(UpdateEmployeeShiftRosterRequest $request, int $employee_shift_roster): RedirectResponse
    {
        $roster = EmployeeShiftRoster::findOrFail($employee_shift_roster);
        $roster->update($request->validated());

        return redirect()
            ->route('scheduling.employee-shift-rosters.index')
            ->with('success', 'Employee shift roster berhasil diperbarui.');
    }

    protected function formData(): array
    {
        return [
            'employees' => Employee::where('active', true)->orderBy('full_name')->get(),
            'shifts' => Shift::where('active', true)->orderBy('shift_name')->get(),
            'sourceTypes' => SourceType::orderBy('source_type_name')->get(),
        ];
    }

    public function coverage(
        Request $request,
        EmployeeShiftRosterCoverageService $coverageService
    ): View {
        $filters = [
            'reference_date' => $request->input('reference_date'),
            'q' => $request->input('q'),
            'coverage_status' => $request->input('coverage_status'),
        ];

        $coverage = $coverageService->getCoverage($filters);

        return view('scheduling.employee-shift-rosters.coverage', [
            'rows' => $coverage['rows'],
            'summary' => $coverage['summary'],
            'referenceDate' => $coverage['referenceDate'],
        ]);
    }
}