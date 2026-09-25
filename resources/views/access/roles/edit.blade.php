@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Edit Role Permissions"
        :subtitle="sprintf('%s · %s', $role->role_name, $role->role_code)"
        :breadcrumbs="[
            ['label' => 'Access'],
            ['label' => 'Roles', 'url' => route('access.roles.index')],
            ['label' => 'Detail', 'url' => route('access.roles.show', $role->app_role_id)],
            ['label' => 'Edit Permissions'],
        ]"
    >
        <div class="flex items-center gap-2">
            <a href="{{ route('access.roles.show', $role->app_role_id) }}"
               class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50">
                Back
            </a>
        </div>
    </x-ui.page-header>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat-card label="Users" :value="$role->users->count()" hint="Assigned users" />
        <x-ui.stat-card label="Selected Permissions" :value="count($selectedPermissionIds)" hint="Current mapped permissions" />
        <x-ui.stat-card label="Modules" :value="$permissionsByModule->count()" hint="Permission groups" />
        <x-ui.stat-card label="Status" :value="$role->is_active ? 'ACTIVE' : 'INACTIVE'" hint="Role status" />
    </div>

    <form method="POST" action="{{ route('access.roles.update', $role->app_role_id) }}" class="space-y-6">
        @csrf
        @method('PUT')

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <div class="font-semibold">Ada input yang perlu diperbaiki.</div>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <x-ui.section-card
            title="Permission Mapping"
            subtitle="Centang permission yang harus dimiliki role ini. Grouping mengikuti module_name permission."
        >
            <div class="space-y-4">
                @forelse($permissionsByModule as $module => $permissions)
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/60 p-4">
                        <div class="flex flex-col gap-3 border-b border-slate-200 pb-3 md:flex-row md:items-center md:justify-between">
                            <div>
                                <div class="text-sm font-semibold text-slate-900">{{ $module }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $permissions->count() }} permissions</div>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <button
                                    type="button"
                                    class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-100"
                                    onclick="document.querySelectorAll('[data-module={{ Illuminate\Support\Str::slug($module) }}]').forEach(el => el.checked = true)"
                                >
                                    Select all
                                </button>

                                <button
                                    type="button"
                                    class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-100"
                                    onclick="document.querySelectorAll('[data-module={{ Illuminate\Support\Str::slug($module) }}]').forEach(el => el.checked = false)"
                                >
                                    Clear
                                </button>
                            </div>
                        </div>

                        <div class="mt-4 grid gap-3 md:grid-cols-2">
                            @foreach($permissions as $permission)
                                <label class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 transition hover:border-slate-300 hover:bg-slate-50">
                                    <input
                                        type="checkbox"
                                        name="permission_ids[]"
                                        value="{{ $permission->permission_id }}"
                                        data-module="{{ Illuminate\Support\Str::slug($module) }}"
                                        class="mt-1 h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-400"
                                        @checked(in_array((int) $permission->permission_id, old('permission_ids', $selectedPermissionIds), true))
                                    >
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <div class="text-sm font-medium text-slate-900">
                                                {{ $permission->permission_name }}
                                            </div>

                                            @unless($permission->is_active)
                                                <x-ui.status-badge label="INACTIVE" tone="neutral" />
                                            @endunless
                                        </div>

                                        <div class="mt-1 text-xs text-slate-500">
                                            {{ $permission->permission_code }}
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <x-ui.empty-state title="No permissions found" description="Belum ada permission aktif yang bisa dipetakan." />
                @endforelse
            </div>
        </x-ui.section-card>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('access.roles.show', $role->app_role_id) }}"
               class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                Cancel
            </a>

            <x-ui.button type="submit">
                Save Permission Mapping
            </x-ui.button>
        </div>
    </form>
</div>
@endsection