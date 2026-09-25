@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Leave Requests"
        subtitle="Kelola request cuti dan izin karyawan."
        :breadcrumbs="[
            ['label' => 'Requests'],
            ['label' => 'Leave Requests'],
        ]"
    >
        <x-slot:actions>
            @if(auth()->user()->hasPermission('leave.manage'))
                <x-ui.button
                    variant="primary"
                    onclick="window.location='{{ route('requests.leave-requests.create') }}'"
                >
                    Add Leave Request
                </x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.page-section
        title="Request Directory"
        subtitle="Filter berdasarkan employee, leave type, status, dan range tanggal."
    >
        <form method="GET" class="grid gap-4 lg:grid-cols-[1fr_1fr_180px_180px_180px_auto]">
            <x-ui.field label="Search">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari emp code / nama" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
            </x-ui.field>

            <x-ui.field label="Leave Type">
                <select name="leave_type_id" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                    <option value="">Semua leave type</option>
                    @foreach($leaveTypes as $leaveType)
                        <option value="{{ $leaveType->leave_type_id }}" @selected((string) request('leave_type_id') === (string) $leaveType->leave_type_id)>
                            {{ $leaveType->leave_type_name }}
                        </option>
                    @endforeach
                </select>
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
                <x-ui.button type="button" variant="ghost" onclick="window.location='{{ route('requests.leave-requests.index') }}'">Reset</x-ui.button>
            </div>
        </form>
    </x-ui.page-section>

    <x-ui.table-shell>
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50/80">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Employee</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Leave Type</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Date Range</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Partial</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse($requests as $leaveRequest)
                    @php
                        $statusClass = match($leaveRequest->request_status_code) {
                            'PENDING' => 'bg-amber-100 text-amber-700 ring-1 ring-amber-200',
                            'APPROVED' => 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200',
                            'REJECTED' => 'bg-rose-100 text-rose-700 ring-1 ring-rose-200',
                            'CANCELLED' => 'bg-slate-100 text-slate-700 ring-1 ring-slate-200',
                            default => 'bg-slate-100 text-slate-700 ring-1 ring-slate-200',
                        };
                    @endphp

                    <tr class="transition hover:bg-slate-50">
                        <td class="px-5 py-4">
                            <div class="font-medium text-slate-900">{{ $leaveRequest->employee->full_name ?? '-' }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ $leaveRequest->employee->emp_code ?? '-' }}</div>
                        </td>

                        <td class="px-5 py-4 text-slate-600">
                            {{ $leaveRequest->leaveType->leave_type_name ?? '-' }}
                        </td>

                        <td class="px-5 py-4 text-slate-600">
                            {{ optional($leaveRequest->start_date)->format('Y-m-d') }}
                            —
                            {{ optional($leaveRequest->end_date)->format('Y-m-d') }}
                        </td>

                        <td class="px-5 py-4 text-slate-600">
                            {{ $leaveRequest->partial_day_flag ? 'Yes' : 'No' }}
                        </td>

                        <td class="px-5 py-4">
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium {{ $statusClass }}">
                                {{ $leaveRequest->request_status_code }}
                            </span>
                        </td>

                        <td class="px-5 py-4">
                            <div class="flex justify-end gap-2">
                                <x-ui.button
                                    size="sm"
                                    variant="ghost"
                                    onclick="window.location='{{ route('requests.leave-requests.show', $leaveRequest->leave_request_id) }}'"
                                >
                                    Detail
                                </x-ui.button>

                                @if(
                                    auth()->user()->hasPermission('leave.manage')
                                    && $leaveRequest->request_status_code === 'PENDING'
                                )
                                    <x-ui.button
                                        size="sm"
                                        onclick="window.location='{{ route('requests.leave-requests.edit', $leaveRequest->leave_request_id) }}'"
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
                                title="No leave requests found"
                                description="Belum ada data leave request."
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