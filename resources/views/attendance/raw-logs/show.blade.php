@extends('layouts.app')

@section('content')
@php
    $item = $row ?? $rawLog ?? null;
@endphp

<div class="space-y-6">
    <x-ui.page-header
        title="Raw Attendance Log Detail"
        subtitle="Detail log mentah sebelum proses normalisasi, pairing, dan kalkulasi attendance harian."
        :breadcrumbs="[
            ['label' => 'Attendance'],
            ['label' => 'Raw Attendance Logs'],
            ['label' => 'Detail'],
        ]"
    >
        <x-slot:actions>
            <x-ui.button
                variant="ghost"
                onclick="window.location='{{ route('attendance.raw-logs.index') }}'"
            >
                Back to List
            </x-ui.button>

            @if($item && auth()->user()->hasPermission('attendance_normalized.view'))
                <x-ui.button
                    variant="ghost"
                    onclick="window.location='{{ route('attendance.normalized-logs.index', [
                        'q' => $item->employee->emp_code ?? $item->device_user_id,
                        'date_from' => optional($item->log_datetime)->format('Y-m-d'),
                        'date_to' => optional($item->log_datetime)->format('Y-m-d'),
                    ]) }}'"
                >
                    Open Normalized
                </x-ui.button>
            @endif

            @if($item && auth()->user()->hasPermission('attendance_daily.view'))
                <x-ui.button
                    variant="ghost"
                    onclick="window.location='{{ route('attendance.daily.index', [
                        'q' => $item->employee->emp_code ?? $item->device_user_id,
                        'date_from' => optional($item->log_datetime)->format('Y-m-d'),
                        'date_to' => optional($item->log_datetime)->format('Y-m-d'),
                    ]) }}'"
                >
                    Open Daily
                </x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    @if($item)
        <div class="grid gap-6 xl:grid-cols-3">
            <div class="space-y-6 xl:col-span-2">
                <x-ui.section-card title="Raw Log Snapshot" subtitle="Informasi utama dari log mentah.">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <div class="text-xs text-slate-500">Employee</div>
                            @if($item->employee)
                                <div class="mt-1 font-medium text-slate-900">{{ $item->employee->full_name }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $item->employee->emp_code }}</div>
                            @else
                                <div class="mt-1 font-medium text-slate-500">Unmapped Employee</div>
                                <div class="mt-1 text-xs text-slate-400">{{ $item->device_user_id ?: '-' }}</div>
                            @endif
                        </div>

                        <div>
                            <div class="text-xs text-slate-500">Source System</div>
                            <div class="mt-1 font-medium text-slate-900">{{ $item->source_system ?: '-' }}</div>
                        </div>

                        <div>
                            <div class="text-xs text-slate-500">Log Datetime</div>
                            <div class="mt-1 font-medium text-slate-900">{{ optional($item->log_datetime)->format('Y-m-d H:i:s') ?: '-' }}</div>
                        </div>

                        <div>
                            <div class="text-xs text-slate-500">Log Date / Time</div>
                            <div class="mt-1 font-medium text-slate-900">
                                {{ $item->log_date ?? '-' }} / {{ $item->log_time ?? '-' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-xs text-slate-500">Device ID</div>
                            <div class="mt-1 font-medium text-slate-900">{{ $item->device_id ?: '-' }}</div>
                        </div>

                        <div>
                            <div class="text-xs text-slate-500">Device User ID</div>
                            <div class="mt-1 font-medium text-slate-900">{{ $item->device_user_id ?: '-' }}</div>
                        </div>

                        <div>
                            <div class="text-xs text-slate-500">Verify Mode</div>
                            <div class="mt-1 font-medium text-slate-900">{{ $item->verify_mode ?: '-' }}</div>
                        </div>

                        <div>
                            <div class="text-xs text-slate-500">IO Mode</div>
                            <div class="mt-2">
                                @php
                                    $ioTone = match($item->io_mode) {
                                        'IN' => 'success',
                                        'OUT' => 'info',
                                        default => 'neutral',
                                    };
                                @endphp
                                <x-ui.status-badge :label="$item->io_mode ?: 'UNKNOWN'" :tone="$ioTone" />
                            </div>
                        </div>
                    </div>
                </x-ui.section-card>

                <x-ui.section-card title="Raw Payload" subtitle="Payload mentah yang tersimpan pada row ini.">
                    <pre class="overflow-x-auto rounded-2xl border border-slate-200 bg-slate-50 p-4 text-xs text-slate-700">{{ json_encode($item->raw_payload ?? null, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </x-ui.section-card>
            </div>

            <div class="space-y-6">
                <x-ui.section-card title="Location & Ingestion" subtitle="Informasi branch dan waktu ingestion.">
                    <div class="space-y-4">
                        <div>
                            <div class="text-xs text-slate-500">Branch</div>
                            <div class="mt-1 font-medium text-slate-900">{{ $item->branch->branch_name ?? '-' }}</div>
                        </div>

                        <div>
                            <div class="text-xs text-slate-500">Location Branch ID</div>
                            <div class="mt-1 font-medium text-slate-900">{{ $item->location_branch_id ?: '-' }}</div>
                        </div>

                        <div>
                            <div class="text-xs text-slate-500">Ingested At</div>
                            <div class="mt-1 font-medium text-slate-900">{{ optional($item->ingested_at)->format('Y-m-d H:i:s') ?: '-' }}</div>
                        </div>

                        @if(isset($item->attendance_import_batch_id))
                            <div>
                                <div class="text-xs text-slate-500">Import Batch ID</div>
                                <div class="mt-1 font-medium text-slate-900">{{ $item->attendance_import_batch_id ?: '-' }}</div>
                            </div>
                        @endif

                        @if(isset($item->source_row_number))
                            <div>
                                <div class="text-xs text-slate-500">Source Row Number</div>
                                <div class="mt-1 font-medium text-slate-900">{{ $item->source_row_number ?: '-' }}</div>
                            </div>
                        @endif

                        @if(isset($item->import_validation_status_code))
                            <div>
                                <div class="text-xs text-slate-500">Import Validation Status</div>
                                <div class="mt-1 font-medium text-slate-900">{{ $item->import_validation_status_code ?: '-' }}</div>
                            </div>
                        @endif

                        @if(isset($item->import_validation_notes))
                            <div>
                                <div class="text-xs text-slate-500">Import Validation Notes</div>
                                <div class="mt-1 text-sm text-slate-800">{{ $item->import_validation_notes ?: '-' }}</div>
                            </div>
                        @endif

                        <div>
                            <div class="text-xs text-slate-500">Normalized Link</div>
                            <div class="mt-2">
                                @if($item->normalizedLog)
                                    <x-ui.status-badge label="Already Normalized" tone="success" />
                                @else
                                    <x-ui.status-badge label="Not Normalized Yet" tone="warning" />
                                @endif
                            </div>
                        </div>
                    </div>
                </x-ui.section-card>
            </div>

            @if($item->importBatch)
                <div>
                    <div class="text-xs text-slate-500">Import Batch File</div>
                    <div class="mt-1 font-medium text-slate-900">{{ $item->importBatch->original_file_name }}</div>
                </div>
            @endif
        </div>
    @else
        <x-ui.empty-state
            title="Raw log not found"
            description="Data raw log tidak tersedia untuk ditampilkan."
        />
    @endif
</div>
@endsection