@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="App Users"
        subtitle="Kelola user internal, role, branch scope, dan keterhubungan ke employee master."
        :breadcrumbs="[
            ['label' => 'Access'],
            ['label' => 'Users'],
        ]"
    >
        <x-slot:actions>
            <div class="flex flex-wrap gap-2">
                @if(auth()->user()->hasPermission('user.manage') && Route::has('access.users.create'))
                    <a href="{{ route('access.users.create') }}"
                       class="inline-flex items-center rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-slate-800">
                        Create User
                    </a>
                @endif
            </div>
        </x-slot:actions>
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
        <x-ui.stat-card label="Total Users" :value="$summaryStats['total_rows'] ?? 0" hint="Matched current filter" />
        <x-ui.stat-card label="Active" :value="$summaryStats['active_rows'] ?? 0" hint="User aktif" />
        <x-ui.stat-card label="Inactive" :value="$summaryStats['inactive_rows'] ?? 0" hint="User nonaktif" />
        <x-ui.stat-card label="Linked Employee" :value="$summaryStats['linked_employee_rows'] ?? 0" hint="Sudah terhubung ke employee" />
    </div>

    <x-ui.section-card
        title="Filter & Search"
        subtitle="Cari berdasarkan nama, email, emp code, nama employee, atau biometric code."
    >
        <form method="GET" action="{{ route('access.users.index') }}" class="grid grid-cols-1 gap-4 lg:grid-cols-12">
            <div class="lg:col-span-5">
                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                    Search
                </label>
                <input
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Cari user / employee..."
                    class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                >
            </div>

            <div class="lg:col-span-3">
                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                    Status
                </label>
                <select
                    name="is_active"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                >
                    <option value="">All Status</option>
                    <option value="1" @selected(request('is_active') === '1')>Active</option>
                    <option value="0" @selected(request('is_active') === '0')>Inactive</option>
                </select>
            </div>

            <div class="lg:col-span-3">
                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                    Role
                </label>
                <select
                    name="role_code"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                >
                    <option value="">All Roles</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->role_code }}" @selected(request('role_code') === $role->role_code)>
                            {{ $role->role_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end gap-2 lg:col-span-1">
                <button
                    type="submit"
                    class="inline-flex w-full items-center justify-center rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-slate-800"
                >
                    Apply
                </button>
            </div>

            <div class="lg:col-span-12">
                <div class="flex flex-wrap gap-2 pt-1">
                    <a href="{{ route('access.users.index') }}"
                       class="inline-flex items-center rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50">
                        Reset Filter
                    </a>

                    @if(request()->filled('q') || request()->filled('is_active') || request()->filled('role_code'))
                        <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs text-slate-500">
                            Filter aktif sedang diterapkan
                        </span>
                    @endif
                </div>
            </div>
        </form>
    </x-ui.section-card>

    <x-ui.section-card
        title="User Directory"
        subtitle="Daftar user internal beserta status akun, linkage employee, role, dan branch scope."
    >
        <x-ui.table-shell>
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50/80">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">User</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">Linked Employee</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">Roles</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">Branch Scope</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">Status</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">Last Login</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-slate-500">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($users as $user)
                        @php
                            $assignments = collect(optional($user->employee)->assignments ?? []);
                            $primaryAssignment = $assignments->firstWhere('is_primary', true) ?? $assignments->first();
                            $primaryBranch = optional($primaryAssignment)->branch;
                            $isSuperAdmin = method_exists($user, 'isSuperAdmin') ? $user->isSuperAdmin() : false;
                            $roleCount = $user->roles->count();
                            $branchCount = $user->branchAccesses->count();
                        @endphp

                        <tr class="align-top">
                            <td class="px-5 py-4">
                                <div class="space-y-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <a href="{{ route('access.users.show', $user->user_id) }}"
                                           class="font-semibold text-slate-900 hover:text-slate-700">
                                            {{ $user->full_name }}
                                        </a>

                                        @if($isSuperAdmin)
                                            <x-ui.status-badge label="SUPER ADMIN" tone="danger" />
                                        @endif
                                    </div>

                                    <div class="text-sm text-slate-600 break-words">
                                        {{ $user->email }}
                                    </div>

                                    <div class="flex flex-wrap gap-2 pt-1">
                                        @if($user->must_change_password)
                                            <x-ui.status-badge label="CHANGE PASSWORD" tone="warning" />
                                        @endif

                                        <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] font-medium text-slate-600">
                                            ID #{{ $user->user_id }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <td class="px-5 py-4">
                                @if($user->employee)
                                    <div class="space-y-1">
                                        <div class="font-medium text-slate-900">
                                            {{ $user->employee->emp_code }} · {{ $user->employee->full_name }}
                                        </div>
                                        <div class="text-xs text-slate-500">
                                            Biometric: {{ $user->employee->biometric_code ?: '-' }}
                                        </div>
                                        <div class="text-xs text-slate-500">
                                            Primary Branch: {{ $primaryBranch?->branch_name ?? '-' }}
                                        </div>
                                    </div>
                                @else
                                    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-3 py-2 text-xs text-slate-500">
                                        Not linked
                                    </div>
                                @endif
                            </td>

                            <td class="px-5 py-4">
                                @if($roleCount)
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($user->roles->take(3) as $role)
                                            @php
                                                $tone = match($role->role_code) {
                                                    'SUPER_ADMIN' => 'danger',
                                                    'HR_ADMIN', 'PAYROLL_OFFICER' => 'warning',
                                                    'BRANCH_ADMIN', 'SUPERVISOR' => 'info',
                                                    default => 'neutral',
                                                };
                                            @endphp

                                            <x-ui.status-badge :label="$role->role_name" :tone="$tone" />
                                        @endforeach

                                        @if($roleCount > 3)
                                            <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] font-medium text-slate-600">
                                                +{{ $roleCount - 3 }} more
                                            </span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-xs text-slate-500">No roles</span>
                                @endif
                            </td>

                            <td class="px-5 py-4">
                                @if($isSuperAdmin)
                                    <div class="space-y-1">
                                        <x-ui.status-badge label="ALL BRANCHES" tone="danger" />
                                        <div class="text-xs text-slate-500">Implicit full scope</div>
                                    </div>
                                @elseif($branchCount > 0)
                                    <div class="space-y-1">
                                        <div class="font-medium text-slate-900">{{ $branchCount }} branches</div>
                                        <div class="text-xs text-slate-500">
                                            {{ $user->branchAccesses->pluck('branch_code')->take(3)->implode(', ') }}
                                            @if($branchCount > 3)
                                                +{{ $branchCount - 3 }} more
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <span class="text-xs text-slate-500">No branch access</span>
                                @endif
                            </td>

                            <td class="px-5 py-4">
                                <div class="space-y-2">
                                    <x-ui.status-badge
                                        :label="$user->is_active ? 'ACTIVE' : 'INACTIVE'"
                                        :tone="$user->is_active ? 'success' : 'neutral'"
                                    />
                                </div>
                            </td>

                            <td class="px-5 py-4">
                                <div class="space-y-1">
                                    <div class="font-medium text-slate-900">
                                        {{ $user->last_login_at ? $user->last_login_at->format('Y-m-d') : '-' }}
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        {{ $user->last_login_at ? $user->last_login_at->format('H:i:s') : 'Never login' }}
                                    </div>
                                </div>
                            </td>

                            <td class="px-5 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('access.users.show', $user->user_id) }}"
                                       class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-700 shadow-sm transition hover:bg-slate-50">
                                        Detail
                                    </a>

                                    @if(auth()->user()->hasPermission('user.manage') && Route::has('access.users.edit'))
                                        <a href="{{ route('access.users.edit', $user->user_id) }}"
                                           class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-700 shadow-sm transition hover:bg-slate-50">
                                            Edit
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center">
                                <div class="mx-auto max-w-md space-y-2">
                                    <div class="text-sm font-semibold text-slate-900">Belum ada data user</div>
                                    <div class="text-sm text-slate-500">
                                        Coba ubah filter pencarian, atau buat user baru jika memang belum ada.
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-ui.table-shell>

        @if($users->hasPages())
            <div class="mt-4">
                {{ $users->links() }}
            </div>
        @endif
    </x-ui.section-card>
</div>
@endsection