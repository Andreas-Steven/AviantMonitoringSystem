<?php

namespace App\Http\Controllers\Web\Scheduling;

use App\Http\Controllers\Controller;
use App\Http\Requests\Scheduling\StoreEmployeeWorkPatternAssignmentRequest;
use App\Http\Requests\Scheduling\UpdateEmployeeWorkPatternAssignmentRequest;
use App\Http\Requests\Scheduling\StoreBulkEmployeeWorkPatternAssignmentRequest;
use App\Domains\Scheduling\Services\EmployeeWorkPatternAssignmentDomainService;
use App\Domains\Scheduling\Services\EmployeeWorkPatternAssignmentCoverageService;
use App\Domains\Scheduling\Models\EmployeeWorkPatternAssignment;
use App\Domains\Scheduling\Actions\BulkAssignEmployeeWorkPatternsAction;
use App\Domains\Scheduling\Models\WorkPattern;
use App\Domains\Master\Models\Employee;
use App\Domains\Master\Models\EmploymentType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeWorkPatternAssignmentController extends Controller
{
    public function index(Request $request): View
    {
        $query = EmployeeWorkPatternAssignment::with(['employee', 'workPattern'])
            ->orderByDesc('effective_start_date')
            ->orderByDesc('employee_work_pattern_assignment_id');

        if ($request->filled('q')) {
            $keyword = $request->string('q');

            $query->whereHas('employee', function ($q) use ($keyword) {
                $q->where('emp_code', 'ilike', "%{$keyword}%")
                    ->orWhere('full_name', 'ilike', "%{$keyword}%");
            });
        }

        if ($request->filled('work_pattern_id')) {
            $query->where('work_pattern_id', (int) $request->work_pattern_id);
        }

        $assignments = $query->paginate(15)->withQueryString();

        $workPatterns = WorkPattern::where('active', true)
            ->orderBy('work_pattern_name')
            ->get();

        return view('scheduling.employee-work-pattern-assignments.index', compact(
            'assignments',
            'workPatterns'
        ));
    }

    public function create(Request $request): View
    {
        return view('scheduling.employee-work-pattern-assignments.create', array_merge(
            [
                'prefillEmpId' => $request->filled('emp_id')
                    ? (int) $request->input('emp_id')
                    : null,
            ],
            $this->formData()
        ));
    }

    public function store(
        StoreEmployeeWorkPatternAssignmentRequest $request,
        EmployeeWorkPatternAssignmentDomainService $domainService
    ): RedirectResponse {
        $data = $request->validated();

        $domainService->assertSingleStoreAllowed($data);

        EmployeeWorkPatternAssignment::create($data);

        return redirect()
            ->route('scheduling.employee-work-pattern-assignments.index')
            ->with('success', 'Employee work pattern assignment berhasil ditambahkan.');
    }

    public function show(int $employee_work_pattern_assignment): View
    {
        $assignment = EmployeeWorkPatternAssignment::with(['employee', 'workPattern'])
            ->findOrFail($employee_work_pattern_assignment);

        return view('scheduling.employee-work-pattern-assignments.show', compact('assignment'));
    }

    public function edit(int $employee_work_pattern_assignment): View
    {
        $assignment = EmployeeWorkPatternAssignment::findOrFail($employee_work_pattern_assignment);

        return view('scheduling.employee-work-pattern-assignments.edit', array_merge(
            [
                'assignment' => $assignment,
                'prefillEmpId' => null,
            ],
            $this->formData()
        ));
    }

    public function update(
        UpdateEmployeeWorkPatternAssignmentRequest $request,
        int $employee_work_pattern_assignment,
        EmployeeWorkPatternAssignmentDomainService $domainService
    ): RedirectResponse {
        $assignment = EmployeeWorkPatternAssignment::findOrFail($employee_work_pattern_assignment);
        $data = $request->validated();

        $domainService->assertSingleUpdateAllowed($assignment, $data);

        $assignment->update($data);

        return redirect()
            ->route('scheduling.employee-work-pattern-assignments.index')
            ->with('success', 'Employee work pattern assignment berhasil diperbarui.');
    }

    protected function formData(): array
    {
        return [
            'employees' => Employee::where('active', true)
                ->orderBy('full_name')
                ->get(),

            'workPatterns' => WorkPattern::where('active', true)
                ->orderBy('work_pattern_name')
                ->get(),
        ];
    }

    public function coverage(
        Request $request,
        EmployeeWorkPatternAssignmentCoverageService $coverageService
    ): View {
        $filters = [
            'reference_date' => $request->input('reference_date'),
            'q' => $request->input('q'),
            'employment_type_id' => $request->input('employment_type_id'),
            'coverage_status' => $request->input('coverage_status'),
        ];

        $coverage = $coverageService->getCoverage($filters);

        $employmentTypes = EmploymentType::query()
            ->where('active', true)
            ->orderBy('employment_type_name')
            ->get();

        return view('scheduling.employee-work-pattern-assignments.coverage', [
            'rows' => $coverage['rows'],
            'summary' => $coverage['summary'],
            'referenceDate' => $coverage['referenceDate'],
            'employmentTypes' => $employmentTypes,
        ]);
    }

    public function bulkCreate(
        Request $request,
        EmployeeWorkPatternAssignmentCoverageService $coverageService
    ): View {
        $filters = [
            'reference_date' => $request->input('reference_date'),
            'q' => $request->input('q'),
            'employment_type_id' => $request->input('employment_type_id'),
            'candidate_status' => $request->input('candidate_status', 'ALL_ACTIVE_EMPLOYEES'),
        ];

        $coverage = $coverageService->getCoverage($filters);

        return view('scheduling.employee-work-pattern-assignments.bulk-create', [
            'referenceDate' => $coverage['referenceDate'],
            'candidateRows' => $coverage['rows'],
            'employmentTypes' => EmploymentType::query()
                ->where('active', true)
                ->orderBy('employment_type_name')
                ->get(),
            'workPatterns' => WorkPattern::query()
                ->where('active', true)
                ->orderBy('work_pattern_name')
                ->get(),
        ]);
    }

    public function bulkStore(
        StoreBulkEmployeeWorkPatternAssignmentRequest $request,
        BulkAssignEmployeeWorkPatternsAction $action
    ): RedirectResponse {
        $result = $action->execute($request->validated());

        $createdCount = (int) ($result['created_count'] ?? 0);
        $replacedCount = (int) ($result['replaced_count'] ?? 0);
        $skippedCount = (int) ($result['skipped_count'] ?? 0);

        return redirect()
            ->route('scheduling.employee-work-pattern-assignments.bulk-create', [
                'reference_date' => $request->input('reference_date'),
                'q' => $request->input('q'),
                'employment_type_id' => $request->input('employment_type_id'),
                'candidate_status' => $request->input('candidate_status', 'ALL_ACTIVE_EMPLOYEES'),
            ])
            ->with('bulk_work_pattern_assignment_result', $result)
            ->with(
                'success',
                "Bulk work pattern assignment selesai. {$createdCount} assignment baru dibuat, {$replacedCount} assignment aktif diganti, dan {$skippedCount} employee tidak diproses."
            );
    }
}