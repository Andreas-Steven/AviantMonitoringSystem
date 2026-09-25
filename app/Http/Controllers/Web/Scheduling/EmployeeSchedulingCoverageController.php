<?php

namespace App\Http\Controllers\Web\Scheduling;

use App\Domains\Master\Models\Branch;
use App\Domains\Master\Models\Department;
use App\Domains\Master\Models\Employee;
use App\Domains\Master\Models\Grade;
use App\Domains\Master\Models\PositionRole;
use App\Domains\Scheduling\Actions\CreateEmployeeSchedulingSetupAction;
use App\Domains\Scheduling\Models\AssignmentType;
use App\Domains\Scheduling\Models\Shift;
use App\Domains\Scheduling\Models\WorkPattern;
use App\Domains\Scheduling\Services\EmployeeSchedulingCoverageService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Scheduling\StoreEmployeeSchedulingSetupRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeSchedulingCoverageController extends Controller
{
    public function index(
        Request $request,
        EmployeeSchedulingCoverageService $coverageService
    ): View {
        $coverage = $coverageService->getCoverage([
            'reference_date' => $request->input('reference_date'),
            'q' => $request->input('q'),
            'overall_status' => $request->input('overall_status'),
        ]);

        return view('scheduling.employee-scheduling-coverage.index', [
            'rows' => $coverage['rows'],
            'summary' => $coverage['summary'],
            'referenceDate' => $coverage['referenceDate'],
        ]);
    }

    public function createSetup(
        Request $request,
        EmployeeSchedulingCoverageService $coverageService
    ): View {
        $empId = (int) $request->input('emp_id');

        abort_if($empId <= 0, 404);

        $referenceDate = $request->input('reference_date') ?: now()->toDateString();

        $employee = Employee::query()
            ->where('active', true)
            ->findOrFail($empId);

        $coverage = $coverageService->getCoverage([
            'reference_date' => $referenceDate,
            'q' => $employee->emp_code,
        ]);

        $row = $coverage['rows']
            ->first(fn (array $item): bool => (int) $item['employee']->emp_id === $empId);

        abort_if(!$row, 404);

        abort_if($row['overall_status'] === 'COMPLETE', 422, 'Employee scheduling setup sudah lengkap.');
        abort_if($row['overall_status'] === 'CONFLICT_DETECTED', 422, 'Employee memiliki conflict setup aktif. Rapikan histori terlebih dahulu.');

        $user = $request->user();

        $canCreateAssignment = $row['assignment_status'] === 'MISSING'
            && $user?->hasPermission('assignment.manage');

        $canCreateShiftAssignment = $row['shift_status'] === 'MISSING'
            && $user?->hasPermission('shift.manage');

        $canCreateWorkPatternAssignment = $row['work_pattern_status'] === 'MISSING'
            && $user?->hasPermission('workpattern.manage');

        abort_if(
            !$canCreateAssignment && !$canCreateShiftAssignment && !$canCreateWorkPatternAssignment,
            403,
            'Anda tidak memiliki permission untuk melengkapi setup yang missing.'
        );

        return view('scheduling.employee-scheduling-coverage.setup', [
            'employee' => $employee,
            'row' => $row,
            'referenceDate' => $coverage['referenceDate'],

            'canCreateAssignment' => $canCreateAssignment,
            'canCreateShiftAssignment' => $canCreateShiftAssignment,
            'canCreateWorkPatternAssignment' => $canCreateWorkPatternAssignment,

            'branches' => Branch::query()
                ->where('active', true)
                ->orderBy('branch_name')
                ->get(),

            'departments' => Department::query()
                ->where('active', true)
                ->orderBy('dept_name')
                ->get(),

            'roles' => PositionRole::query()
                ->where('active', true)
                ->orderBy('role_name')
                ->get(),

            'grades' => Grade::query()
                ->where('active', true)
                ->orderBy('level_order')
                ->orderBy('grade_name')
                ->get(),

            'shifts' => Shift::query()
                ->where('active', true)
                ->orderBy('shift_name')
                ->get(),

            'assignmentTypes' => AssignmentType::query()
                ->where('active', true)
                ->orderBy('assignment_type_name')
                ->get(),

            'workPatterns' => WorkPattern::query()
                ->where('active', true)
                ->orderBy('work_pattern_name')
                ->get(),
        ]);
    }

    public function storeSetup(
        StoreEmployeeSchedulingSetupRequest $request,
        CreateEmployeeSchedulingSetupAction $action
    ): RedirectResponse {
        $result = $action->execute($request->validated());

        $createdLabels = collect([
            $result['assignment'] ?? false ? 'organization assignment' : null,
            $result['shift_assignment'] ?? false ? 'shift assignment' : null,
            $result['work_pattern_assignment'] ?? false ? 'work pattern assignment' : null,
        ])->filter()->implode(', ');

        return redirect()
            ->route('scheduling.employee-scheduling-coverage.index', [
                'reference_date' => $request->input('reference_date'),
            ])
            ->with('success', 'Employee scheduling setup berhasil dibuat: ' . $createdLabels . '.');
    }
}