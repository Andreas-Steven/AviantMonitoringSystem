@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Departments"
        subtitle="Kelola master department sebagai fondasi struktur organisasi."
        :breadcrumbs="[
            ['label' => 'Master'],
            ['label' => 'Departments'],
        ]"
    >
        <x-slot:actions>
            @if(auth()->user()->hasPermission('department.manage'))
                <x-ui.button variant="primary" onclick="window.location='{{ route('master.departments.create') }}'">Add Department</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.page-section title="Department Directory" subtitle="Cari dan review data department yang aktif maupun nonaktif.">
        <form method="GET" action="{{ route('master.departments.index') }}" class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_220px_auto]">
            <x-ui.field label="Search">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari dept code / dept name" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
            </x-ui.field>
            <x-ui.field label="Status">
                <select name="active" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                    <option value="">All</option>
                    <option value="1" @selected((string) request('active') === '1')>Active</option>
                    <option value="0" @selected((string) request('active') === '0')>Inactive</option>
                </select>
            </x-ui.field>
            <div class="flex items-end gap-3">
                <x-ui.button type="submit">Search</x-ui.button>
                <x-ui.button type="button" variant="ghost" onclick="window.location='{{ route('master.departments.index') }}'">Reset</x-ui.button>
            </div>
        </form>
    </x-ui.page-section>

    <x-ui.table-shell>
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50/80">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Code</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Name</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse($departments as $department)
                    <tr class="transition hover:bg-slate-50">
                        <td class="px-5 py-4 font-medium text-slate-900">{{ $department->dept_code }}</td>
                        <td class="px-5 py-4 text-slate-700">{{ $department->dept_name }}</td>
                        <td class="px-5 py-4">
                            @if($department->active)
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200">Active</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700 ring-1 ring-slate-200">Inactive</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-right">
                            @if(auth()->user()->hasPermission('department.manage'))
                                <x-ui.button type="button" size="sm" onclick="window.location='{{ route('master.departments.edit', $department->dept_id) }}'">Edit</x-ui.button>
                            @else
                                <span class="text-xs text-slate-400">No action</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-14"><x-ui.empty-state title="No departments found" description="Belum ada data department untuk filter yang sedang dipakai." /></td></tr>
                @endforelse
            </tbody>
        </table>
        @if($departments->hasPages())<div class="border-t border-slate-200 bg-white px-5 py-4">{{ $departments->withQueryString()->links() }}</div>@endif
    </x-ui.table-shell>
</div>
@endsection
