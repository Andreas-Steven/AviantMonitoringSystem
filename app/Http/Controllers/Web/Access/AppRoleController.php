<?php

namespace App\Http\Controllers\Web\Access;

use App\Domains\Access\Models\AppPermission;
use App\Domains\Access\Models\AppRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Access\UpdateAppRolePermissionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AppRoleController extends Controller
{
    public function index(Request $request): View
    {
        $query = AppRole::query()
            ->withCount(['users', 'permissions'])
            ->orderBy('role_name');

        if ($request->filled('q')) {
            $keyword = trim((string) $request->input('q'));

            $query->where(function ($q) use ($keyword): void {
                $q->where('role_name', 'ilike', "%{$keyword}%")
                    ->orWhere('role_code', 'ilike', "%{$keyword}%");
            });
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', (bool) $request->boolean('is_active'));
        }

        $roles = $query->paginate(15)->withQueryString();

        $summaryStats = [
            'total_rows' => AppRole::query()->count(),
            'active_rows' => AppRole::query()->where('is_active', true)->count(),
            'inactive_rows' => AppRole::query()->where('is_active', false)->count(),
        ];

        return view('access.roles.index', compact('roles', 'summaryStats'));
    }

    public function show(int $app_role): View
    {
        $role = AppRole::query()
            ->with(['permissions', 'users'])
            ->findOrFail($app_role);

        $permissionsByModule = $role->permissions
            ->sortBy([
                ['module_name', 'asc'],
                ['permission_name', 'asc'],
            ])
            ->groupBy('module_name');

        return view('access.roles.show', compact('role', 'permissionsByModule'));
    }

    public function edit(int $app_role): View
    {
        $role = AppRole::query()
            ->with(['permissions', 'users'])
            ->findOrFail($app_role);

        $selectedPermissionIds = $role->permissions
            ->pluck('permission_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $permissions = AppPermission::query()
            ->where(function ($query) use ($selectedPermissionIds): void {
                $query->where('is_active', true);

                if (! empty($selectedPermissionIds)) {
                    $query->orWhereIn('permission_id', $selectedPermissionIds);
                }
            })
            ->orderBy('module_name')
            ->orderBy('permission_name')
            ->get();

        $permissionsByModule = $permissions->groupBy('module_name');

        return view('access.roles.edit', compact(
            'role',
            'permissionsByModule',
            'selectedPermissionIds',
        ));
    }

    public function update(UpdateAppRolePermissionRequest $request, int $app_role): RedirectResponse
    {
        $role = AppRole::query()->findOrFail($app_role);

        $permissionIds = collect($request->validated('permission_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values()
            ->all();

        DB::transaction(function () use ($role, $permissionIds): void {
            $role->permissions()->sync($permissionIds);
        });

        return redirect()
            ->route('access.roles.show', $role->app_role_id)
            ->with('success', 'Permission role berhasil diperbarui.');
    }
}
