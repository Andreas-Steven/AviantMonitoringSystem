<?php

namespace App\Http\Controllers\Web\Access;

use App\Domains\Access\Models\AppPermission;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppPermissionController extends Controller
{
    public function index(Request $request): View
    {
        $query = AppPermission::query()
            ->withCount('roles')
            ->orderBy('module_name')
            ->orderBy('permission_name');

        if ($request->filled('q')) {
            $keyword = trim((string) $request->input('q'));

            $query->where(function ($q) use ($keyword): void {
                $q->where('permission_name', 'ilike', "%{$keyword}%")
                    ->orWhere('permission_code', 'ilike', "%{$keyword}%")
                    ->orWhere('module_name', 'ilike', "%{$keyword}%");
            });
        }

        if ($request->filled('module_name')) {
            $query->where('module_name', (string) $request->input('module_name'));
        }

        $permissions = $query->get();

        $modules = AppPermission::query()
            ->select('module_name')
            ->distinct()
            ->orderBy('module_name')
            ->pluck('module_name');

        $permissionsByModule = $permissions->groupBy('module_name');

        $summaryStats = [
            'total_rows' => $permissions->count(),
            'module_rows' => $permissionsByModule->count(),
            'active_rows' => $permissions->where('is_active', true)->count(),
        ];

        return view('access.permissions.index', compact(
            'permissionsByModule',
            'modules',
            'summaryStats',
        ));
    }
}
