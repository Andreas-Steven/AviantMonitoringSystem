@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Branches"
        subtitle="Kelola data cabang untuk kebutuhan operasional absensi."
        :breadcrumbs="[
            ['label' => 'Master'],
            ['label' => 'Branches'],
        ]"
    >
        <x-slot:actions>
            @if(auth()->user()->hasPermission('branch.manage'))
                <x-ui.button
                    variant="primary"
                    onclick="window.location='{{ route('master.branches.create') }}'"
                >
                    Add Branch
                </x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.page-section
        title="Branch Directory"
        subtitle="Cari dan review data cabang yang terdaftar."
    >
        <form method="GET" action="{{ route('master.branches.index') }}" class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_auto]">
            <x-ui.field label="Search">
                <input
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Cari branch code / branch name"
                    class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                >
            </x-ui.field>

            <div class="flex items-end gap-3">
                <x-ui.button type="submit">
                    Search
                </x-ui.button>

                <x-ui.button
                    type="button"
                    variant="ghost"
                    onclick="window.location='{{ route('master.branches.index') }}'"
                >
                    Reset
                </x-ui.button>
            </div>
        </form>
    </x-ui.page-section>

    <x-ui.table-shell>
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50/80">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Code
                    </th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Name
                    </th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Type
                    </th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Status
                    </th>
                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Action
                    </th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse ($branches as $branch)
                    <tr class="transition hover:bg-slate-50">
                        <td class="px-5 py-4 font-medium text-slate-900">
                            {{ $branch->branch_code }}
                        </td>

                        <td class="px-5 py-4">
                            <div class="font-medium text-slate-900">
                                {{ $branch->branch_name }}
                            </div>
                        </td>

                        <td class="px-5 py-4 text-slate-600">
                            {{ $branch->branch_type_code }}
                        </td>

                        <td class="px-5 py-4">
                            @if ($branch->active)
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200">
                                    Active
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700 ring-1 ring-slate-200">
                                    Inactive
                                </span>
                            @endif
                        </td>

                        <td class="px-5 py-4 text-right">
                            @if(auth()->user()->hasPermission('branch.manage'))
                                <x-ui.button
                                    type="button"
                                    size="sm"
                                    onclick="window.location='{{ route('master.branches.edit', $branch->branch_id) }}'"
                                >
                                    Edit
                                </x-ui.button>
                            @else
                                <span class="text-xs text-slate-400">No action</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-14">
                            <x-ui.empty-state
                                title="No branches found"
                                description="Belum ada data branch untuk filter yang sedang dipakai."
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if ($branches->hasPages())
            <div class="border-t border-slate-200 bg-white px-5 py-4">
                {{ $branches->withQueryString()->links() }}
            </div>
        @endif
    </x-ui.table-shell>
</div>
@endsection