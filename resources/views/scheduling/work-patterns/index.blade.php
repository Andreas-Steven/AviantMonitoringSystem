@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Work Patterns"
        subtitle="Kelola pola evaluasi periodik untuk scheduling."
        :breadcrumbs="[
            ['label' => 'Scheduling'],
            ['label' => 'Work Patterns'],
        ]"
    >
        <x-slot:actions>
            @if(auth()->user()->hasPermission('workpattern.manage'))
                <x-ui.button
                    variant="primary"
                    onclick="window.location='{{ route('scheduling.work-patterns.create') }}'"
                >
                    Add Work Pattern
                </x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.page-section
        title="Work Pattern Directory"
        subtitle="Filter berdasarkan code, evaluation mode, dan status."
    >
        <form method="GET" class="grid gap-4 lg:grid-cols-[1fr_1fr_1fr_auto]">
            <x-ui.field label="Search">
                <input
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Cari code / name"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                >
            </x-ui.field>

            <x-ui.field label="Evaluation Mode">
                <select name="evaluation_mode_code" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                    <option value="">Semua evaluation mode</option>
                    @foreach($evaluationModes as $mode)
                        <option value="{{ $mode->evaluation_mode_code }}" @selected(request('evaluation_mode_code') === $mode->evaluation_mode_code)>
                            {{ $mode->evaluation_mode_name }}
                        </option>
                    @endforeach
                </select>
            </x-ui.field>

            <x-ui.field label="Status">
                <select name="active" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                    <option value="">Semua status</option>
                    <option value="1" @selected(request('active') === '1')>Active</option>
                    <option value="0" @selected(request('active') === '0')>Inactive</option>
                </select>
            </x-ui.field>

            <div class="flex items-end gap-3">
                <x-ui.button type="submit">
                    Search
                </x-ui.button>

                <x-ui.button
                    type="button"
                    variant="ghost"
                    onclick="window.location='{{ route('scheduling.work-patterns.index') }}'"
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
                        Evaluation Mode
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
                @forelse($workPatterns as $workPattern)
                    <tr class="transition hover:bg-slate-50">
                        <td class="px-5 py-4 text-slate-600">
                            {{ $workPattern->work_pattern_code }}
                        </td>

                        <td class="px-5 py-4 font-medium text-slate-900">
                            {{ $workPattern->work_pattern_name }}
                        </td>

                        <td class="px-5 py-4 text-slate-600">
                            {{ $workPattern->evaluationMode->evaluation_mode_name ?? $workPattern->evaluation_mode_code }}
                        </td>

                        <td class="px-5 py-4">
                            @if($workPattern->active)
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200">
                                    Active
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700 ring-1 ring-slate-200">
                                    Inactive
                                </span>
                            @endif
                        </td>

                        <td class="px-5 py-4">
                            <div class="flex justify-end gap-2">
                                <x-ui.button
                                    size="sm"
                                    variant="ghost"
                                    onclick="window.location='{{ route('scheduling.work-patterns.show', $workPattern->work_pattern_id) }}'"
                                >
                                    Detail
                                </x-ui.button>

                                @if(auth()->user()->hasPermission('workpattern.manage'))
                                    <x-ui.button
                                        size="sm"
                                        onclick="window.location='{{ route('scheduling.work-patterns.edit', $workPattern->work_pattern_id) }}'"
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
                                title="No work patterns found"
                                description="Belum ada data work pattern."
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if(method_exists($workPatterns, 'hasPages') && $workPatterns->hasPages())
            <div class="border-t border-slate-200 bg-white px-5 py-4">
                {{ $workPatterns->links() }}
            </div>
        @endif
    </x-ui.table-shell>
</div>
@endsection