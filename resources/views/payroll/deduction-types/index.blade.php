@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Payroll Deduction Types"
        subtitle="Master jenis potongan payroll untuk attendance, charge, kasbon, dan debt-forming deduction."
        :breadcrumbs="[
            ['label' => 'Payroll'],
            ['label' => 'Deduction Types'],
        ]"
    >
        <x-slot:actions>
            @if(auth()->user()?->hasPermission('payroll_deduction.manage'))
                <x-ui.button onclick="window.location='{{ route('payroll.deduction-types.create') }}'">
                    Add Deduction Type
                </x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.page-section
        title="Filters"
        subtitle="Cari deduction type berdasarkan code, nama, category, status aktif, dan debt-forming default."
    >
        <form method="GET" action="{{ route('payroll.deduction-types.index') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.field label="Keyword">
                <input
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Cari code / nama / category"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                >
            </x-ui.field>

            <x-ui.field label="Category">
                <select name="category_code" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                    <option value="">Semua category</option>
                    @foreach($categories as $category)
                        <option value="{{ $category }}" @selected(request('category_code') === $category)>
                            {{ $category }}
                        </option>
                    @endforeach
                </select>
            </x-ui.field>

            <x-ui.field label="Active">
                <select name="active" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                    <option value="">Semua</option>
                    <option value="1" @selected(request('active') === '1')>Active</option>
                    <option value="0" @selected(request('active') === '0')>Inactive</option>
                </select>
            </x-ui.field>

            <x-ui.field label="Debt Forming Default">
                <select name="debt_forming_default_flag" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                    <option value="">Semua</option>
                    <option value="1" @selected(request('debt_forming_default_flag') === '1')>Yes</option>
                    <option value="0" @selected(request('debt_forming_default_flag') === '0')>No</option>
                </select>
            </x-ui.field>

            <div class="flex items-end gap-3 xl:col-span-4">
                <x-ui.button type="submit">Search</x-ui.button>
                <x-ui.button type="button" variant="ghost" onclick="window.location='{{ route('payroll.deduction-types.index') }}'">Reset</x-ui.button>
            </div>
        </form>
    </x-ui.page-section>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat-card label="Rows" :value="$summaryStats['total_rows']" hint="Total jenis potongan hasil filter" />
        <x-ui.stat-card label="Active" :value="$summaryStats['active_rows']" hint="Jenis potongan active" />
        <x-ui.stat-card label="Debt Forming" :value="$summaryStats['debt_forming_rows']" hint="Default membentuk hutang" />
        <x-ui.stat-card label="Attendance" :value="$summaryStats['attendance_rows']" hint="Attendance-based deduction" />
    </div>

    <x-ui.table-shell>
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50/80">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Code</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Name</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Category</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Flags</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Notes</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse($rows as $row)
                    <tr class="transition hover:bg-slate-50">
                        <td class="px-5 py-4">
                            <div class="font-semibold text-slate-900">{{ $row->deduction_code }}</div>
                        </td>
                        <td class="px-5 py-4">
                            <div class="font-medium text-slate-900">{{ $row->deduction_name }}</div>
                        </td>
                        <td class="px-5 py-4 text-slate-700">
                            {{ $row->category_code }}
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex flex-wrap gap-2">
                                <x-ui.status-badge :label="$row->active ? 'ACTIVE' : 'INACTIVE'" :tone="$row->active ? 'success' : 'neutral'" />
                                <x-ui.status-badge :label="$row->debt_forming_default_flag ? 'DEBT DEFAULT' : 'NON-DEBT DEFAULT'" :tone="$row->debt_forming_default_flag ? 'warning' : 'info'" />
                            </div>
                        </td>
                        <td class="px-5 py-4 text-slate-600">
                            <div class="max-w-lg whitespace-pre-line text-sm leading-6">{{ $row->notes ?: '-' }}</div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-14">
                            <x-ui.empty-state
                                title="No deduction types found"
                                description="Belum ada payroll deduction type sesuai filter."
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($rows->hasPages())
            <div class="border-t border-slate-200 bg-white px-5 py-4">
                {{ $rows->withQueryString()->links() }}
            </div>
        @endif
    </x-ui.table-shell>
</div>
@endsection