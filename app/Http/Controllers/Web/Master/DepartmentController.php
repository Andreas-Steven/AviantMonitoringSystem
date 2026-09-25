<?php

namespace App\Http\Controllers\Web\Master;

use App\Domains\Master\Models\Department;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreDepartmentRequest;
use App\Http\Requests\Master\UpdateDepartmentRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(Request $request): View
    {
        $query = Department::query()->orderBy('dept_name');

        if ($request->filled('q')) {
            $keyword = trim((string) $request->input('q'));

            $query->where(function ($q) use ($keyword): void {
                $q->where('dept_code', 'ilike', "%{$keyword}%")
                    ->orWhere('dept_name', 'ilike', "%{$keyword}%");
            });
        }

        if ($request->filled('active')) {
            $query->where('active', $request->boolean('active'));
        }

        $departments = $query->paginate(15)->withQueryString();

        return view('master.departments.index', compact('departments'));
    }

    public function create(): View
    {
        return view('master.departments.create');
    }

    public function store(StoreDepartmentRequest $request): RedirectResponse
    {
        Department::create($request->validated());

        return redirect()
            ->route('master.departments.index')
            ->with('success', 'Department berhasil ditambahkan.');
    }

    public function edit(int $department): View
    {
        $department = Department::query()->findOrFail($department);

        return view('master.departments.edit', compact('department'));
    }

    public function update(UpdateDepartmentRequest $request, int $department): RedirectResponse
    {
        $departmentModel = Department::query()->findOrFail($department);
        $departmentModel->update($request->validated());

        return redirect()
            ->route('master.departments.index')
            ->with('success', 'Department berhasil diperbarui.');
    }
}
