@extends('layouts.app')

@section('content')
    @php
        $severityTone = match($row->severity_code) {
            'LOW' => 'neutral',
            'MEDIUM' => 'info',
            'HIGH' => 'warning',
            'CRITICAL' => 'danger',
            default => 'neutral',
        };

        $statusTone = match($row->review_status_code) {
            'OPEN' => 'info',
            'IN_REVIEW' => 'warning',
            'RESOLVED' => 'success',
            'REJECTED' => 'danger',
            'CLOSED' => 'neutral',
            default => 'neutral',
        };

        $resolutionTone = match($row->resolution_type_code) {
            'NO_ACTION' => 'neutral',
            'MANUAL_CORRECTION' => 'info',
            'APPROVED_OVERRIDE' => 'success',
            'REJECTED_CASE' => 'danger',
            'SYSTEM_ADJUSTMENT' => 'warning',
            default => 'neutral',
        };
    @endphp

    <div class="space-y-6">
        <x-ui.page-header
            title="Attendance Review Case Detail"
            subtitle="Halaman investigasi review case untuk melihat konteks attendance harian, normalized logs, dan raw logs dalam window investigasi case ini."
            :breadcrumbs="[
                ['label' => 'Review'],
                ['label' => 'Attendance Cases', 'url' => route('review.attendance-cases.index')],
                ['label' => 'Detail'],
            ]"
        />

        <div class="flex flex-wrap items-center gap-3">
            <a
                href="{{ route('review.attendance-cases.index') }}"
                class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 shadow-sm transition hover:border-slate-300 hover:text-slate-900"
            >
                Back to List
            </a>

            @if(auth()->user()->hasPermission('attendance_daily.view'))
                <a
                    href="{{ route('attendance.daily.index', [
                        'q' => $row->employee->emp_code ?? null,
                        'date_from' => optional($row->work_date)->format('Y-m-d'),
                        'date_to' => optional($row->work_date)->format('Y-m-d'),
                    ]) }}"
                    class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 shadow-sm transition hover:border-slate-300 hover:text-slate-900"
                >
                    Open Daily
                </a>
            @endif

            @if(auth()->user()->hasPermission('attendance_normalized.view'))
                <a
                    href="{{ route('attendance.normalized-logs.index', [
                        'q' => $row->employee->emp_code ?? null,
                        'date_from' => optional($windowStart)->format('Y-m-d'),
                        'date_to' => optional($windowEnd)->format('Y-m-d'),
                    ]) }}"
                    class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 shadow-sm transition hover:border-slate-300 hover:text-slate-900"
                >
                    Open Normalized
                </a>
            @endif

            @if(auth()->user()->hasPermission('attendance_raw.view'))
                <a
                    href="{{ route('attendance.raw-logs.index', [
                        'q' => $row->employee->emp_code ?? null,
                        'date_from' => optional($windowStart)->format('Y-m-d'),
                        'date_to' => optional($windowEnd)->format('Y-m-d'),
                    ]) }}"
                    class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 shadow-sm transition hover:border-slate-300 hover:text-slate-900"
                >
                    Open Raw Logs
                </a>
            @endif
        </div>

        @if (session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <div class="font-semibold">Form belum lengkap.</div>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid gap-6 xl:grid-cols-3">
            <div class="xl:col-span-2 space-y-6">
                <x-ui.section-card
                    title="Case Snapshot"
                    subtitle="Ringkasan utama case yang sedang direview."
                >
                    <div class="grid gap-6 md:grid-cols-2">
                        <div class="space-y-4">
                            <div>
                                <div class="text-xs text-slate-500">Employee</div>
                                <div class="mt-1 text-lg font-semibold text-slate-900">
                                    {{ $row->employee->full_name ?? '-' }}
                                </div>
                                <div class="mt-1 text-sm text-slate-500">
                                    {{ $row->employee->emp_code ?? '-' }}
                                </div>
                            </div>

                            <div>
                                <div class="text-xs text-slate-500">Work Date</div>
                                <div class="mt-1 font-medium text-slate-900">
                                    {{ optional($row->work_date)->format('Y-m-d') ?: '-' }}
                                </div>
                            </div>

                            <div>
                                <div class="text-xs text-slate-500">Detected At</div>
                                <div class="mt-1 font-medium text-slate-900">
                                    {{ optional($row->detected_at)->format('Y-m-d H:i:s') ?: '-' }}
                                </div>
                            </div>

                            <div>
                                <div class="text-xs text-slate-500">Case Type</div>
                                <div class="mt-1 font-medium text-slate-900">
                                    {{ $row->case_type_code }}
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <div class="text-xs text-slate-500">Severity</div>
                                <div class="mt-2">
                                    <x-ui.status-badge :label="$row->severity_code" :tone="$severityTone" />
                                </div>
                            </div>

                            <div>
                                <div class="text-xs text-slate-500">Review Status</div>
                                <div class="mt-2">
                                    <x-ui.status-badge :label="$row->review_status_code" :tone="$statusTone" />
                                </div>
                            </div>

                            <div>
                                <div class="text-xs text-slate-500">Resolution</div>
                                <div class="mt-2">
                                    @if($row->resolution_type_code)
                                        <x-ui.status-badge :label="$row->resolution_type_code" :tone="$resolutionTone" />
                                    @else
                                        <span class="text-sm text-slate-500">-</span>
                                    @endif
                                </div>
                            </div>

                            <div>
                                <div class="text-xs text-slate-500">Resolved By</div>
                                <div class="mt-1 font-medium text-slate-900">
                                    {{ $row->resolver->full_name ?? '-' }}
                                </div>
                            </div>

                            <div>
                                <div class="text-xs text-slate-500">Resolved At</div>
                                <div class="mt-1 font-medium text-slate-900">
                                    {{ optional($row->resolved_at)->format('Y-m-d H:i:s') ?: '-' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <div class="text-xs text-slate-500">Case Notes</div>
                        <div class="mt-2 whitespace-normal break-words text-sm leading-6 text-slate-700">
                            {{ $row->notes ?: '-' }}
                        </div>
                    </div>
                </x-ui.section-card>

                <x-ui.section-card
                    title="Attendance Daily Context"
                    subtitle="Konteks hasil attendance_daily yang menjadi referensi utama review case ini."
                >
                    @if ($daily)
                        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                            <div>
                                <div class="text-xs text-slate-500">Branch</div>
                                <div class="mt-1 font-medium text-slate-900">
                                    {{ $daily->branch->branch_name ?? '-' }}
                                </div>
                            </div>

                            <div>
                                <div class="text-xs text-slate-500">Shift</div>
                                <div class="mt-1 font-medium text-slate-900">
                                    {{ $daily->shift->shift_name ?? $daily->shift->shift_code ?? '-' }}
                                </div>
                            </div>

                            <div>
                                <div class="text-xs text-slate-500">Policy</div>
                                <div class="mt-1 font-medium text-slate-900">
                                    {{ $daily->policy->policy_name ?? $daily->policy->policy_code ?? '-' }}
                                </div>
                            </div>

                            <div>
                                <div class="text-xs text-slate-500">Attendance Status</div>
                                <div class="mt-1 font-medium text-slate-900">
                                    {{ $daily->attendance_status_code ?? '-' }}
                                </div>
                            </div>

                            <div>
                                <div class="text-xs text-slate-500">Presence Type</div>
                                <div class="mt-1 font-medium text-slate-900">
                                    {{ $daily->presence_type_code ?? '-' }}
                                </div>
                            </div>

                            <div>
                                <div class="text-xs text-slate-500">Review Reason</div>
                                <div class="mt-1 font-medium text-slate-900">
                                    {{ $daily->review_reason_code ?? '-' }}
                                </div>
                            </div>

                            <div>
                                <div class="text-xs text-slate-500">Scheduled In</div>
                                <div class="mt-1 font-medium text-slate-900">
                                    {{ optional($daily->scheduled_in_datetime)->format('Y-m-d H:i:s') ?: '-' }}
                                </div>
                            </div>

                            <div>
                                <div class="text-xs text-slate-500">Scheduled Out</div>
                                <div class="mt-1 font-medium text-slate-900">
                                    {{ optional($daily->scheduled_out_datetime)->format('Y-m-d H:i:s') ?: '-' }}
                                </div>
                            </div>

                            <div>
                                <div class="text-xs text-slate-500">Actual In</div>
                                <div class="mt-1 font-medium text-slate-900">
                                    {{ optional($daily->actual_in_datetime)->format('Y-m-d H:i:s') ?: '-' }}
                                </div>
                            </div>

                            <div>
                                <div class="text-xs text-slate-500">Actual Out</div>
                                <div class="mt-1 font-medium text-slate-900">
                                    {{ optional($daily->actual_out_datetime)->format('Y-m-d H:i:s') ?: '-' }}
                                </div>
                            </div>

                            <div>
                                <div class="text-xs text-slate-500">Work Min</div>
                                <div class="mt-1 font-medium text-slate-900">
                                    {{ number_format((int) ($daily->work_min ?? 0)) }}
                                </div>
                            </div>

                            <div>
                                <div class="text-xs text-slate-500">Late Min</div>
                                <div class="mt-1 font-medium text-slate-900">
                                    {{ number_format((int) ($daily->late_min ?? 0)) }}
                                </div>
                            </div>

                            <div>
                                <div class="text-xs text-slate-500">Early Out Min</div>
                                <div class="mt-1 font-medium text-slate-900">
                                    {{ number_format((int) ($daily->early_out_min ?? 0)) }}
                                </div>
                            </div>

                            <div>
                                <div class="text-xs text-slate-500">Overtime Min</div>
                                <div class="mt-1 font-medium text-slate-900">
                                    {{ number_format((int) ($daily->overtime_min ?? 0)) }}
                                </div>
                            </div>
                        </div>

                        @if (!empty($daily->notes))
                            <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                                <div class="text-xs text-slate-500">Daily Notes</div>
                                <div class="mt-2 whitespace-normal break-words text-sm leading-6 text-slate-700">
                                    {{ $daily->notes }}
                                </div>
                            </div>
                        @endif
                    @else
                        <x-ui.empty-state
                            title="Attendance Daily belum tersedia"
                            description="Belum ada row attendance_daily untuk employee dan tanggal review case ini."
                        />
                    @endif
                </x-ui.section-card>

                <x-ui.section-card
                    title="Normalized Logs"
                    subtitle="Log hasil normalisasi pada employee dalam window investigasi case ini."
                >
                    @if ($normalizedLogs->count())
                        <div class="overflow-hidden rounded-2xl border border-slate-200">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-slate-200 bg-white">
                                    <thead class="bg-slate-50/80">
                                        <tr>
                                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Datetime</th>
                                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Event</th>
                                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Duplicate</th>
                                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach ($normalizedLogs as $log)
                                            @php
                                                $normalizedTone = match($log->normalized_status_code) {
                                                    'VALID' => 'success',
                                                    'SUSPICIOUS' => 'warning',
                                                    'DUPLICATE' => 'neutral',
                                                    'INVALID' => 'danger',
                                                    'IGNORED' => 'neutral',
                                                    default => 'neutral',
                                                };
                                            @endphp

                                            <tr class="hover:bg-slate-50/70">
                                                <td class="px-5 py-4 text-sm text-slate-700">
                                                    {{ \Illuminate\Support\Carbon::parse($log->log_datetime)->format('Y-m-d H:i:s') }}
                                                </td>
                                                <td class="px-5 py-4 text-sm text-slate-700">
                                                    {{ $log->derived_event_type_code ?? '-' }}
                                                </td>
                                                <td class="px-5 py-4">
                                                    <x-ui.status-badge :label="$log->normalized_status_code ?? '-'" :tone="$normalizedTone" />
                                                </td>
                                                <td class="px-5 py-4 text-sm text-slate-700">
                                                    {{ $log->is_duplicate_candidate ? 'Yes' : 'No' }}
                                                </td>
                                                <td class="px-5 py-4 text-sm text-slate-600">
                                                    <div class="max-w-md whitespace-normal break-words line-clamp-3" title="{{ $log->notes }}">
                                                        {{ $log->notes ?: '-' }}
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @else
                        <x-ui.empty-state
                            title="Normalized logs tidak ditemukan"
                            description="Belum ada attendance_logs_normalized pada employee dan tanggal review case ini."
                        />
                    @endif
                </x-ui.section-card>

                <x-ui.section-card
                    title="Raw Logs"
                    subtitle="Log mentah attendance pada employee dalam window investigasi case ini."
                >
                    @if ($rawLogs->count())
                        <div class="overflow-hidden rounded-2xl border border-slate-200">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-slate-200 bg-white">
                                    <thead class="bg-slate-50/80">
                                        <tr>
                                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Datetime</th>
                                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Source</th>
                                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Device</th>
                                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Device User</th>
                                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Mode</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach ($rawLogs as $log)
                                            <tr class="hover:bg-slate-50/70">
                                                <td class="px-5 py-4 text-sm text-slate-700">
                                                    {{ \Illuminate\Support\Carbon::parse($log->log_datetime)->format('Y-m-d H:i:s') }}
                                                </td>
                                                <td class="px-5 py-4 text-sm text-slate-700">
                                                    {{ $log->source_system ?? '-' }}
                                                </td>
                                                <td class="px-5 py-4 text-sm text-slate-700">
                                                    {{ $log->device_id ?? '-' }}
                                                </td>
                                                <td class="px-5 py-4 text-sm text-slate-700">
                                                    {{ $log->device_user_id ?? '-' }}
                                                </td>
                                                <td class="px-5 py-4 text-sm text-slate-700">
                                                    {{ $log->io_mode ?? '-' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @else
                        <x-ui.empty-state
                            title="Raw logs tidak ditemukan"
                            description="Belum ada attendance_logs_raw pada employee dan tanggal review case ini."
                        />
                    @endif
                </x-ui.section-card>

                <x-ui.section-card
                    title="Related Cases"
                    subtitle="Case review lain untuk employee dalam window investigasi yang sama."
                >
                    @if ($relatedCases->count())
                        <div class="space-y-3">
                            @foreach ($relatedCases as $case)
                                @php
                                    $relatedSeverityTone = match($case->severity_code) {
                                        'LOW' => 'neutral',
                                        'MEDIUM' => 'info',
                                        'HIGH' => 'warning',
                                        'CRITICAL' => 'danger',
                                        default => 'neutral',
                                    };

                                    $relatedStatusTone = match($case->review_status_code) {
                                        'OPEN' => 'info',
                                        'IN_REVIEW' => 'warning',
                                        'RESOLVED' => 'success',
                                        'REJECTED' => 'danger',
                                        'CLOSED' => 'neutral',
                                        default => 'neutral',
                                    };
                                @endphp

                                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm">
                                    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <div class="text-sm font-semibold text-slate-900">
                                                    {{ $case->case_type_code }}
                                                </div>
                                                <x-ui.status-badge :label="$case->severity_code" :tone="$relatedSeverityTone" />
                                                <x-ui.status-badge :label="$case->review_status_code" :tone="$relatedStatusTone" />
                                            </div>

                                            <div class="mt-2 max-w-3xl whitespace-normal break-words text-sm text-slate-600">
                                                {{ $case->notes ?: '-' }}
                                            </div>
                                        </div>

                                        <a
                                            href="{{ route('review.attendance-cases.show', $case->review_case_id) }}"
                                            class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-600 shadow-sm transition hover:border-slate-300 hover:text-slate-900"
                                        >
                                            Open
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <x-ui.empty-state
                            title="Tidak ada related cases"
                            description="Tidak ada review case lain pada employee dan tanggal yang sama."
                        />
                    @endif
                </x-ui.section-card>
            </div>

            <div class="space-y-6">
                <x-ui.section-card
                    title="Investigation Window"
                    subtitle="Window log yang dipakai untuk membaca konteks case ini."
                >
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Window</div>
                        <div class="mt-1 text-sm font-medium text-slate-900">
                            {{ optional($windowStart)->format('Y-m-d H:i:s') }} → {{ optional($windowEnd)->format('Y-m-d H:i:s') }}
                        </div>
                    </div>
                </x-ui.section-card>
                <x-ui.section-card
                    title="Review Action"
                    subtitle="Perbarui status review, resolution type, dan catatan investigasi."
                >
                    <form
                        method="POST"
                        action="{{ route('review.attendance-cases.update', $row->review_case_id) }}"
                        class="space-y-4"
                    >
                        @csrf
                        @method('PUT')

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Review Status</label>
                            <select
                                name="review_status_code"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                            >
                                @foreach ($reviewStatuses as $status)
                                    <option
                                        value="{{ $status }}"
                                        @selected(old('review_status_code', $row->review_status_code) === $status)
                                    >
                                        {{ $status }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Resolution Type</label>
                            <select
                                name="resolution_type_code"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                            >
                                <option value="">-- Select Resolution --</option>
                                @foreach ($resolutionTypes as $resolutionType)
                                    <option
                                        value="{{ $resolutionType }}"
                                        @selected(old('resolution_type_code', $row->resolution_type_code) === $resolutionType)
                                    >
                                        {{ $resolutionType }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Notes</label>
                            <textarea
                                name="notes"
                                rows="6"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                                placeholder="Tuliskan hasil investigasi, keputusan, atau alasan review..."
                            >{{ old('notes', $row->notes) }}</textarea>
                        </div>

                        <div class="flex justify-end">
                            <x-ui.button type="submit" variant="primary">
                                Save Review Update
                            </x-ui.button>
                        </div>
                    </form>
                </x-ui.section-card>

                <x-ui.section-card
                    title="Quick Reading Guide"
                    subtitle="Ringkasan singkat untuk mempercepat investigasi."
                >
                    <div class="space-y-3 text-sm text-slate-600">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                            Lihat dulu apakah attendance_daily untuk tanggal ini sudah terbentuk. Jika belum ada, biasanya investigasi perlu dimulai dari normalized/raw logs.
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                            Periksa urutan normalized logs untuk memastikan event IN/OUT, suspicious, atau duplicate sudah sesuai.
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                            Gunakan raw logs sebagai sumber paling bawah untuk memastikan jam, device, dan source system sebelum memutuskan resolution.
                        </div>
                    </div>
                </x-ui.section-card>
            </div>
        </div>
    </div>
@endsection