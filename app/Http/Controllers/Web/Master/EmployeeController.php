<?php

namespace App\Http\Controllers\Web\Master;

use App\Domains\Master\Models\Employee;
use App\Domains\Master\Models\EmploymentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreEmployeeRequest;
use App\Http\Requests\Master\UpdateEmployeeRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $query = Employee::with('employmentType')
            ->orderBy('full_name');

        // =========================================================
        // FILTER: Missing Shift
        // =========================================================
        if ($request->boolean('missing_shift')) {
            $today = now()->toDateString();

            $query->whereNotExists(function ($q) use ($today) {
                $q->select(\DB::raw(1))
                ->from('employee_shift_assignments as esa')
                ->whereColumn('esa.emp_id', 'employees.emp_id')
                ->whereDate('esa.effective_start_date', '<=', $today)
                ->where(function ($sq) use ($today) {
                    $sq->whereNull('esa.effective_end_date')
                        ->orWhereDate('esa.effective_end_date', '>=', $today);
                });
            });
        }

        // =========================================================
        // FILTER: Missing Work Pattern
        // =========================================================
        if ($request->boolean('missing_work_pattern')) {
            $today = now()->toDateString();

            $query->whereNotExists(function ($q) use ($today) {
                $q->select(\DB::raw(1))
                ->from('employee_work_pattern_assignments as ewpa')
                ->whereColumn('ewpa.emp_id', 'employees.emp_id')
                ->whereDate('ewpa.effective_start_date', '<=', $today)
                ->where(function ($sq) use ($today) {
                    $sq->whereNull('ewpa.effective_end_date')
                        ->orWhereDate('ewpa.effective_end_date', '>=', $today);
                });
            });
        }

        if ($request->filled('q')) {
            $keyword = $request->string('q');
            $query->where(function ($q) use ($keyword) {
                $q->where('emp_code', 'ilike', "%{$keyword}%")
                  ->orWhere('biometric_code', 'ilike', "%{$keyword}%")
                  ->orWhere('full_name', 'ilike', "%{$keyword}%");
            });
        }

        if ($request->filled('employment_type_id')) {
            $query->where('employment_type_id', (int) $request->employment_type_id);
        }

        if ($request->filled('active')) {
            $query->where('active', (bool) $request->active);
        }

        $employees = $query->paginate(15)->withQueryString();

        $employmentTypes = EmploymentType::query()
            ->when($request->filled('employment_type_id'), function ($query) use ($request): void {
                $query->where(function ($sub) use ($request): void {
                    $sub->where('active', true)
                        ->orWhere('employment_type_id', (int) $request->input('employment_type_id'));
                });
            }, function ($query): void {
                $query->where('active', true);
            })
            ->orderBy('employment_type_name')
            ->get();

        return view('master.employees.index', compact('employees', 'employmentTypes'));
    }

    public function create(): View
    {
        $employmentTypes = EmploymentType::where('active', true)
            ->orderBy('employment_type_name')
            ->get();

        return view('master.employees.create', compact('employmentTypes'));
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        Employee::create($request->validated());

        return redirect()
            ->route('master.employees.index')
            ->with('success', 'Employee berhasil ditambahkan.');
    }

    public function show(int $employee): View
    {
        $today = Carbon::today()->toDateString();

        $employee = Employee::with([
            'employmentType',
            'assignments' => function ($query) {
                $query->with(['branch', 'department', 'role.department', 'grade'])
                    ->orderByDesc('effective_start_date')
                    ->orderByDesc('assignment_id');
            },
        ])->findOrFail($employee);

        $currentAssignment = $employee->assignments->first(function ($assignment) use ($today) {
            $start = optional($assignment->effective_start_date)->format('Y-m-d');
            $end = optional($assignment->effective_end_date)->format('Y-m-d');

            return $start !== null
                && $start <= $today
                && ($end === null || $end >= $today);
        });

        $recentAssignments = $employee->assignments->take(5);

        return view('master.employees.show', compact(
            'employee',
            'today',
            'currentAssignment',
            'recentAssignments'
        ));
    }

    public function edit(int $employee): View
    {
        $employee = Employee::findOrFail($employee);

        $employmentTypes = EmploymentType::query()
            ->where(function ($query) use ($employee): void {
                $query->where('active', true)
                    ->orWhere('employment_type_id', $employee->employment_type_id);
            })
            ->orderBy('employment_type_name')
            ->get();

        return view('master.employees.edit', compact('employee', 'employmentTypes'));
    }

    public function update(UpdateEmployeeRequest $request, int $employee): RedirectResponse
    {
        $employeeModel = Employee::findOrFail($employee);
        $employeeModel->update($request->validated());

        return redirect()
            ->route('master.employees.index')
            ->with('success', 'Employee berhasil diperbarui.');
    }
}