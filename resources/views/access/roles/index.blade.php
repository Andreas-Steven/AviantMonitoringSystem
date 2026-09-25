@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Roles"
        subtitle="Daftar role internal dan cakupan aksesnya."
        :breadcrumbs="[
            ['label' => 'Access'],
            ['label' => 'Roles'],
        ]"
    />

    <x-ui.page-section
        title="Role Filters"
        subtitle="Filter role berdasarkan keyword dan status aktif."
    >
        <form method="GET" action="{{ route('access.roles.index') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.field label="Keyword">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari role code / role name"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
            </x-ui.field>

            <x-ui.field label="Active">
                <select name="is_active" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                    <option value="">Semua</option>
                    <option value="1" @selected(request('is_active') === '1')>Active</option>
                    <option value="0" @selected(request('is_active') === '0')>Inactive</option>
                </select>
            </x-ui.field>

            <div class="flex items-end gap-3 md:col-span-2">
                <x-ui.button type="submit">Search</x-ui.button>
                <x-ui.button type="button" variant="ghost" onclick="window.location='{{ route('access.roles.index') }}'">Reset</x-ui.button>
            </div>
        </form>
    </x-ui.page-section>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <x-ui.stat-card label="Rows" :value="$summaryStats['total_rows']" hint="Total roles" />
        <x-ui.stat-card label="Active" :value="$summaryStats['active_rows']" hint="Role aktif" />
        <x-ui.stat-card label="Inactive" :value="$summaryStats['inactive_rows']" hint="Role nonaktif" />
    </div>

    <x-ui.table-shell>
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50/80">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Role</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Users</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Permissions</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse($roles as $role)
                    <tr class="transition hover:bg-slate-50">
                        <td class="px-5 py-4">
                            <div class="font-medium text-slate-900">{{ $role->role_name }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ $role->role_code }}</div>
                        </td>
                        <td class="px-5 py-4">{{ $role->users_count }}</td>
                        <td class="px-5 py-4">{{ $role->permissions_count }}</td>
                        <td class="px-5 py-4">
                            <x-ui.status-badge :label="$role->is_active ? 'ACTIVE' : 'INACTIVE'" :tone="$role->is_active ? 'success' : 'neutral'" />
                        </td>
                        <td class="px-5 py-4 text-right">
                            <div class="flex justify-end gap-2">
                                <x-ui.button variant="ghost" onclick="window.location='{{ route('access.roles.show', $role->app_role_id) }}'">
                                    Detail
                                </x-ui.button>

                                @if(auth()->user()?->hasPermission('role.manage'))
                                    <x-ui.button variant="ghost" onclick="window.location='{{ route('access.roles.edit', $role->app_role_id) }}'">
                                        Edit Permissions
                                    </x-ui.button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-14">
                            <x-ui.empty-state title="No roles found" description="Belum ada role sesuai filter." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($roles->hasPages())
            <div class="border-t border-slate-200 bg-white px-5 py-4">
                {{ $roles->withQueryString()->links() }}
            </div>
        @endif
    </x-ui.table-shell>
</div>
@endsection