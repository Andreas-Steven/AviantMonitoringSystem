<?php

namespace App\Http\Controllers\Web\Scheduling;

use App\Http\Controllers\Controller;
use App\Http\Requests\Scheduling\StoreBulkEmployeeShiftAssignmentRequest;
use App\Http\Requests\Scheduling\StoreEmployeeShiftAssignmentRequest;
use App\Http\Requests\Scheduling\UpdateEmployeeShiftAssignmentRequest;
use App\Domains\Master\Models\Employee;
use App\Domains\Master\Models\EmploymentType;
use App\Domains\Scheduling\Actions\BulkAssignEmployeeShiftsAction;
use App\Domains\Scheduling\Models\AssignmentType;
use App\Domains\Scheduling\Models\EmployeeShiftAssignment;
use App\Domains\Scheduling\Models\Shift;
use App\Domains\Scheduling\Services\EmployeeShiftAssignmentCoverageService;
use App\Domains\Scheduling\Services\EmployeeShiftAssignmentDomainService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeShiftAssignmentController extends Controller
{
    public function index(Request $request): View
    {
        $query = EmployeeShiftAssignment::with(['employee', 'shift', 'assignmentType'])
            ->orderByDesc('effective_start_date')
            ->orderByDesc('employee_shift_assignment_id');

        if ($request->filled('q')) {
            $keyword = trim((string) $request->string('q'));

            $query->whereHas('employee', function ($q) use ($keyword): void {
                $q->where('emp_code', 'ilike', "%{$keyword}%")
                    ->orWhere('full_name', 'ilike', "%{$keyword}%");
            });
        }

        if ($request->filled('shift_id')) {
            $query->where('shift_id', (int) $request->input('shift_id'));
        }

        if ($request->filled('assignment_type_code')) {
            $query->where('assignment_type_code', (string) $request->input('assignment_type_code'));
        }

        $assignments = $query->paginate(15)->withQueryString();

        return view('scheduling.employee-shift-assignments.index', [
            'assignments' => $assignments,
            'shifts' => Shift::query()->where('active', true)->orderBy('shift_name')->get(),
            'assignmentTypes' => AssignmentType::query()->orderBy('assignment_type_name')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        $prefillEmpId = $request->filled('emp_id') ? (int) $request->input('emp_id') : null;

        return view('scheduling.employee-shift-assignments.create', array_merge(
            ['prefillEmpId' => $prefillEmpId],
            $this->formData()
        ));
    }

    public function store(
        StoreEmployeeShiftAssignmentRequest $request,
        EmployeeShiftAssignmentDomainService $domainService
    ): RedirectResponse {
        $data = $request->validated();

        $domainService->assertSingleStoreAllowed($data);

        EmployeeShiftAssignment::create($data);

        return redirect()
            ->route('scheduling.employee-shift-assignments.index')
            ->with('success', 'Employee shift assignment berhasil ditambahkan.');
    }

    public function show(int $employee_shift_assignment): View
    {
        $assignment = EmployeeShiftAssignment::with(['employee', 'shift', 'assignmentType'])
            ->findOrFail($employee_shift_assignment);

        return view('scheduling.employee-shift-assignments.show', compact('assignment'));
    }

    public function edit(int $employee_shift_assignment): View
    {
        $assignment = EmployeeShiftAssignment::findOrFail($employee_shift_assignment);

        return view('scheduling.employee-shift-assignments.edit', array_merge(
            ['assignment' => $assignment],
            $this->formData($assignment)
        ));
    }

    public function update(
        UpdateEmployeeShiftAssignmentRequest $request,
        int $employee_shift_assignment,
        EmployeeShiftAssignmentDomainService $domainService
    ): RedirectResponse {
        $assignment = EmployeeShiftAssignment::findOrFail($employee_shift_assignment);
        $data = $request->validated();

        $domainService->assertSingleUpdateAllowed($assignment, $data);

        $assignment->update($data);

        return redirect()
            ->route('scheduling.employee-shift-assignments.index')
            ->with('success', 'Employee shift assignment berhasil diperbarui.');
    }

    public function coverage(
        Request $request,
        EmployeeShiftAssignmentCoverageService $coverageService
    ): View {
        $filters = [
            'reference_date' => $request->input('reference_date'),
            'q' => $request->input('q'),
            'employment_type_id' => $request->input('employment_type_id'),
            'coverage_status' => $request->input('coverage_status'),
        ];

        $coverage = $coverageService->getCoverage($filters);

        return view('scheduling.employee-shift-assignments.coverage', [
            'rows' => $coverage['rows'],
            'summary' => $coverage['summary'],
            'referenceDate' => $coverage['referenceDate'],
            'employmentTypes' => EmploymentType::query()
                ->where('active', true)
                ->orderBy('employment_type_name')
                ->get(),
        ]);
    }

    public function bulkCreate(
        Request $request,
        EmployeeShiftAssignmentCoverageService $coverageService
    ): View {
        $filters = [
            'reference_date' => $request->input('reference_date'),
            'q' => $request->input('q'),
            'employment_type_id' => $request->input('employment_type_id'),
            'candidate_status' => $request->input('candidate_status', 'ALL_ACTIVE_EMPLOYEES'),
        ];

        $coverage = $coverageService->getCoverage($filters);

        return view('scheduling.employee-shift-assignments.bulk-create', [
            'referenceDate' => $coverage['referenceDate'],
            'candidateRows' => $coverage['rows'],
            'employmentTypes' => EmploymentType::query()
                ->where('active', true)
                ->orderBy('employment_type_name')
                ->get(),
            'shifts' => Shift::query()
                ->where('active', true)
                ->orderBy('shift_name')
                ->get(),
            'assignmentTypes' => AssignmentType::query()
                ->orderBy('assignment_type_name')
                ->get(),
        ]);
    }

    public function bulkStore(
        StoreBulkEmployeeShiftAssignmentRequest $request,
        BulkAssignEmployeeShiftsAction $action
    ): RedirectResponse {
        $result = $action->execute($request->validated());

        $createdCount = (int) ($result['created_count'] ?? 0);
        $replacedCount = (int) ($result['replaced_count'] ?? 0);
        $skippedCount = (int) ($result['skipped_count'] ?? 0);

        return redirect()
            ->route('scheduling.employee-shift-assignments.bulk-create', [
                'reference_date' => $request->input('reference_date'),
                'q' => $request->input('q'),
                'employment_type_id' => $request->input('employment_type_id'),
                'candidate_status' => $request->input('candidate_status', 'ALL_ACTIVE_EMPLOYEES'),
            ])
            ->with('bulk_shift_assignment_result', $result)
            ->with(
                'success',
                "Bulk shift assignment selesai. {$createdCount} assignment baru dibuat, {$replacedCount} assignment aktif diganti, dan {$skippedCount} employee tidak diproses."
            );
    }

    protected function formData(?EmployeeShiftAssignment $assignment = null): array
    {
        $currentEmpId = $assignment?->emp_id;
        $currentShiftId = $assignment?->shift_id;
        $currentTypeCode = $assignment?->assignment_type_code;

        return [
            'employees' => Employee::query()
                ->where(function ($query) use ($currentEmpId): void {
                    $query->where('active', true);

                    if ($currentEmpId) {
                        $query->orWhere('emp_id', $currentEmpId);
                    }
                })
                ->orderBy('full_name')
                ->get(),

            'shifts' => Shift::query()
                ->where(function ($query) use ($currentShiftId): void {
                    $query->where('active', true);

                    if ($currentShiftId) {
                        $query->orWhere('shift_id', $currentShiftId);
                    }
                })
                ->orderBy('shift_name')
                ->get(),

            'assignmentTypes' => AssignmentType::query()
                ->where(function ($query) use ($currentTypeCode): void {
                    if ($currentTypeCode) {
                        $query->where('assignment_type_code', $currentTypeCode)
                            ->orWhereRaw('1 = 1');
                    }
                })
                ->orderBy('assignment_type_name')
                ->get(),
        ];
    }
}