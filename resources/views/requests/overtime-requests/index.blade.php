@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Overtime Requests"
        subtitle="Manage overtime requests and approval workflow."
        :breadcrumbs="[
            ['label' => 'Requests'],
            ['label' => 'Overtime Requests'],
        ]"
    >
        <x-slot:actions>
            @if(auth()->user()->hasPermission('overtime.manage'))
                <x-ui.button onclick="window.location='{{ route('requests.overtime-requests.create') }}'">
                    Create Overtime Request
                </x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.page-section title="Filters" subtitle="Search and narrow overtime requests.">
        <form method="GET" class="grid gap-4 md:grid-cols-5">
            <x-ui.field label="Search">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Employee name / code" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
            </x-ui.field>

            <x-ui.field label="Status">
                <select name="request_status_code" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                    <option value="">Semua status</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status['code'] }}" @selected(request('request_status_code') === $status['code'])>
                            {{ $status['name'] }}
                        </option>
                    @endforeach
                </select>
            </x-ui.field>

            <x-ui.field label="Date From">
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
            </x-ui.field>

            <x-ui.field label="Date To">
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
            </x-ui.field>

            <div class="flex items-end gap-3">
                <x-ui.button type="submit">Search</x-ui.button>
                <x-ui.button type="button" variant="ghost" onclick="window.location='{{ route('requests.overtime-requests.index') }}'">Reset</x-ui.button>
            </div>
        </form>
    </x-ui.page-section>

    <x-ui.table-shell>
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50/80">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Employee</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Work Date</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Planned</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Actual</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse($requests as $overtimeRequest)
                    @php
                        $statusClass = match($overtimeRequest->request_status_code) {
                            'PENDING' => 'bg-amber-100 text-amber-700 ring-1 ring-amber-200',
                            'APPROVED' => 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200',
                            'REJECTED' => 'bg-rose-100 text-rose-700 ring-1 ring-rose-200',
                            'CANCELLED' => 'bg-slate-100 text-slate-700 ring-1 ring-slate-200',
                            default => 'bg-slate-100 text-slate-700 ring-1 ring-slate-200',
                        };
                    @endphp

                    <tr class="transition hover:bg-slate-50">
                        <td class="px-5 py-4">
                            <div class="font-medium text-slate-900">{{ $overtimeRequest->employee->full_name ?? '-' }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ $overtimeRequest->employee->emp_code ?? '-' }}</div>
                        </td>

                        <td class="px-5 py-4 text-slate-600">
                            {{ optional($overtimeRequest->work_date)->format('Y-m-d') }}
                        </td>

                        <td class="px-5 py-4 text-slate-600">
                            <div>{{ optional($overtimeRequest->planned_start_datetime)->format('Y-m-d H:i') ?: '-' }}</div>
                            <div class="text-xs text-slate-400">{{ optional($overtimeRequest->planned_end_datetime)->format('Y-m-d H:i') ?: '-' }}</div>
                        </td>

                        <td class="px-5 py-4 text-slate-600">
                            <div>{{ optional($overtimeRequest->actual_start_datetime)->format('Y-m-d H:i') ?: '-' }}</div>
                            <div class="text-xs text-slate-400">{{ optional($overtimeRequest->actual_end_datetime)->format('Y-m-d H:i') ?: '-' }}</div>
                        </td>

                        <td class="px-5 py-4">
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium {{ $statusClass }}">
                                {{ $overtimeRequest->request_status_code }}
                            </span>
                        </td>

                        <td class="px-5 py-4">
                            <div class="flex justify-end gap-2">
                                <x-ui.button
                                    size="sm"
                                    variant="ghost"
                                    onclick="window.location='{{ route('requests.overtime-requests.show', $overtimeRequest->overtime_request_id) }}'"
                                >
                                    Detail
                                </x-ui.button>

                                @if(
                                    auth()->user()->hasPermission('overtime.manage')
                                    && $overtimeRequest->request_status_code === 'PENDING'
                                )
                                    <x-ui.button
                                        size="sm"
                                        onclick="window.location='{{ route('requests.overtime-requests.edit', $overtimeRequest->overtime_request_id) }}'"
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
                                title="No overtime requests found"
                                description="Belum ada data overtime request."
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($requests->hasPages())
            <div class="border-t border-slate-200 bg-white px-5 py-4">
                {{ $requests->withQueryString()->links() }}
            </div>
        @endif
    </x-ui.table-shell>
</div>
@endsection