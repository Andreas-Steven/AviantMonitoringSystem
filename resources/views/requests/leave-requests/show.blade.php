@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Leave Request Detail"
        subtitle="Review leave request and approval information."
        :breadcrumbs="[
            ['label' => 'Requests'],
            ['label' => 'Leave Requests', 'url' => route('requests.leave-requests.index')],
            ['label' => 'Detail'],
        ]"
    >
        <x-slot:actions>
            @if(auth()->user()->hasPermission('leave.manage') && $leaveRequest->request_status_code === 'PENDING')
                <x-ui.button
                    variant="ghost"
                    onclick="window.location='{{ route('requests.leave-requests.edit', $leaveRequest->leave_request_id) }}'"
                >
                    Edit
                </x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.page-section title="Request Information">
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <div class="text-xs text-slate-500">Employee</div>
                <div class="mt-1 font-medium text-slate-900">{{ $leaveRequest->employee->full_name ?? '-' }}</div>
            </div>
            <div>
                <div class="text-xs text-slate-500">Employee Code</div>
                <div class="mt-1 font-medium text-slate-900">{{ $leaveRequest->employee->emp_code ?? '-' }}</div>
            </div>
            <div>
                <div class="text-xs text-slate-500">Leave Type</div>
                <div class="mt-1 font-medium text-slate-900">{{ $leaveRequest->leaveType->leave_type_name ?? '-' }}</div>
            </div>
            <div>
                <div class="text-xs text-slate-500">Status</div>
                <div class="mt-1 font-medium text-slate-900">{{ $leaveRequest->request_status_code }}</div>
            </div>
            <div>
                <div class="text-xs text-slate-500">Start Date</div>
                <div class="mt-1 font-medium text-slate-900">{{ optional($leaveRequest->start_date)->format('Y-m-d') }}</div>
            </div>
            <div>
                <div class="text-xs text-slate-500">End Date</div>
                <div class="mt-1 font-medium text-slate-900">{{ optional($leaveRequest->end_date)->format('Y-m-d') }}</div>
            </div>
            <div>
                <div class="text-xs text-slate-500">Partial Day</div>
                <div class="mt-1 font-medium text-slate-900">{{ $leaveRequest->partial_day_flag ? 'Yes' : 'No' }}</div>
            </div>
            <div>
                <div class="text-xs text-slate-500">Partial Time</div>
                <div class="mt-1 font-medium text-slate-900">
                    {{ $leaveRequest->partial_start_time ?? '-' }} — {{ $leaveRequest->partial_end_time ?? '-' }}
                </div>
            </div>
            <div>
                <div class="text-xs text-slate-500">Reason</div>
                <div class="mt-1 font-medium text-slate-900">{{ $leaveRequest->reason ?: '-' }}</div>
            </div>
            <div>
                <div class="text-xs text-slate-500">Attachment URL</div>
                <div class="mt-1 font-medium text-slate-900">{{ $leaveRequest->attachment_url ?: '-' }}</div>
            </div>
            <div>
                <div class="text-xs text-slate-500">Approved At</div>
                <div class="mt-1 font-medium text-slate-900">{{ optional($leaveRequest->approved_at)->format('Y-m-d H:i') ?: '-' }}</div>
            </div>
            <div>
                <div class="text-xs text-slate-500">Approved By</div>
                <div class="mt-1 font-medium text-slate-900">{{ $leaveRequest->approver->full_name ?? '-' }}</div>
            </div>
            <div class="md:col-span-2">
                <div class="text-xs text-slate-500">Notes</div>
                <div class="mt-1 whitespace-pre-line font-medium text-slate-900">{{ $leaveRequest->notes ?: '-' }}</div>
            </div>
        </div>
    </x-ui.page-section>

    @if(auth()->user()->hasPermission('leave.approve')&& $leaveRequest->request_status_code === 'PENDING')
        <x-ui.page-section title="Approval Actions" subtitle="Gunakan workflow approval terpisah, bukan edit form umum.">
            <div class="grid gap-6 xl:grid-cols-2">
                <form method="POST" action="{{ route('requests.leave-requests.approve', $leaveRequest->leave_request_id) }}" class="space-y-4 rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
                    @csrf

                    <div class="text-sm font-semibold text-emerald-800">Approve Request</div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                        User login aktif:
                        <span class="font-medium text-slate-900">
                            {{ auth()->user()->full_name ?? auth()->user()->email ?? '-' }}
                        </span>
                        @if(auth()->user()?->employee_id)
                            <span class="text-slate-500">(Employee ID: {{ auth()->user()->employee_id }})</span>
                        @else
                            <span class="text-rose-600">(belum terhubung ke employee)</span>
                        @endif
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Approved At</label>
                        <input type="datetime-local" name="approved_at" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Approval Note</label>
                        <textarea name="approval_note" rows="3" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"></textarea>
                    </div>

                    <div class="flex justify-end">
                        <x-ui.button type="submit" variant="primary">Approve</x-ui.button>
                    </div>
                </form>

                <form method="POST" action="{{ route('requests.leave-requests.reject', $leaveRequest->leave_request_id) }}" class="space-y-4 rounded-2xl border border-rose-200 bg-rose-50 p-5">
                    @csrf

                    <div class="text-sm font-semibold text-rose-800">Reject Request</div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                        User login aktif:
                        <span class="font-medium text-slate-900">
                            {{ auth()->user()->full_name ?? auth()->user()->email ?? '-' }}
                        </span>
                        @if(auth()->user()?->employee_id)
                            <span class="text-slate-500">(Employee ID: {{ auth()->user()->employee_id }})</span>
                        @else
                            <span class="text-rose-600">(belum terhubung ke employee)</span>
                        @endif
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Rejected At</label>
                        <input type="datetime-local" name="approved_at" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Rejection Reason</label>
                        <textarea name="rejection_reason" rows="3" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm" required></textarea>
                    </div>

                    <div class="flex justify-end">
                        <x-ui.button type="submit" variant="ghost">Reject</x-ui.button>
                    </div>
                </form>
            </div>
        </x-ui.page-section>
    @endif

    @if(auth()->user()->hasPermission('leave.manage') && $leaveRequest->request_status_code === 'PENDING')
        <x-ui.page-section title="Cancel Request" subtitle="Gunakan cancel jika request tidak jadi diproses.">
            <form method="POST" action="{{ route('requests.leave-requests.cancel', $leaveRequest->leave_request_id) }}" class="space-y-4 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                @csrf

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Cancel Reason</label>
                    <textarea name="cancel_reason" rows="3" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm" required></textarea>
                </div>

                <div class="flex justify-end">
                    <x-ui.button type="submit" variant="ghost">Cancel Request</x-ui.button>
                </div>
            </form>
        </x-ui.page-section>
    @endif
</div>
@endsection