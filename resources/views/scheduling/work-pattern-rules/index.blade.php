@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Work Pattern Rules"
        subtitle="Kelola rule detail untuk setiap work pattern."
        :breadcrumbs="[
            ['label' => 'Scheduling'],
            ['label' => 'Work Pattern Rules'],
        ]"
    >
        <x-slot:actions>
            @if(auth()->user()->hasPermission('workpattern.manage'))
                <x-ui.button
                    variant="primary"
                    onclick="window.location='{{ route('scheduling.work-pattern-rules.create') }}'"
                >
                    Add Rule
                </x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.page-section
        title="Rule Directory"
        subtitle="Filter berdasarkan work pattern, rule type, dan status."
    >
        <form method="GET" class="grid gap-4 lg:grid-cols-[1fr_1fr_1fr_auto]">
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

            <x-ui.field label="Rule Type">
                <select name="rule_type_code" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                    <option value="">Semua rule type</option>
                    @foreach($ruleTypes as $type)
                        <option value="{{ $type->rule_type_code }}" @selected(request('rule_type_code') === $type->rule_type_code)>
                            {{ $type->rule_type_name }}
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
                    onclick="window.location='{{ route('scheduling.work-pattern-rules.index') }}'"
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
                        Pattern
                    </th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Rule
                    </th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Type
                    </th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Day
                    </th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Priority
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
                @forelse($rules as $rule)
                    <tr class="transition hover:bg-slate-50">
                        <td class="px-5 py-4 text-slate-600">
                            {{ $rule->workPattern->work_pattern_name ?? '-' }}
                        </td>

                        <td class="px-5 py-4">
                            <div class="font-medium text-slate-900">
                                {{ $rule->rule_name }}
                            </div>
                            <div class="mt-1 text-xs text-slate-500">
                                {{ $rule->rule_code }}
                            </div>
                        </td>

                        <td class="px-5 py-4 text-slate-600">
                            {{ $rule->ruleType->rule_type_name ?? $rule->rule_type_code }}
                        </td>

                        <td class="px-5 py-4 text-slate-600">
                            {{ $rule->dayOfWeek->day_of_week_name ?? '-' }}
                        </td>

                        <td class="px-5 py-4 text-slate-600">
                            {{ $rule->priority_order }}
                        </td>

                        <td class="px-5 py-4">
                            @if($rule->active)
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
                                    onclick="window.location='{{ route('scheduling.work-pattern-rules.show', $rule->work_pattern_rule_id) }}'"
                                >
                                    Detail
                                </x-ui.button>

                                @if(auth()->user()->hasPermission('workpattern.manage'))
                                    <x-ui.button
                                        size="sm"
                                        onclick="window.location='{{ route('scheduling.work-pattern-rules.edit', $rule->work_pattern_rule_id) }}'"
                                    >
                                        Edit
                                    </x-ui.button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-14">
                            <x-ui.empty-state
                                title="No rules found"
                                description="Belum ada data work pattern rule."
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if(method_exists($rules, 'hasPages') && $rules->hasPages())
            <div class="border-t border-slate-200 bg-white px-5 py-4">
                {{ $rules->links() }}
            </div>
        @endif
    </x-ui.table-shell>
</div>
@endsection