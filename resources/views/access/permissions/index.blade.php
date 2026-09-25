@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Permissions"
        subtitle="Daftar permission internal yang dipakai role-role aplikasi, dikelompokkan per module."
        :breadcrumbs="[
            ['label' => 'Access'],
            ['label' => 'Permissions'],
        ]"
    />

    <x-ui.page-section
        title="Permission Filters"
        subtitle="Filter permission berdasarkan keyword dan module."
    >
        <form method="GET" action="{{ route('access.permissions.index') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.field label="Keyword">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari permission / code / module"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
            </x-ui.field>

            <x-ui.field label="Module">
                <select name="module_name" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                    <option value="">Semua module</option>
                    @foreach($modules as $module)
                        <option value="{{ $module }}" @selected(request('module_name') === $module)>
                            {{ $module }}
                        </option>
                    @endforeach
                </select>
            </x-ui.field>

            <div class="flex items-end gap-3 md:col-span-2">
                <x-ui.button type="submit">Search</x-ui.button>
                <x-ui.button type="button" variant="ghost" onclick="window.location='{{ route('access.permissions.index') }}'">Reset</x-ui.button>
            </div>
        </form>
    </x-ui.page-section>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <x-ui.stat-card label="Rows" :value="$summaryStats['total_rows']" hint="Permissions sesuai filter" />
        <x-ui.stat-card label="Modules" :value="$summaryStats['module_rows']" hint="Group module" />
        <x-ui.stat-card label="Active" :value="$summaryStats['active_rows']" hint="Permission aktif" />
    </div>

    <div class="grid gap-4 xl:grid-cols-2">
        @forelse($permissionsByModule as $module => $permissions)
            <x-ui.section-card :title="$module" :subtitle="sprintf('%d permissions', $permissions->count())">
                <div class="space-y-3">
                    @foreach($permissions as $permission)
                        <div class="rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-3">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <div class="text-sm font-medium text-slate-900">
                                        {{ $permission->permission_name }}
                                    </div>
                                    <div class="mt-1 text-xs text-slate-500">
                                        {{ $permission->permission_code }}
                                    </div>
                                </div>

                                <div class="shrink-0">
                                    <x-ui.status-badge :label="(string) $permission->roles_count . ' roles'" tone="info" />
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-ui.section-card>
        @empty
            <div class="xl:col-span-2">
                <x-ui.empty-state title="No permissions found" description="Belum ada permission sesuai filter." />
            </div>
        @endforelse
    </div>
</div>
@endsection