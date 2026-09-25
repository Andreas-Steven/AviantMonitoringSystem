@extends('layouts.app')

@section('content')
@php
    $assignments = collect(optional($user->employee)->assignments ?? []);
    $primaryAssignment = $assignments->firstWhere('is_primary', true) ?? $assignments->first();
    $primaryBranch = optional($primaryAssignment)->branch;
    $roleCount = $user->roles->count();
    $branchCount = $user->branchAccesses->count();
    $permissionCount = $effectivePermissions->count();
    $lastLoginAudit = $user->loginAudits->first();
    $permissionGroups = $effectivePermissions->groupBy(fn ($permission) => $permission->module_name ?: 'General');
    $isSuperAdmin = method_exists($user, 'isSuperAdmin') ? $user->isSuperAdmin() : false;
@endphp

<div class="space-y-6">
    <x-ui.page-header
        title="App User Detail"
        :subtitle="sprintf('%s · %s', $user->full_name, $user->email)"
        :breadcrumbs="[
            ['label' => 'Access'],
            ['label' => 'Users', 'url' => route('access.users.index')],
            ['label' => 'Detail'],
        ]"
    >
        <div class="flex flex-wrap gap-2">
            @if(auth()->user()->hasPermission('user.manage') && Route::has('access.users.edit'))
                <a href="{{ route('access.users.edit', $user->user_id) }}"
                   class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50">
                    Edit User
                </a>
            @endif

            <a href="{{ route('access.users.index') }}"
               class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50">
                Back
            </a>
        </div>
    </x-ui.page-header>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat-card label="Roles" :value="$roleCount" hint="Assigned roles" />
        <x-ui.stat-card
            label="Branch Access"
            :value="$isSuperAdmin ? 'ALL' : $branchCount"
            :hint="$isSuperAdmin ? 'Super admin scope' : 'Accessible branches'"
        />
        <x-ui.stat-card label="Permissions" :value="$permissionCount" hint="Effective permissions" />
        <x-ui.stat-card
            label="Last Login"
            :value="$user->last_login_at ? $user->last_login_at->format('Y-m-d') : '-'"
            hint="Login terakhir"
        />
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <x-ui.section-card title="User Summary" subtitle="Identitas user internal, status akun, dan keterkaitan employee.">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 md:col-span-2">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">User Identity</div>
                                <div class="mt-1 text-base font-semibold text-slate-900">
                                    {{ $user->full_name }}
                                </div>
                                <div class="mt-1 text-sm text-slate-600 break-words">
                                    {{ $user->email }}
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <x-ui.status-badge
                                    :label="$user->is_active ? 'ACTIVE' : 'INACTIVE'"
                                    :tone="$user->is_active ? 'success' : 'neutral'"
                                />

                                @if($user->must_change_password)
                                    <x-ui.status-badge label="CHANGE PASSWORD" tone="warning" />
                                @endif

                                @if($isSuperAdmin)
                                    <x-ui.status-badge label="SUPER ADMIN" tone="danger" />
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Linked Employee</div>
                        @if($user->employee)
                            <div class="mt-1 text-sm font-semibold text-slate-900">
                                {{ $user->employee->emp_code }} · {{ $user->employee->full_name }}
                            </div>
                            <div class="mt-2 text-xs text-slate-500">
                                Biometric: {{ $user->employee->biometric_code ?: '-' }}
                            </div>
                        @else
                            <div class="mt-1 text-sm font-semibold text-slate-900">-</div>
                            <div class="mt-2 text-xs text-slate-500">
                                Belum terhubung ke employee master.
                            </div>
                        @endif
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Primary Branch</div>
                        <div class="mt-1 text-sm font-semibold text-slate-900">
                            {{ $primaryBranch?->branch_name ?? '-' }}
                        </div>
                        <div class="mt-2 text-xs text-slate-500">
                            {{ $primaryBranch?->branch_code ? 'Code: '.$primaryBranch->branch_code : 'Belum ada primary assignment.' }}
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Last Login At</div>
                        <div class="mt-1 text-sm font-semibold text-slate-900">
                            {{ $user->last_login_at ? $user->last_login_at->format('Y-m-d H:i:s') : '-' }}
                        </div>
                        <div class="mt-2 text-xs text-slate-500">
                            {{ $lastLoginAudit?->ip_address ? 'IP: '.$lastLoginAudit->ip_address : 'Belum ada login audit.' }}
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Account Created</div>
                        <div class="mt-1 text-sm font-semibold text-slate-900">
                            {{ optional($user->created_at)->format('Y-m-d H:i:s') ?: '-' }}
                        </div>
                        <div class="mt-2 text-xs text-slate-500">
                            Updated: {{ optional($user->updated_at)->format('Y-m-d H:i:s') ?: '-' }}
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">RBAC Snapshot</div>
                        <div class="mt-1 flex flex-wrap gap-2 text-sm">
                            <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-700">
                                {{ $roleCount }} Roles
                            </span>
                            <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-700">
                                {{ $permissionCount }} Permissions
                            </span>
                            <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-700">
                                {{ $isSuperAdmin ? 'ALL Branches' : $branchCount.' Branches' }}
                            </span>
                        </div>
                    </div>
                </div>
            </x-ui.section-card>

            <x-ui.section-card title="Roles & Effective Permissions" subtitle="Role yang menempel dan permission efektif yang dihasilkan dari role tersebut.">
                <div class="space-y-5">
                    <div>
                        <div class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Assigned Roles
                        </div>

                        <div class="flex flex-wrap gap-2">
                            @forelse($user->roles as $role)
                                @php
                                    $tone = match($role->role_code) {
                                        'SUPER_ADMIN' => 'danger',
                                        'HR_ADMIN', 'PAYROLL_OFFICER' => 'warning',
                                        'BRANCH_ADMIN', 'SUPERVISOR' => 'info',
                                        default => 'neutral',
                                    };
                                @endphp

                                <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs text-slate-700">
                                    <x-ui.status-badge :label="$role->role_name" :tone="$tone" />
                                    <span class="text-slate-500">{{ $role->role_code }}</span>
                                </span>
                            @empty
                                <span class="text-sm text-slate-500">Belum ada role.</span>
                            @endforelse
                        </div>
                    </div>

                    <div class="border-t border-slate-200 pt-5">
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Effective Permissions
                            </div>
                            <div class="text-xs text-slate-500">
                                {{ $permissionCount }} permissions · {{ $permissionGroups->count() }} modules
                            </div>
                        </div>

                        @if($permissionCount)
                            <div class="space-y-4">
                                @foreach($permissionGroups as $moduleName => $permissions)
                                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <div class="text-sm font-semibold text-slate-900">{{ $moduleName }}</div>
                                            <div class="text-xs text-slate-500">{{ $permissions->count() }} permissions</div>
                                        </div>

                                        <div class="mt-3 flex flex-wrap gap-2">
                                            @foreach($permissions->sortBy('permission_name') as $permission)
                                                <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs text-slate-700">
                                                    {{ $permission->permission_name ?? $permission->permission_code ?? '-' }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-4 text-sm text-slate-500">
                                Belum ada effective permissions.
                            </div>
                        @endif
                    </div>
                </div>
            </x-ui.section-card>

            <x-ui.section-card title="Login Audit Preview" subtitle="Beberapa aktivitas login terakhir user ini.">
                <x-ui.table-shell>
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50/80">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">Logged At</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">Status</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">IP Address</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">Notes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse($user->loginAudits as $audit)
                                <tr>
                                    <td class="px-5 py-4 align-top">
                                        {{ optional($audit->logged_at)->format('Y-m-d H:i') ?: '-' }}
                                    </td>
                                    <td class="px-5 py-4 align-top">
                                        <x-ui.status-badge
                                            :label="$audit->login_status"
                                            :tone="$audit->login_status === 'SUCCESS' ? 'success' : 'warning'"
                                        />
                                    </td>
                                    <td class="px-5 py-4 align-top">
                                        {{ $audit->ip_address ?: '-' }}
                                    </td>
                                    <td class="px-5 py-4 align-top">
                                        <div class="max-w-md whitespace-normal break-words text-slate-700">
                                            {{ $audit->notes ?: '-' }}
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-10 text-center text-sm text-slate-500">
                                        Belum ada login audit.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </x-ui.table-shell>
            </x-ui.section-card>
        </div>

        <div class="space-y-6">
            <x-ui.section-card title="Branch Access" subtitle="Cakupan branch yang dapat diakses user ini.">
                @if($isSuperAdmin)
                    <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4">
                        <div class="flex items-center gap-2">
                            <x-ui.status-badge label="ALL BRANCHES" tone="danger" />
                        </div>
                        <div class="mt-3 text-sm text-rose-800">
                            User ini memiliki akses lintas seluruh branch karena membawa role super admin.
                        </div>
                    </div>
                @elseif($branchCount)
                    <div class="space-y-2">
                        @foreach($user->branchAccesses->sortBy('branch_name') as $branch)
                            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <div class="text-sm font-medium text-slate-900">
                                    {{ $branch->branch_name }}
                                </div>
                                <div class="mt-1 text-xs text-slate-500">
                                    {{ $branch->branch_code }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-4 text-sm text-slate-500">
                        Belum ada branch access yang ditetapkan.
                    </div>
                @endif
            </x-ui.section-card>

            <x-ui.section-card title="Employee Link Snapshot" subtitle="Konteks employee master yang terhubung ke app user ini.">
                @if($user->employee)
                    <div class="space-y-3 text-sm">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Employee</div>
                            <div class="mt-1 font-medium text-slate-900">
                                {{ $user->employee->emp_code }} · {{ $user->employee->full_name }}
                            </div>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Biometric</div>
                            <div class="mt-1 font-medium text-slate-900">
                                {{ $user->employee->biometric_code ?: '-' }}
                            </div>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Assignment</div>
                            <div class="mt-1 font-medium text-slate-900">
                                {{ $primaryBranch?->branch_name ?? '-' }}
                            </div>
                            <div class="mt-1 text-xs text-slate-500">
                                {{ optional($primaryAssignment)->effective_start_date ? 'Effective from '.optional($primaryAssignment->effective_start_date)->format('Y-m-d') : 'Belum ada assignment aktif.' }}
                            </div>
                        </div>
                    </div>
                @else
                    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-4 text-sm text-slate-500">
                        User ini belum dihubungkan ke employee master.
                    </div>
                @endif
            </x-ui.section-card>

            <x-ui.section-card title="Quick Snapshot" subtitle="Ringkasan cepat untuk admin.">
                <div class="space-y-3 text-sm text-slate-600">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        Employee Linked:
                        <span class="font-medium text-slate-900">
                            {{ $user->employee ? 'Yes' : 'No' }}
                        </span>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        Branch Scope:
                        <span class="font-medium text-slate-900">
                            {{ $isSuperAdmin ? 'All Branches' : $branchCount.' Branches' }}
                        </span>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        Permission Modules:
                        <span class="font-medium text-slate-900">
                            {{ $permissionGroups->count() }}
                        </span>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        Last Login Audit:
                        <span class="font-medium text-slate-900">
                            {{ $lastLoginAudit ? optional($lastLoginAudit->logged_at)->format('Y-m-d H:i') : '-' }}
                        </span>
                    </div>
                </div>
            </x-ui.section-card>
        </div>
    </div>
</div>
@endsection