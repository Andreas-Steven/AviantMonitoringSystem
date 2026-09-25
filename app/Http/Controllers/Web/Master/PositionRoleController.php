<?php

namespace App\Http\Controllers\Web\Master;

use App\Domains\Master\Models\Department;
use App\Domains\Master\Models\PositionRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StorePositionRoleRequest;
use App\Http\Requests\Master\UpdatePositionRoleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PositionRoleController extends Controller
{
    public function index(Request $request): View
    {
        $query = PositionRole::query()
            ->with('department')
            ->orderBy('role_name');

        if ($request->filled('q')) {
            $keyword = trim((string) $request->input('q'));

            $query->where(function ($q) use ($keyword): void {
                $q->where('role_code', 'ilike', "%{$keyword}%")
                    ->orWhere('role_name', 'ilike', "%{$keyword}%")
                    ->orWhereHas('department', function ($departmentQuery) use ($keyword): void {
                        $departmentQuery->where('dept_code', 'ilike', "%{$keyword}%")
                            ->orWhere('dept_name', 'ilike', "%{$keyword}%");
                    });
            });
        }

        if ($request->filled('dept_id')) {
            $query->where('dept_id', (int) $request->input('dept_id'));
        }

        if ($request->filled('active')) {
            $query->where('active', $request->boolean('active'));
        }

        $positionRoles = $query->paginate(15)->withQueryString();
        $departments = Department::query()->where('active', true)->orderBy('dept_name')->get();

        return view('master.position-roles.index', compact('positionRoles', 'departments'));
    }

    public function create(): View
    {
        $departments = Department::query()->where('active', true)->orderBy('dept_name')->get();

        return view('master.position-roles.create', compact('departments'));
    }

    public function store(StorePositionRoleRequest $request): RedirectResponse
    {
        PositionRole::create($request->validated());

        return redirect()
            ->route('master.position-roles.index')
            ->with('success', 'Position role berhasil ditambahkan.');
    }

    public function edit(int $position_role): View
    {
        $positionRole = PositionRole::query()->findOrFail($position_role);
        $departments = Department::query()
            ->where(function ($query) use ($positionRole): void {
                $query
                    ->where('active', true)
                    ->orWhere('dept_id', $positionRole->dept_id);
            })
            ->orderBy('dept_name')
            ->get();

        return view('master.position-roles.edit', compact('positionRole', 'departments'));
    }

    public function update(UpdatePositionRoleRequest $request, int $position_role): RedirectResponse
    {
        $positionRoleModel = PositionRole::query()->findOrFail($position_role);
        $positionRoleModel->update($request->validated());

        return redirect()
            ->route('master.position-roles.index')
            ->with('success', 'Position role berhasil diperbarui.');
    }
}
