@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Role Detail"
        :subtitle="sprintf('%s · %s', $role->role_name, $role->role_code)"
        :breadcrumbs="[
            ['label' => 'Access'],
            ['label' => 'Roles', 'url' => route('access.roles.index')],
            ['label' => 'Detail'],
        ]"
    >
        <div class="flex items-center gap-2">
            <a href="{{ route('access.roles.index') }}"
            class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50">
                Back
            </a>

            @if(auth()->user()?->hasPermission('role.manage'))
                <a href="{{ route('access.roles.edit', $role->app_role_id) }}"
                class="inline-flex items-center rounded-lg border border-slate-900 bg-slate-900 px-3 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-slate-800">
                    Edit Permissions
                </a>
            @endif
        </div>
    </x-ui.page-header>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <x-ui.stat-card label="Users" :value="$role->users->count()" hint="Assigned users" />
        <x-ui.stat-card label="Permissions" :value="$role->permissions->count()" hint="Mapped permissions" />
        <x-ui.stat-card label="Status" :value="$role->is_active ? 'ACTIVE' : 'INACTIVE'" hint="Role status" />
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <x-ui.section-card title="Permissions by Module" subtitle="Permission yang dimiliki role ini, dikelompokkan per module.">
                <div class="grid gap-4 md:grid-cols-2">
                    @forelse($permissionsByModule as $module => $permissions)
                        <div class="rounded-2xl border border-slate-200 bg-white p-4">
                            <div class="flex items-center justify-between gap-3">
                                <div class="text-sm font-semibold text-slate-900">{{ $module }}</div>
                                <div class="text-xs text-slate-500">{{ $permissions->count() }} permissions</div>
                            </div>

                            <div class="mt-3 space-y-2">
                                @foreach($permissions as $permission)
                                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                                        <div class="text-sm font-medium text-slate-900">{{ $permission->permission_name }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ $permission->permission_code }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-sm text-slate-500">
                            Tidak ada permission di role ini.
                        </div>
                    @endforelse
                </div>
            </x-ui.section-card>
        </div>

        <div class="space-y-6">
            <x-ui.section-card title="Assigned Users" subtitle="User yang saat ini memakai role ini.">
                <div class="space-y-2">
                    @forelse($role->users as $user)
                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                            <div class="font-medium text-slate-900">{{ $user->full_name }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ $user->email }}</div>
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-4 text-sm text-slate-500">
                            Belum ada assigned user.
                        </div>
                    @endforelse
                </div>
            </x-ui.section-card>
        </div>
    </div>
</div>
@endsection