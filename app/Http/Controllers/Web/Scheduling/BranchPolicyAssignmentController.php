<?php

namespace App\Http\Controllers\Web\Scheduling;

use App\Http\Controllers\Controller;
use App\Http\Requests\Scheduling\StoreBranchPolicyAssignmentRequest;
use App\Http\Requests\Scheduling\UpdateBranchPolicyAssignmentRequest;
use App\Domains\Scheduling\Services\BranchPolicyAssignmentDomainService;
use App\Domains\Scheduling\Services\BranchPolicyAssignmentCoverageService;
use App\Domains\Scheduling\Models\BranchPolicyAssignment;
use App\Domains\Scheduling\Models\AttendancePolicy;
use App\Domains\Master\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;



class BranchPolicyAssignmentController extends Controller
{
    public function index(Request $request): View
    {
        $query = BranchPolicyAssignment::with(['branch', 'policy'])
            ->orderByDesc('effective_start_date')
            ->orderByDesc('branch_policy_assignment_id');

        if ($request->filled('branch_id')) {
            $query->where('branch_id', (int) $request->branch_id);
        }

        if ($request->filled('policy_id')) {
            $query->where('policy_id', (int) $request->policy_id);
        }

        $assignments = $query->paginate(15)->withQueryString();

        $branches = Branch::where('active', true)->orderBy('branch_name')->get();
        $policies = AttendancePolicy::where('active', true)->orderBy('policy_name')->get();

        return view('scheduling.branch-policy-assignments.index', compact('assignments', 'branches', 'policies'));
    }

    public function create(): View
    {
        return view('scheduling.branch-policy-assignments.create', $this->formData());
    }

    public function store(
        StoreBranchPolicyAssignmentRequest $request,
        BranchPolicyAssignmentDomainService $domainService
    ) {
        $data = $request->validated();

        $domainService->assertStoreAllowed($data);

        BranchPolicyAssignment::create($data);

        return redirect()
            ->route('scheduling.branch-policy-assignments.index')
            ->with('success', 'Assignment berhasil ditambahkan.');
    }

    public function show(int $branch_policy_assignment): View
    {
        $assignment = BranchPolicyAssignment::with(['branch', 'policy'])
            ->findOrFail($branch_policy_assignment);

        return view('scheduling.branch-policy-assignments.show', compact('assignment'));
    }

    public function edit(int $branch_policy_assignment): View
    {
        $assignment = BranchPolicyAssignment::findOrFail($branch_policy_assignment);

        return view('scheduling.branch-policy-assignments.edit', array_merge(
            ['assignment' => $assignment],
            $this->formData()
        ));
    }

    public function update(
        UpdateBranchPolicyAssignmentRequest $request,
        int $branch_policy_assignment,
        BranchPolicyAssignmentDomainService $domainService
    ) {
        $assignment = BranchPolicyAssignment::findOrFail($branch_policy_assignment);
        $data = $request->validated();

        $domainService->assertUpdateAllowed($assignment, $data);

        $assignment->update($data);

        return redirect()
            ->route('scheduling.branch-policy-assignments.index')
            ->with('success', 'Assignment berhasil diperbarui.');
    }

    protected function formData(): array
    {
        return [
            'branches' => Branch::where('active', true)->orderBy('branch_name')->get(),
            'policies' => AttendancePolicy::where('active', true)->orderBy('policy_name')->get(),
        ];
    }

    public function coverage(
        Request $request,
        BranchPolicyAssignmentCoverageService $coverageService
    ): View {
        $filters = [
            'reference_date' => $request->input('reference_date'),
            'q' => $request->input('q'),
            'coverage_status' => $request->input('coverage_status'),
        ];

        $coverage = $coverageService->getCoverage($filters);

        return view('scheduling.branch-policy-assignments.coverage', [
            'rows' => $coverage['rows'],
            'summary' => $coverage['summary'],
            'referenceDate' => $coverage['referenceDate'],
        ]);
    }    
}