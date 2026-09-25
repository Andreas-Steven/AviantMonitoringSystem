<?php

namespace App\Http\Controllers\Web\Access;

use App\Domains\Access\Models\AppRole;
use App\Domains\Access\Models\AppUser;
use App\Domains\Master\Models\Branch;
use App\Domains\Master\Models\Employee;
use App\Http\Controllers\Controller;
use App\Http\Requests\Access\StoreAppUserRequest;
use App\Http\Requests\Access\UpdateAppUserRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AppUserController extends Controller
{
    public function index(Request $request): View
    {
        $query = AppUser::query()
            ->with(['employee.assignments.branch', 'roles', 'branchAccesses'])
            ->orderBy('full_name');

        if ($request->filled('q')) {
            $keyword = trim((string) $request->input('q'));

            $query->where(function ($q) use ($keyword): void {
                $q->where('full_name', 'ilike', "%{$keyword}%")
                    ->orWhere('email', 'ilike', "%{$keyword}%")
                    ->orWhereHas('employee', function ($sub) use ($keyword): void {
                        $sub->where('emp_code', 'ilike', "%{$keyword}%")
                            ->orWhere('full_name', 'ilike', "%{$keyword}%")
                            ->orWhere('biometric_code', 'ilike', "%{$keyword}%");
                    });
            });
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', (bool) $request->boolean('is_active'));
        }

        if ($request->filled('role_code')) {
            $roleCode = (string) $request->input('role_code');

            $query->whereHas('roles', function ($q) use ($roleCode): void {
                $q->where('role_code', $roleCode);
            });
        }

        $statsBase = clone $query;

        $summaryStats = [
            'total_rows' => (clone $statsBase)->count(),
            'active_rows' => (clone $statsBase)->where('is_active', true)->count(),
            'inactive_rows' => (clone $statsBase)->where('is_active', false)->count(),
            'linked_employee_rows' => (clone $statsBase)->whereNotNull('employee_id')->count(),
        ];

        $users = $query->paginate(15)->withQueryString();

        $roles = AppRole::query()
            ->where('is_active', true)
            ->orderBy('role_name')
            ->get();

        return view('access.users.index', compact(
            'users',
            'roles',
            'summaryStats',
        ));
    }

    public function create(): View
    {
        $roles = AppRole::query()
            ->where('is_active', true)
            ->orderBy('role_name')
            ->get();

        $branches = Branch::query()
            ->where('active', true)
            ->orderBy('branch_name')
            ->get();

        $employees = Employee::query()
            ->where('active', true)
            ->whereDoesntHave('appUsers')
            ->orderBy('full_name')
            ->get([
                'emp_id',
                'emp_code',
                'full_name',
                'biometric_code',
            ]);

        return view('access.users.create', compact('roles', 'branches', 'employees'));
    }

    public function store(StoreAppUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $roleIds = collect($validated['role_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values()
            ->all();

        $branchIds = collect($validated['branch_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values()
            ->all();

        $user = DB::transaction(function () use ($validated, $roleIds, $branchIds): AppUser {
            $user = AppUser::query()->create([
                'full_name' => $validated['full_name'],
                'email' => $validated['email'],
                'employee_id' => $validated['employee_id'] ?? null,
                'password_hash' => Hash::make($validated['password']),
                'is_active' => (bool) ($validated['is_active'] ?? false),
                'must_change_password' => (bool) ($validated['must_change_password'] ?? false),
            ]);

            $user->roles()->sync($roleIds);
            $user->branchAccesses()->sync($branchIds);

            return $user;
        });

        return redirect()
            ->route('access.users.show', $user->user_id)
            ->with('success', 'App user berhasil ditambahkan.');
    }

    public function show(int $app_user): View
    {
        $user = AppUser::query()
            ->with([
                'employee.assignments.branch',
                'roles.permissions',
                'branchAccesses',
                'loginAudits' => fn ($q) => $q->latest('logged_at')->limit(20),
            ])
            ->findOrFail($app_user);

        $effectivePermissions = $user->roles
            ->flatMap(fn ($role) => $role->permissions)
            ->unique('permission_id')
            ->sortBy('module_name')
            ->values();

        return view('access.users.show', compact('user', 'effectivePermissions'));
    }

    public function edit(int $app_user): View
    {
        $user = AppUser::query()
            ->with(['roles', 'branchAccesses', 'employee'])
            ->findOrFail($app_user);

        $roles = AppRole::query()
            ->where('is_active', true)
            ->orderBy('role_name')
            ->get();

        $branches = Branch::query()
            ->where('active', true)
            ->orderBy('branch_name')
            ->get();

        $employees = Employee::query()
            ->where('active', true)
            ->where(function ($query) use ($user): void {
                $query->whereDoesntHave('appUsers');

                if ($user->employee_id) {
                    $query->orWhere('emp_id', $user->employee_id);
                }
            })
            ->orderBy('full_name')
            ->get([
                'emp_id',
                'emp_code',
                'full_name',
                'biometric_code',
            ]);

        return view('access.users.edit', compact('user', 'roles', 'branches', 'employees'));
    }

    public function update(UpdateAppUserRequest $request, int $app_user): RedirectResponse
    {
        $user = AppUser::query()->findOrFail($app_user);
        $validated = $request->validated();

        $roleIds = collect($validated['role_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values()
            ->all();

        $branchIds = collect($validated['branch_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values()
            ->all();

        DB::transaction(function () use ($user, $validated, $roleIds, $branchIds): void {
            $payload = [
                'full_name' => $validated['full_name'],
                'email' => $validated['email'],
                'employee_id' => $validated['employee_id'] ?? null,
                'is_active' => (bool) ($validated['is_active'] ?? false),
                'must_change_password' => (bool) ($validated['must_change_password'] ?? false),
            ];

            if (!empty($validated['password'])) {
                $payload['password_hash'] = Hash::make($validated['password']);
            }

            $user->update($payload);
            $user->roles()->sync($roleIds);
            $user->branchAccesses()->sync($branchIds);
        });

        return redirect()
            ->route('access.users.show', $user->user_id)
            ->with('success', 'App user berhasil diperbarui.');
    }
}