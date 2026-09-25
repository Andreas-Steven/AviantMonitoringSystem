@extends('layouts.app')

@section('content')
<div class="space-y-6">

    <x-ui.page-header
        title="Normalized Logs"
        subtitle="Bridge layer antara raw logs dan attendance daily. Gunakan untuk investigasi hasil normalisasi dan linkage ke daily / review."
        :breadcrumbs="[
            ['label' => 'Attendance'],
            ['label' => 'Normalized Logs'],
        ]"
    />

    {{-- ================= FILTER ================= --}}
    <x-ui.page-section
        title="Filter Logs"
        subtitle="Filter berdasarkan employee, tanggal, status, dan keyword."
    >
        <form method="GET" action="{{ route('attendance.normalized-logs.index') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">

            <x-ui.field label="Search">
                <input
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="emp code / nama / device"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                >
            </x-ui.field>

            <x-ui.field label="Date From">
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
            </x-ui.field>

            <x-ui.field label="Date To">
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
            </x-ui.field>

            <x-ui.field label="Status">
                <select name="normalized_status_code"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                    <option value="">All Status</option>
                    @foreach($normalizedStatuses as $status)
                        <option value="{{ $status }}" @selected(request('normalized_status_code') === $status)>
                            {{ $status }}
                        </option>
                    @endforeach
                </select>
            </x-ui.field>

            <div class="flex items-end gap-3 xl:col-span-4">
                <x-ui.button type="submit">Search</x-ui.button>

                <x-ui.button
                    type="button"
                    variant="ghost"
                    onclick="window.location='{{ route('attendance.normalized-logs.index') }}'"
                >
                    Reset
                </x-ui.button>
            </div>
        </form>
    </x-ui.page-section>

    {{-- ================= TABLE ================= --}}
    <x-ui.table-shell>
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50/80">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">Employee</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">Datetime</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">Event</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">Status</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">Raw Source</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">Daily</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">Notes</th>
                    <th class="px-5 py-3 text-right text-xs font-semibold text-slate-500">Action</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse($rows as $row)

                    @php
                        $statusTone = match($row->normalized_status_code) {
                            'VALID' => 'success',
                            'INVALID' => 'danger',
                            'DUPLICATE' => 'warning',
                            default => 'neutral',
                        };
                    @endphp

                    <tr class="hover:bg-slate-50 transition">

                        {{-- EMPLOYEE --}}
                        <td class="px-5 py-4">
                            <div class="font-medium text-slate-900">
                                {{ $row->employee->full_name ?? '-' }}
                            </div>
                            <div class="text-xs text-slate-500 mt-1">
                                {{ $row->employee->emp_code ?? '-' }}
                            </div>
                        </td>

                        {{-- DATETIME --}}
                        <td class="px-5 py-4 text-slate-700">
                            {{ \Carbon\Carbon::parse($row->log_datetime)->format('Y-m-d H:i:s') }}
                        </td>

                        {{-- EVENT --}}
                        <td class="px-5 py-4 text-slate-700">
                            {{ $row->derived_event_type_code ?? '-' }}
                        </td>

                        {{-- STATUS --}}
                        <td class="px-5 py-4">
                            <x-ui.status-badge :label="$row->normalized_status_code" :tone="$statusTone" />
                        </td>

                        {{-- RAW SOURCE --}}
                        <td class="px-5 py-4 text-slate-600">
                            <div class="font-medium text-slate-900">
                                {{ optional($row->rawLog)->source_system ?? '-' }}
                            </div>

                            <div class="text-xs text-slate-500 mt-1">
                                Device: {{ optional($row->rawLog)->device_id ?? '-' }}
                            </div>

                            <div class="text-xs text-slate-500">
                                Batch: {{ optional($row->rawLog)->attendance_import_batch_id ?? '-' }}
                            </div>
                        </td>

                        {{-- DAILY --}}
                        <td class="px-5 py-4">
                            @if($row->matched_daily_id)
                                <div class="flex flex-col gap-2">
                                    <x-ui.status-badge label="Linked" tone="success" />

                                    <a
                                        href="{{ route('attendance.daily.show', $row->matched_daily_id) }}"
                                        class="text-xs text-indigo-600 hover:underline"
                                    >
                                        Daily #{{ $row->matched_daily_id }}
                                    </a>
                                </div>
                            @else
                                <div class="flex flex-col gap-1">
                                    <x-ui.status-badge label="Not Built" tone="warning" />
                                </div>
                            @endif
                        </td>

                        {{-- NOTES --}}
                        <td class="px-5 py-4 text-sm text-slate-600">
                            <div class="max-w-md whitespace-normal break-words overflow-hidden max-h-[4.5rem]" title="{{ $row->notes }}">
                                {{ $row->notes ?: '-' }}
                            </div>
                        </td>

                        {{-- ACTION --}}
                        <td class="px-5 py-4">
                            <div class="flex flex-wrap justify-end gap-2">

                                <a
                                    href="{{ route('attendance.normalized-logs.show', $row->normalized_log_id) }}"
                                    class="inline-flex items-center rounded-xl border px-3 py-2 text-xs text-slate-600 hover:border-slate-300"
                                >
                                    Detail
                                </a>

                                {{-- DAILY --}}
                                @if($row->matched_daily_id)
                                    <a
                                        href="{{ route('attendance.daily.show', $row->matched_daily_id) }}"
                                        class="inline-flex items-center rounded-xl border px-3 py-2 text-xs text-slate-600"
                                    >
                                        Daily
                                    </a>
                                @endif

                                {{-- RAW --}}
                                @if($row->log_id)
                                    <a
                                        href="{{ route('attendance.raw-logs.show', $row->log_id) }}"
                                        class="inline-flex items-center rounded-xl border px-3 py-2 text-xs text-slate-600"
                                    >
                                        Raw
                                    </a>
                                @endif

                                {{-- REVIEW --}}
                                <a
                                    href="{{ route('review.attendance-cases.index', [
                                        'q' => $row->employee->emp_code ?? null,
                                        'date_from' => \Carbon\Carbon::parse($row->log_datetime)->format('Y-m-d'),
                                        'date_to' => \Carbon\Carbon::parse($row->log_datetime)->format('Y-m-d'),
                                    ]) }}"
                                    class="inline-flex items-center rounded-xl border px-3 py-2 text-xs text-slate-600"
                                >
                                    Review
                                </a>

                            </div>
                        </td>

                    </tr>

                @empty
                    <tr>
                        <td colspan="8" class="px-5 py-14 text-center">
                            <x-ui.empty-state
                                title="No normalized logs"
                                description="Tidak ada data sesuai filter."
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- PAGINATION --}}
        @if($rows->hasPages())
            <div class="border-t px-5 py-4">
                {{ $rows->withQueryString()->links() }}
            </div>
        @endif
    </x-ui.table-shell>

</div>
@endsection