<?php

namespace App\Http\Controllers\Web\Master;

use App\Domains\Master\Models\Branch;
use App\Domains\Master\Models\Department;
use App\Domains\Master\Models\Employee;
use App\Domains\Master\Models\EmployeeAssignment;
use App\Domains\Master\Models\Grade;
use App\Domains\Master\Models\PositionRole;
use App\Domains\Master\Models\EmploymentType;
use App\Domains\Master\Services\EmployeeAssignmentCoverageService;
use App\Domains\Master\Actions\BulkAssignEmployeesAction;
use App\Domains\Master\Services\EmployeeAssignmentDomainService;
use App\Http\Requests\Master\StoreBulkEmployeeAssignmentRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreEmployeeAssignmentRequest;
use App\Http\Requests\Master\UpdateEmployeeAssignmentRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class EmployeeAssignmentController extends Controller
{
    public function index(Request $request): View
    {
        $today = Carbon::today()->toDateString();

        $query = EmployeeAssignment::with(['employee', 'branch', 'department', 'role.department', 'grade'])
            ->orderByDesc('effective_start_date')
            ->orderByDesc('assignment_id');

        if ($request->filled('q')) {
            $keyword = trim((string) $request->string('q'));

            $query->whereHas('employee', function ($q) use ($keyword) {
                $q->where('emp_code', 'ilike', "%{$keyword}%")
                    ->orWhere('full_name', 'ilike', "%{$keyword}%");
            });
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', (int) $request->input('branch_id'));
        }

        if ($request->filled('is_primary')) {
            $query->where('is_primary', (bool) ((int) $request->input('is_primary')));
        }

        if ($request->filled('assignment_status')) {
            $status = (string) $request->input('assignment_status');

            if ($status === 'current') {
                $query
                    ->whereDate('effective_start_date', '<=', $today)
                    ->where(function ($q) use ($today) {
                        $q->whereNull('effective_end_date')
                            ->orWhereDate('effective_end_date', '>=', $today);
                    });
            } elseif ($status === 'historical') {
                $query->whereNotNull('effective_end_date')
                    ->whereDate('effective_end_date', '<', $today);
            } elseif ($status === 'upcoming') {
                $query->whereDate('effective_start_date', '>', $today);
            }
        }

        if ($request->filled('emp_id')) {
            $query->where('emp_id', (int) $request->input('emp_id'));
        }

        $assignments = $query->paginate(15)->withQueryString();

        $branches = Branch::where('active', true)
            ->orderBy('branch_name')
            ->get();

        return view('master.employee-assignments.index', compact('assignments', 'branches', 'today'));
    }

    public function create(Request $request): View
    {
        $prefillEmpId = $request->filled('emp_id') ? (int) $request->input('emp_id') : null;

        return view('master.employee-assignments.create', array_merge(
            ['prefillEmpId' => $prefillEmpId],
            $this->formData()
        ));
    }

    public function store(
        StoreEmployeeAssignmentRequest $request,
        EmployeeAssignmentDomainService $domainService
    ): RedirectResponse {
        $data = $request->validated();

        $domainService->assertSingleStoreAllowed($data);

        EmployeeAssignment::create($data);

        return redirect()
            ->route('master.employee-assignments.index')
            ->with('success', 'Employee assignment berhasil ditambahkan.');
}

    public function show(int $employee_assignment): View
    {
        $assignment = EmployeeAssignment::with(['employee', 'branch', 'department', 'role.department', 'grade'])
            ->findOrFail($employee_assignment);

        $today = Carbon::today()->toDateString();

        $status = 'current';

        if ($assignment->effective_start_date?->format('Y-m-d') > $today) {
            $status = 'upcoming';
        } elseif ($assignment->effective_end_date && $assignment->effective_end_date->format('Y-m-d') < $today) {
            $status = 'historical';
        }

        return view('master.employee-assignments.show', compact('assignment', 'today', 'status'));
    }

    public function edit(int $employee_assignment): View
    {
        $assignment = EmployeeAssignment::findOrFail($employee_assignment);

        return view('master.employee-assignments.edit', array_merge(
            ['assignment' => $assignment],
            $this->formData($assignment)
        ));
    }

    public function update(
        UpdateEmployeeAssignmentRequest $request,
        int $employee_assignment,
        EmployeeAssignmentDomainService $domainService
    ): RedirectResponse {
        $assignment = EmployeeAssignment::findOrFail($employee_assignment);
        $data = $request->validated();

        $domainService->assertSingleUpdateAllowed($assignment, $data);

        $assignment->update($data);

        return redirect()
            ->route('master.employee-assignments.index')
            ->with('success', 'Employee assignment berhasil diperbarui.');
    }

    protected function formData(?EmployeeAssignment $assignment = null): array
    {
        $currentEmpId = $assignment?->emp_id;
        $currentBranchId = $assignment?->branch_id;
        $currentDeptId = $assignment?->dept_id;
        $currentRoleId = $assignment?->role_id;
        $currentGradeId = $assignment?->grade_id;

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

            'branches' => Branch::query()
                ->where(function ($query) use ($currentBranchId): void {
                    $query->where('active', true);

                    if ($currentBranchId) {
                        $query->orWhere('branch_id', $currentBranchId);
                    }
                })
                ->orderBy('branch_name')
                ->get(),

            'departments' => Department::query()
                ->where(function ($query) use ($currentDeptId): void {
                    $query->where('active', true);

                    if ($currentDeptId) {
                        $query->orWhere('dept_id', $currentDeptId);
                    }
                })
                ->orderBy('dept_name')
                ->get(),

            'roles' => PositionRole::query()
                ->with('department')
                ->where(function ($query) use ($currentRoleId): void {
                    $query->where('active', true);

                    if ($currentRoleId) {
                        $query->orWhere('role_id', $currentRoleId);
                    }
                })
                ->orderBy('role_name')
                ->get(),

            'grades' => Grade::query()
                ->where(function ($query) use ($currentGradeId): void {
                    $query->where('active', true);

                    if ($currentGradeId) {
                        $query->orWhere('grade_id', $currentGradeId);
                    }
                })
                ->orderBy('level_order')
                ->orderBy('grade_name')
                ->get(),
        ];
    }

    public function coverage(Request $request, EmployeeAssignmentCoverageService $coverageService): View
    {
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

        return view('master.employee-assignments.coverage', [
            'rows' => $coverage['rows'],
            'summary' => $coverage['summary'],
            'referenceDate' => $coverage['referenceDate'],
            'employmentTypes' => $employmentTypes,
        ]);
    }
    
    public function bulkCreate(Request $request, EmployeeAssignmentCoverageService $coverageService): View
    {
        $filters = [
            'reference_date' => $request->input('reference_date'),
            'q' => $request->input('q'),
            'employment_type_id' => $request->input('employment_type_id'),
            'candidate_status' => $request->input('candidate_status', 'ALL_ACTIVE_EMPLOYEES'),
        ];

        $coverage = $coverageService->getCoverage($filters);

        return view('master.employee-assignments.bulk-create', [
            'referenceDate' => $coverage['referenceDate'],
            'candidateRows' => $coverage['rows'],
            'employmentTypes' => EmploymentType::query()
                ->where('active', true)
                ->orderBy('employment_type_name')
                ->get(),
            'branches' => Branch::query()->where('active', true)->orderBy('branch_name')->get(),
            'departments' => Department::query()->where('active', true)->orderBy('dept_name')->get(),
            'roles' => PositionRole::query()->with('department')->where('active', true)->orderBy('role_name')->get(),
            'grades' => Grade::query()->where('active', true)->orderBy('level_order')->orderBy('grade_name')->get(),
        ]);
    }

    public function bulkStore(StoreBulkEmployeeAssignmentRequest $request, BulkAssignEmployeesAction $action): RedirectResponse
    {
        $result = $action->execute($request->validated());

        $createdCount = (int) ($result['created_count'] ?? 0);
        $replacedCount = (int) ($result['replaced_count'] ?? 0);
        $skippedCount = (int) ($result['skipped_count'] ?? 0);

        return redirect()
            ->route('master.employee-assignments.bulk-create', [
                'reference_date' => $request->input('reference_date'),
                'q' => $request->input('q'),
                'employment_type_id' => $request->input('employment_type_id'),
                'candidate_status' => $request->input('candidate_status', 'ALL_ACTIVE_EMPLOYEES'),
            ])
            ->with('bulk_assignment_result', $result)
            ->with(
                'success',
                "Bulk assignment selesai. {$createdCount} assignment baru dibuat, {$replacedCount} assignment aktif diganti, dan {$skippedCount} employee tidak diproses."
            );
    }
}