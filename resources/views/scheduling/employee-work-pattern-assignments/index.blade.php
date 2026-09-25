@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Employee Work Pattern Assignments"
        subtitle="Kelola assignment work pattern per employee."
        :breadcrumbs="[
            ['label' => 'Scheduling'],
            ['label' => 'Employee Work Pattern Assignments'],
        ]"
    >
        <x-slot:actions>
            <div class="flex items-center gap-3">
                <x-ui.button
                    type="button"
                    variant="ghost"
                    onclick="window.location='{{ route('scheduling.employee-work-pattern-assignments.coverage') }}'"
                >
                    Coverage
                </x-ui.button>

                @if(auth()->user()?->hasPermission('workpattern.manage') && Route::has('scheduling.employee-work-pattern-assignments.import.create'))
                    <x-ui.button
                        type="button"
                        onclick="window.location='{{ route('scheduling.employee-work-pattern-assignments.import.create') }}'"
                    >
                        Import Work Pattern Assignment
                    </x-ui.button>
                @endif

                @if(auth()->user()->hasPermission('workpattern.manage'))
                    <x-ui.button
                        type="button"
                        variant="ghost"
                        onclick="window.location='{{ route('scheduling.employee-work-pattern-assignments.bulk-create') }}'"
                    >
                        Bulk Assignment
                    </x-ui.button>

                    <x-ui.button
                        variant="primary"
                        onclick="window.location='{{ route('scheduling.employee-work-pattern-assignments.create') }}'"
                    >
                        Add Assignment
                    </x-ui.button>
                @endif
            </div>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.page-section
        title="Assignment Directory"
        subtitle="Filter berdasarkan employee dan work pattern."
    >
        <form method="GET" class="grid gap-4 lg:grid-cols-[1fr_1fr_auto]">
            <x-ui.field label="Search">
                <input
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Cari emp code / nama"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                >
            </x-ui.field>

            <x-ui.field label="Work Pattern">
                <select name="work_pattern_id" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                    <option value="">Semua work pattern</option>
                    @foreach($workPatterns as $pattern)
                        <option value="{{ $pattern->work_pattern_id }}" @selected((string) request('work_pattern_id') === (string) $pattern->work_pattern_id)>
                            {{ $pattern->work_pattern_name }}
                        </option>
                    @endforeach
                </select>
            </x-ui.field>

            <div class="flex items-end gap-3">
                <x-ui.button type="submit">
                    Search
                </x-ui.button>

                <x-ui.button
                    type="button"
                    variant="ghost"
                    onclick="window.location='{{ route('scheduling.employee-work-pattern-assignments.index') }}'"
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
                        Work Pattern
                    </th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Effective Start
                    </th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Effective End
                    </th>
                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Action
                    </th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse($assignments as $assignment)
                    <tr class="transition hover:bg-slate-50">
                        <td class="px-5 py-4">
                            <div class="font-medium text-slate-900">
                                {{ $assignment->employee->full_name ?? '-' }}
                            </div>
                            <div class="mt-1 text-xs text-slate-500">
                                {{ $assignment->employee->emp_code ?? '-' }}
                            </div>
                        </td>

                        <td class="px-5 py-4">
                            <div class="font-medium text-slate-900">
                                {{ $assignment->workPattern->work_pattern_name ?? '-' }}
                            </div>
                            <div class="mt-1 text-xs text-slate-500">
                                {{ $assignment->workPattern->work_pattern_code ?? '-' }}
                            </div>
                        </td>

                        <td class="px-5 py-4 text-slate-600">
                            {{ optional($assignment->effective_start_date)->format('Y-m-d') }}
                        </td>

                        <td class="px-5 py-4 text-slate-600">
                            {{ optional($assignment->effective_end_date)->format('Y-m-d') ?? '-' }}
                        </td>

                        <td class="px-5 py-4">
                            <div class="flex justify-end gap-2">
                                <x-ui.button
                                    size="sm"
                                    variant="ghost"
                                    onclick="window.location='{{ route('scheduling.employee-work-pattern-assignments.show', $assignment->employee_work_pattern_assignment_id) }}'"
                                >
                                    Detail
                                </x-ui.button>

                                @if(auth()->user()->hasPermission('workpattern.manage'))
                                    <x-ui.button
                                        size="sm"
                                        onclick="window.location='{{ route('scheduling.employee-work-pattern-assignments.edit', $assignment->employee_work_pattern_assignment_id) }}'"
                                    >
                                        Edit
                                    </x-ui.button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-14">
                            <x-ui.empty-state
                                title="No assignments found"
                                description="Belum ada data employee work pattern assignment."
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if(method_exists($assignments, 'hasPages') && $assignments->hasPages())
            <div class="border-t border-slate-200 bg-white px-5 py-4">
                {{ $assignments->links() }}
            </div>
        @endif
    </x-ui.table-shell>
</div>
@endsection