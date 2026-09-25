@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Normalized Log Detail"
        subtitle="Detail hasil normalisasi log dan hubungannya dengan raw source."
        :breadcrumbs="[
            ['label' => 'Attendance'],
            ['label' => 'Normalized Logs'],
            ['label' => 'Detail'],
        ]"
    >
        <x-slot:actions>
            <x-ui.button
                variant="ghost"
                onclick="window.location='{{ route('attendance.normalized-logs.index') }}'"
            >
                Back to List
            </x-ui.button>

            @if(auth()->user()->hasPermission('attendance_daily.view'))
                <x-ui.button
                    variant="ghost"
                    onclick="window.location='{{ $matchedDaily
                        ? route('attendance.daily.show', $matchedDaily->attendance_daily_id)
                        : route('attendance.daily.index', [
                            'q' => $row->employee->emp_code ?? null,
                            'date_from' => optional($row->log_datetime)->format('Y-m-d'),
                            'date_to' => optional($row->log_datetime)->format('Y-m-d'),
                        ]) }}'"
                >
                    Open Daily
                </x-ui.button>
            @endif

            @if(auth()->user()->hasPermission('attendance_raw.view') && $row->rawLog)
                <x-ui.button
                    variant="ghost"
                    onclick="window.location='{{ route('attendance.raw-logs.show', $row->rawLog->log_id) }}'"
                >
                    Open Raw Log
                </x-ui.button>
            @endif
            
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <x-ui.section-card title="Normalized Snapshot" subtitle="Ringkasan utama normalized log.">
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <div class="text-xs text-slate-500">Employee</div>
                        <div class="mt-1 font-medium text-slate-900">{{ $row->employee->full_name ?? '-' }}</div>
                        <div class="mt-1 text-xs text-slate-500">{{ $row->employee->emp_code ?? '-' }}</div>
                    </div>

                    <div>
                        <div class="text-xs text-slate-500">Log Datetime</div>
                        <div class="mt-1 font-medium text-slate-900">{{ optional($row->log_datetime)->format('Y-m-d H:i:s') ?: '-' }}</div>
                    </div>

                    <div>
                        <div class="text-xs text-slate-500">Derived Event Type</div>
                        <div class="mt-2">
                            @php
                                $eventTone = match($row->derived_event_type_code) {
                                    'IN' => 'success',
                                    'OUT' => 'info',
                                    'UNKNOWN' => 'warning',
                                    'DUPLICATE' => 'neutral',
                                    default => 'neutral',
                                };
                            @endphp
                            <x-ui.status-badge :label="$row->derived_event_type_code" :tone="$eventTone" />
                        </div>
                    </div>

                    <div>
                        <div class="text-xs text-slate-500">Normalized Status</div>
                        <div class="mt-2">
                            @php
                                $statusTone = match($row->normalized_status_code) {
                                    'VALID' => 'success',
                                    'SUSPICIOUS' => 'warning',
                                    'INVALID' => 'danger',
                                    'DUPLICATE' => 'neutral',
                                    'IGNORED' => 'neutral',
                                    default => 'neutral',
                                };
                            @endphp
                            <x-ui.status-badge :label="$row->normalized_status_code" :tone="$statusTone" />
                        </div>
                    </div>

                    <div>
                        <div class="text-xs text-slate-500">Duplicate Candidate</div>
                        <div class="mt-1 font-medium text-slate-900">{{ $row->is_duplicate_candidate ? 'Yes' : 'No' }}</div>
                    </div>

                    <div>
                        <div class="text-xs text-slate-500">Duplicate Group Key</div>
                        <div class="mt-1 font-medium text-slate-900">{{ $row->duplicate_group_key ?: '-' }}</div>
                    </div>

                    <div>
                        <div class="text-xs text-slate-500">Processed At</div>
                        <div class="mt-1 font-medium text-slate-900">{{ optional($row->processed_at)->format('Y-m-d H:i:s') ?: '-' }}</div>
                    </div>
                </div>
            </x-ui.section-card>

            <x-ui.section-card title="Normalized Notes" subtitle="Catatan hasil normalisasi.">
                <div class="text-sm whitespace-pre-line text-slate-800">{{ $row->notes ?: '-' }}</div>
            </x-ui.section-card>
        </div>

        <div class="space-y-6">
            <x-ui.section-card title="Raw Source" subtitle="Sumber raw log yang melahirkan normalized row ini.">
                <div class="space-y-4">
                    <div>
                        <div class="text-xs text-slate-500">Source System</div>
                        <div class="mt-1 font-medium text-slate-900">{{ optional($row->rawLog)->source_system ?? '-' }}</div>
                    </div>

                    <div>
                        <div class="text-xs text-slate-500">Device ID</div>
                        <div class="mt-1 font-medium text-slate-900">{{ $row->rawLog->device_id ?? '-' }}</div>
                    </div>

                    <div>
                        <div class="text-xs text-slate-500">Device User ID</div>
                        <div class="mt-1 font-medium text-slate-900">{{ $row->rawLog->device_user_id ?? '-' }}</div>
                    </div>

                    <div>
                        <div class="text-xs text-slate-500">Verify Mode</div>
                        <div class="mt-1 font-medium text-slate-900">{{ $row->rawLog->verify_mode ?? '-' }}</div>
                    </div>

                    <div>
                        <div class="text-xs text-slate-500">IO Mode</div>
                        <div class="mt-1 font-medium text-slate-900">{{ $row->rawLog->io_mode ?? '-' }}</div>
                    </div>

                    <div>
                        <div class="text-xs text-slate-500">Location Branch ID</div>
                        <div class="mt-1 font-medium text-slate-900">{{ $row->rawLog->location_branch_id ?? '-' }}</div>
                    </div>

                    <div>
                        <div class="text-xs text-slate-500">Branch</div>
                        <div class="mt-1 font-medium text-slate-900">{{ optional(optional($row->rawLog)->branch)->branch_name ?? '-' }}</div>
                    </div>

                    <div>
                        <div class="text-xs text-slate-500">Import Batch ID</div>
                        <div class="mt-1 font-medium text-slate-900">{{ optional($row->rawLog)->attendance_import_batch_id ?? '-' }}</div>
                    </div>

                    @if($row->rawLog && $row->rawLog->importBatch)
                        <div>
                            <div class="text-xs text-slate-500">Import File</div>
                            <div class="mt-1 font-medium text-slate-900">{{ $row->rawLog->importBatch->original_file_name }}</div>
                        </div>
                    @endif

                    <div>
                        <div class="text-xs text-slate-500">Daily Match</div>
                        <div class="mt-2">
                            @if($matchedDaily)
                                <x-ui.status-badge label="Linked to Daily" tone="success" />
                                <div class="mt-2 text-xs text-slate-500">
                                    Daily ID: {{ $matchedDaily->attendance_daily_id }}
                                </div>
                            @else
                                <x-ui.status-badge label="Daily Not Built" tone="warning" />
                                <div class="mt-2 text-xs text-slate-500">
                                    Belum ada attendance_daily untuk employee dan tanggal log ini.
                                </div>
                            @endif
                        </div>
                    </div>                    

                    <div>
                        <div class="text-xs text-slate-500">Raw Payload</div>
                        <pre class="mt-2 overflow-x-auto rounded-2xl border border-slate-200 bg-slate-50 p-4 text-xs text-slate-700">{{ json_encode(optional($row->rawLog)->raw_payload ?? null, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                </div>
            </x-ui.section-card>
        </div>
    </div>
</div>
@endsection