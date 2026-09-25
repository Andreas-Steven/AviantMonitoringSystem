@extends('layouts.app')

@section('content')
@php
    $tableRows = $rows ?? $rawLogs ?? $logs ?? null;
@endphp

<div class="space-y-6">
    <x-ui.page-header
        title="Raw Attendance Logs"
        subtitle="Log mentah dari mesin absensi atau sumber import sebelum proses normalisasi."
        :breadcrumbs="[
            ['label' => 'Attendance'],
            ['label' => 'Raw Attendance Logs'],
        ]"
    >
        <x-slot:actions>
            @if(auth()->user()->hasPermission('attendance_raw.import'))
                <x-ui.button onclick="window.location='{{ route('attendance.raw-logs.import.create') }}'">
                    Import Raw Logs
                </x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.page-section
        title="Raw Log Filters"
        subtitle="Gunakan filter untuk menelusuri log mentah berdasarkan periode, branch, employee, device, dan sumber."
    >
        <form method="GET" action="{{ route('attendance.raw-logs.index') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.field label="Search">
                <input
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Cari employee / biometric / device"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                >
            </x-ui.field>

            @isset($payrollPeriods)
                <x-ui.field label="Payroll Period">
                    <select name="payroll_period_id" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                        <option value="">Semua period</option>
                        @foreach($payrollPeriods as $period)
                            <option value="{{ $period->payroll_period_id }}" @selected((string) request('payroll_period_id') === (string) $period->payroll_period_id)>
                                {{ $period->period_code }}
                                ({{ optional($period->period_start_date)->format('Y-m-d') }} s/d {{ optional($period->period_end_date)->format('Y-m-d') }})
                            </option>
                        @endforeach
                    </select>
                </x-ui.field>
            @endisset

            @isset($branches)
                <x-ui.field label="Branch">
                    <select name="branch_id" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                        <option value="">Semua branch</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->branch_id }}" @selected((string) request('branch_id') === (string) $branch->branch_id)>
                                {{ $branch->branch_name }}
                            </option>
                        @endforeach
                    </select>
                </x-ui.field>
            @endisset

            @isset($sourceSystems)
                <x-ui.field label="Source System">
                    <select name="source_system" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                        <option value="">Semua source</option>
                        @foreach($sourceSystems as $sourceSystem)
                            <option value="{{ $sourceSystem }}" @selected(request('source_system') === $sourceSystem)>
                                {{ $sourceSystem }}
                            </option>
                        @endforeach
                    </select>
                </x-ui.field>
            @endisset

            <x-ui.field label="Date From">
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
            </x-ui.field>

            <x-ui.field label="Date To">
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
            </x-ui.field>

            <x-ui.field label="Device ID">
                <input
                    type="text"
                    name="device_id"
                    value="{{ request('device_id') }}"
                    placeholder="Contoh: DEV-OFF-01"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                >
            </x-ui.field>

            <x-ui.field label="Device User ID">
                <input
                    type="text"
                    name="device_user_id"
                    value="{{ request('device_user_id') }}"
                    placeholder="Contoh: BIO001"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                >
            </x-ui.field>

            <div class="flex items-end gap-3 xl:col-span-4">
                <x-ui.button type="submit">Search</x-ui.button>
                <x-ui.button type="button" variant="ghost" onclick="window.location='{{ route('attendance.raw-logs.index') }}'">
                    Reset
                </x-ui.button>
            </div>
        </form>
    </x-ui.page-section>

    @if($tableRows)
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.stat-card
                title="Rows"
                :value="$tableRows->total()"
                description="Total raw logs hasil filter"
            />

            <x-ui.stat-card
                title="With Employee"
                :value="$tableRows->getCollection()->filter(fn($item) => !empty($item->emp_id))->count()"
                description="Row yang sudah terhubung ke employee"
            />

            <x-ui.stat-card
                title="IN Mode"
                :value="$tableRows->getCollection()->where('io_mode', 'IN')->count()"
                description="Raw logs IN di halaman ini"
            />

            <x-ui.stat-card
                title="OUT Mode"
                :value="$tableRows->getCollection()->where('io_mode', 'OUT')->count()"
                description="Raw logs OUT di halaman ini"
            />
        </div>
    @endif

    <x-ui.table-shell>
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50/80">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Employee</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Datetime</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Source</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Device</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Mode</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Branch</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Normalized</th>
                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse($tableRows ?? [] as $item)
                    @php
                        $ioTone = match($item->io_mode) {
                            'IN' => 'success',
                            'OUT' => 'info',
                            default => 'neutral',
                        };
                    @endphp

                    <tr class="transition hover:bg-slate-50">
                        <td class="px-5 py-4">
                            @if($item->employee)
                                <div class="font-medium text-slate-900">{{ $item->employee->full_name }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $item->employee->emp_code }}</div>
                            @else
                                <div class="font-medium text-slate-500">Unmapped Employee</div>
                                <div class="mt-1 text-xs text-slate-400">{{ $item->device_user_id ?: '-' }}</div>
                            @endif
                        </td>

                        <td class="px-5 py-4">
                            <div class="font-medium text-slate-900">{{ optional($item->log_datetime)->format('Y-m-d H:i:s') ?: '-' }}</div>
                            <div class="mt-1 text-xs text-slate-500">
                                {{ $item->log_date ?? '-' }} / {{ $item->log_time ?? '-' }}
                            </div>
                        </td>

                        <td class="px-5 py-4 text-slate-600">
                            <div>{{ $item->source_system ?: '-' }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ $item->verify_mode ?: '-' }}</div>
                        </td>

                        <td class="px-5 py-4 text-slate-600">
                            <div>{{ $item->device_id ?: '-' }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ $item->device_user_id ?: '-' }}</div>
                        </td>

                        <td class="px-5 py-4">
                            <x-ui.status-badge :label="$item->io_mode ?: 'UNKNOWN'" :tone="$ioTone" />
                        </td>

                        <td class="px-5 py-4 text-slate-600">
                            {{ $item->branch->branch_name ?? '-' }}
                        </td>

                        <td class="px-5 py-4">
                            @if($item->normalizedLog)
                                <x-ui.status-badge label="Linked" tone="success" />
                            @else
                                <x-ui.status-badge label="Pending" tone="warning" />
                            @endif
                        </td>                        

                        <td class="px-5 py-4">
                            <div class="flex justify-end gap-2">
                                <x-ui.button
                                    variant="ghost"
                                    onclick="window.location='{{ route('attendance.raw-logs.show', $item->log_id) }}'"
                                >
                                    Detail
                                </x-ui.button>

                                <x-ui.row-actions :actions="[
                                    [
                                        'label' => 'Open Normalized Logs',
                                        'url' => auth()->user()->hasPermission('attendance_normalized.view')
                                            ? route('attendance.normalized-logs.index', [
                                                'q' => $item->employee->emp_code ?? $item->device_user_id,
                                                'date_from' => optional($item->log_datetime)->format('Y-m-d'),
                                                'date_to' => optional($item->log_datetime)->format('Y-m-d'),
                                            ])
                                            : null,
                                        'visible' => auth()->user()->hasPermission('attendance_normalized.view'),
                                    ],
                                    [
                                        'label' => 'Open Daily Attendance',
                                        'url' => auth()->user()->hasPermission('attendance_daily.view')
                                            ? route('attendance.daily.index', [
                                                'q' => $item->employee->emp_code ?? $item->device_user_id,
                                                'date_from' => optional($item->log_datetime)->format('Y-m-d'),
                                                'date_to' => optional($item->log_datetime)->format('Y-m-d'),
                                            ])
                                            : null,
                                        'visible' => auth()->user()->hasPermission('attendance_daily.view'),
                                    ],
                                ]" />
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-5 py-14">
                            <x-ui.empty-state
                                title="No raw logs found"
                                description="Belum ada raw attendance logs sesuai filter."
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($tableRows && method_exists($tableRows, 'hasPages') && $tableRows->hasPages())
            <div class="border-t border-slate-200 bg-white px-5 py-4">
                {{ $tableRows->withQueryString()->links() }}
            </div>
        @endif
    </x-ui.table-shell>
</div>
@endsection