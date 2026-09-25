<?php

namespace App\Http\Controllers\Web\Scheduling;

use App\Http\Controllers\Controller;
use App\Http\Requests\Scheduling\StoreAttendancePolicyRequest;
use App\Http\Requests\Scheduling\UpdateAttendancePolicyRequest;
use App\Domains\Scheduling\Models\AttendancePolicy;
use App\Domains\Scheduling\Models\MissingAttendancePolicy;
use App\Domains\Scheduling\Models\OvertimeRoundingMode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendancePolicyController extends Controller
{
    public function index(Request $request): View
    {
        $query = AttendancePolicy::query()->orderBy('policy_name');

        if ($request->filled('q')) {
            $keyword = $request->string('q');
            $query->where(function ($q) use ($keyword) {
                $q->where('policy_code', 'ilike', "%{$keyword}%")
                  ->orWhere('policy_name', 'ilike', "%{$keyword}%");
            });
        }

        if ($request->filled('active')) {
            $query->where('active', (bool) $request->active);
        }

        $policies = $query->paginate(15)->withQueryString();

        return view('scheduling.attendance-policies.index', compact('policies'));
    }

    public function create(): View
    {
        $roundingModes = OvertimeRoundingMode::query()
            ->orderBy('overtime_rounding_mode_name')
            ->get();

        $missingPolicies = MissingAttendancePolicy::query()
            ->orderBy('missing_attendance_policy_name')
            ->get();

        return view('scheduling.attendance-policies.create', compact('roundingModes', 'missingPolicies'));
    }

    public function store(StoreAttendancePolicyRequest $request): RedirectResponse
    {
        AttendancePolicy::create($request->validated());

        return redirect()
            ->route('scheduling.attendance-policies.index')
            ->with('success', 'Attendance policy berhasil ditambahkan.');
    }

    public function show(int $attendance_policy): View
    {
        $policy = AttendancePolicy::findOrFail($attendance_policy);

        return view('scheduling.attendance-policies.show', compact('policy'));
    }

    public function edit(int $attendance_policy): View
    {
        $policy = AttendancePolicy::findOrFail($attendance_policy);

        $roundingModes = OvertimeRoundingMode::query()
            ->orderBy('overtime_rounding_mode_name')
            ->get();

        $missingPolicies = MissingAttendancePolicy::query()
            ->orderBy('missing_attendance_policy_name')
            ->get();

        return view('scheduling.attendance-policies.edit', compact('policy', 'roundingModes', 'missingPolicies'));
    }

    public function update(UpdateAttendancePolicyRequest $request, int $attendance_policy): RedirectResponse
    {
        $policy = AttendancePolicy::findOrFail($attendance_policy);
        $policy->update($request->validated());

        return redirect()
            ->route('scheduling.attendance-policies.index')
            ->with('success', 'Attendance policy berhasil diperbarui.');
    }
}