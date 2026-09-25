@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Employee Shift Rosters"
        subtitle="Kelola roster harian employee."
        :breadcrumbs="[
            ['label' => 'Scheduling'],
            ['label' => 'Employee Shift Rosters'],
        ]"
    >
        <x-slot:actions>
            <div class="flex items-center gap-3">
                <x-ui.button
                    type="button"
                    variant="ghost"
                    onclick="window.location='{{ route('scheduling.employee-shift-rosters.coverage') }}'"
                >
                    Coverage
                </x-ui.button>

                @if(auth()->user()->hasPermission('roster.manage'))
                    <x-ui.button
                        variant="primary"
                        onclick="window.location='{{ route('scheduling.employee-shift-rosters.create') }}'"
                    >
                        Add Roster
                    </x-ui.button>
                @endif
            </div>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.page-section
        title="Roster Directory"
        subtitle="Filter berdasarkan employee, shift, source type, dan range tanggal."
    >
        <form method="GET" class="grid gap-4 lg:grid-cols-[1fr_1fr_1fr_180px_180px_auto]">
            <x-ui.field label="Search">
                <input
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Cari emp code / nama"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                >
            </x-ui.field>

            <x-ui.field label="Shift">
                <select name="shift_id" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                    <option value="">Semua shift</option>
                    @foreach($shifts as $shift)
                        <option value="{{ $shift->shift_id }}" @selected((string) request('shift_id') === (string) $shift->shift_id)>
                            {{ $shift->shift_name }}
                        </option>
                    @endforeach
                </select>
            </x-ui.field>

            <x-ui.field label="Source Type">
                <select name="source_type_code" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                    <option value="">Semua source type</option>
                    @foreach($sourceTypes as $type)
                        <option value="{{ $type->source_type_code }}" @selected(request('source_type_code') === $type->source_type_code)>
                            {{ $type->source_type_name }}
                        </option>
                    @endforeach
                </select>
            </x-ui.field>

            <x-ui.field label="Date From">
                <input
                    type="date"
                    name="date_from"
                    value="{{ request('date_from') }}"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                >
            </x-ui.field>

            <x-ui.field label="Date To">
                <input
                    type="date"
                    name="date_to"
                    value="{{ request('date_to') }}"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                >
            </x-ui.field>

            <div class="flex items-end gap-3">
                <x-ui.button type="submit">
                    Search
                </x-ui.button>

                <x-ui.button
                    type="button"
                    variant="ghost"
                    onclick="window.location='{{ route('scheduling.employee-shift-rosters.index') }}'"
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
                        Employee
                    </th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Work Date
                    </th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Shift
                    </th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Source Type
                    </th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Published At
                    </th>
                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Action
                    </th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse($rosters as $roster)
                    <tr class="transition hover:bg-slate-50">
                        <td class="px-5 py-4">
                            <div class="font-medium text-slate-900">
                                {{ $roster->employee->full_name ?? '-' }}
                            </div>
                            <div class="mt-1 text-xs text-slate-500">
                                {{ $roster->employee->emp_code ?? '-' }}
                            </div>
                        </td>

                        <td class="px-5 py-4 text-slate-600">
                            {{ optional($roster->work_date)->format('Y-m-d') }}
                        </td>

                        <td class="px-5 py-4">
                            <div class="font-medium text-slate-900">
                                {{ $roster->shift->shift_name ?? '-' }}
                            </div>
                            <div class="mt-1 text-xs text-slate-500">
                                {{ $roster->shift->shift_code ?? '-' }}
                            </div>
                        </td>

                        <td class="px-5 py-4 text-slate-600">
                            {{ $roster->sourceType->source_type_name ?? $roster->source_type_code }}
                        </td>

                        <td class="px-5 py-4 text-slate-600">
                            {{ optional($roster->published_at)->format('Y-m-d H:i') ?? '-' }}
                        </td>

                        <td class="px-5 py-4">
                            <div class="flex justify-end gap-2">
                                <x-ui.button
                                    size="sm"
                                    variant="ghost"
                                    onclick="window.location='{{ route('scheduling.employee-shift-rosters.show', $roster->roster_id) }}'"
                                >
                                    Detail
                                </x-ui.button>

                                @if(auth()->user()->hasPermission('roster.manage'))
                                    <x-ui.button
                                        size="sm"
                                        onclick="window.location='{{ route('scheduling.employee-shift-rosters.edit', $roster->roster_id) }}'"
                                    >
                                        Edit
                                    </x-ui.button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-14">
                            <x-ui.empty-state
                                title="No rosters found"
                                description="Belum ada data employee shift roster."
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if(method_exists($rosters, 'hasPages') && $rosters->hasPages())
            <div class="border-t border-slate-200 bg-white px-5 py-4">
                {{ $rosters->links() }}
            </div>
        @endif
    </x-ui.table-shell>
</div>
@endsection