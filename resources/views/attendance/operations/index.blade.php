@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <x-ui.page-header
            title="Attendance Operations"
            subtitle="Panel kontrol operasional untuk normalize logs, build daily, dan menjalankan full pipeline dari UI."
            :breadcrumbs="[
                ['label' => 'Attendance'],
                ['label' => 'Operations'],
            ]"
        />

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

        <x-ui.section-card
            title="Execution Scope"
            subtitle="Gunakan salah satu mode: pilih payroll period, atau isi manual Date From dan Date To. Jika payroll period dipilih, date range akan otomatis mengikuti periode dan dikunci."
        >
            <form id="attendance-operations-scope" method="GET" action="{{ route('attendance.operations.index') }}" class="grid gap-4 md:grid-cols-4">
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Payroll Period</label>
                    <select
                        name="payroll_period_id"
                        id="payroll_period_id"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                    >
                        <option value="">-- Pilih Payroll Period --</option>
                        @foreach ($payrollPeriods as $period)
                            <option
                                value="{{ $period->payroll_period_id }}"
                                data-date-from="{{ $period->period_start_date->format('Y-m-d') }}"
                                data-date-to="{{ $period->period_end_date->format('Y-m-d') }}"
                                @selected((string) old('payroll_period_id', $selectedPayrollPeriodId) === (string) $period->payroll_period_id)
                            >
                                {{ $period->period_code }} — {{ $period->period_start_date->format('d M Y') }} s/d {{ $period->period_end_date->format('d M Y') }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Date From</label>
                    <input
                        type="date"
                        name="date_from"
                        id="date_from"
                        value="{{ old('date_from', $selectedDateFrom) }}"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                    >
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Date To</label>
                    <input
                        type="date"
                        name="date_to"
                        id="date_to"
                        value="{{ old('date_to', $selectedDateTo) }}"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                    >
                </div>

                <div class="flex items-end gap-3 md:justify-end">
                    <x-ui.button type="submit" variant="secondary">
                        Apply Scope
                    </x-ui.button>

                    <a
                        href="{{ route('attendance.operations.index') }}"
                        class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 shadow-sm transition hover:border-slate-300 hover:text-slate-900"
                    >
                        Reset
                    </a>
                </div>

                <p class="mt-2 text-xs text-slate-500">
                    Kosongkan payroll period jika ingin menjalankan proses dengan date range manual.
                </p>
            </form>
        </x-ui.section-card>

        <x-ui.section-card
            title="Pipeline Snapshot"
            subtitle="Ringkasan pipeline berdasarkan scope aktif, agar raw → normalized → daily → review bisa dipantau dari satu layar."
        >
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Raw Logs</div>
                    <div class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">
                        {{ number_format($pipelineStats['raw_count'] ?? 0) }}
                    </div>
                    <div class="mt-2 text-xs text-slate-500">
                        Pending normalize: {{ number_format($pipelineStats['pending_normalize_count'] ?? 0) }}
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Normalized Logs</div>
                    <div class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">
                        {{ number_format($pipelineStats['normalized_count'] ?? 0) }}
                    </div>
                    <div class="mt-2 text-xs text-slate-500">
                        Last processed: {{ optional($pipelineStats['last_normalized_at'] ?? null)->format('Y-m-d H:i') ?: '-' }}
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Daily Records</div>
                    <div class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">
                        {{ number_format($pipelineStats['daily_count'] ?? 0) }}
                    </div>
                    <div class="mt-2 text-xs text-slate-500">
                        Last calculated: {{ optional($pipelineStats['last_calculated_at'] ?? null)->format('Y-m-d H:i') ?: '-' }}
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Review Cases</div>
                    <div class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">
                        {{ number_format($pipelineStats['review_count'] ?? 0) }}
                    </div>
                    <div class="mt-2 text-xs text-slate-500">
                        Open / In Review: {{ number_format($pipelineStats['pending_review_count'] ?? 0) }}
                    </div>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <a
                    href="{{ route('attendance.raw-logs.index', ['date_from' => $selectedDateFrom, 'date_to' => $selectedDateTo]) }}"
                    class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 shadow-sm transition hover:border-slate-300 hover:text-slate-900"
                >
                    Open Raw Logs
                </a>

                <a
                    href="{{ route('attendance.normalized-logs.index', ['date_from' => $selectedDateFrom, 'date_to' => $selectedDateTo]) }}"
                    class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 shadow-sm transition hover:border-slate-300 hover:text-slate-900"
                >
                    Open Normalized Logs
                </a>

                <a
                    href="{{ route('attendance.daily.index', ['date_from' => $selectedDateFrom, 'date_to' => $selectedDateTo]) }}"
                    class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 shadow-sm transition hover:border-slate-300 hover:text-slate-900"
                >
                    Open Attendance Daily
                </a>

                @if (Route::has('review.attendance-cases.index'))
                    <a
                        href="{{ route('review.attendance-cases.index', ['date_from' => $selectedDateFrom, 'date_to' => $selectedDateTo]) }}"
                        class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 shadow-sm transition hover:border-slate-300 hover:text-slate-900"
                    >
                        Open Review Cases
                    </a>
                @endif
            </div>
        </x-ui.section-card>

        <div class="grid gap-6 xl:grid-cols-3">
            <x-ui.section-card
                title="Normalize Logs"
                subtitle="Mengubah raw logs menjadi normalized logs berdasarkan range yang dipilih."
            >
                <form method="POST" action="{{ route('attendance.operations.normalize') }}" class="space-y-4 js-operation-form">
                    @csrf
                    <input type="hidden" name="payroll_period_id" class="js-payroll-period-id">
                    <input type="hidden" name="date_from" class="js-date-from">
                    <input type="hidden" name="date_to" class="js-date-to">

                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                        Menjalankan proses normalize untuk raw logs yang belum masuk ke attendance_logs_normalized.
                    </div>

                    <div class="flex justify-end">
                        <x-ui.button type="submit" variant="primary">
                            Run Normalize
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.section-card>

            <x-ui.section-card
                title="Build Daily"
                subtitle="Membangun attendance_daily dari normalized logs sesuai range yang dipilih."
            >
                <form method="POST" action="{{ route('attendance.operations.build-daily') }}" class="space-y-4 js-operation-form">
                    @csrf
                    <input type="hidden" name="payroll_period_id" class="js-payroll-period-id">
                    <input type="hidden" name="date_from" class="js-date-from">
                    <input type="hidden" name="date_to" class="js-date-to">

                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                        Menjalankan build attendance harian untuk employee-day group pada range yang dipilih.
                    </div>

                    <div class="flex justify-end">
                        <x-ui.button type="submit" variant="primary">
                            Run Build Daily
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.section-card>

            <x-ui.section-card
                title="Full Pipeline"
                subtitle="Jalankan normalize lalu build daily secara berurutan dari UI."
            >
                <form method="POST" action="{{ route('attendance.operations.run-pipeline') }}" class="space-y-4 js-operation-form">
                    @csrf
                    <input type="hidden" name="payroll_period_id" class="js-payroll-period-id">
                    <input type="hidden" name="date_from" class="js-date-from">
                    <input type="hidden" name="date_to" class="js-date-to">

                    <div class="rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-700">
                        Cocok untuk workflow operasional utama: setelah import raw log, langsung normalize dan build daily.
                    </div>

                    <div class="flex justify-end">
                        <x-ui.button type="submit" variant="primary">
                            Run Full Pipeline
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.section-card>
        </div>

        <x-ui.section-card
            title="Operation History"
            subtitle="Histori eksekusi terbaru agar normalize / build daily / full pipeline lebih mudah diaudit."
        >
            @if ($operationRuns->isEmpty())
                <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
                    <div class="text-sm font-medium text-slate-700">Belum ada histori operasi</div>
                    <div class="mt-2 text-sm text-slate-500">
                        Jalankan normalize, build daily, atau full pipeline untuk mulai membangun jejak observability.
                    </div>
                </div>
            @else
                <div class="space-y-4">
                    @foreach ($operationRuns as $run)
                        <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <div class="text-sm font-semibold text-slate-900">
                                            {{ str_replace('_', ' ', $run->operation_type_code) }}
                                        </div>

                                        @php
                                            $statusTone = match ($run->operation_status_code) {
                                                'SUCCESS', 'SUCCEEDED', 'COMPLETED' => 'success',
                                                'FAILED', 'ERROR' => 'danger',
                                                'RUNNING', 'PROCESSING' => 'info',
                                                'PENDING', 'QUEUED' => 'warning',
                                                default => 'neutral',
                                            };
                                        @endphp

                                        <x-ui.status-badge
                                            :label="$run->operation_status_code"
                                            :tone="$statusTone"
                                        />
                                    </div>

                                    <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500">
                                        <div>
                                            Scope:
                                            {{ optional($run->date_from)->format('Y-m-d') ?: '-' }}
                                            →
                                            {{ optional($run->date_to)->format('Y-m-d') ?: '-' }}
                                        </div>
                                        <div>
                                            Period:
                                            {{ $run->payrollPeriod?->period_code ?? '-' }}
                                        </div>
                                        <div>
                                            Triggered by:
                                            {{ $run->triggeredByUser?->full_name ?? 'System / Unknown' }}
                                        </div>
                                        <div>
                                            Duration:
                                            @php
                                                if (!$run->started_at) {
                                                    $durationText = '-';
                                                } elseif (!$run->finished_at) {
                                                    $durationText = 'In progress';
                                                } else {
                                                    $seconds = $run->finished_at->diffInSeconds($run->started_at);

                                                    if ($seconds < 60) {
                                                        $durationText = $seconds . ' sec';
                                                    } else {
                                                        $minutes = intdiv($seconds, 60);
                                                        $remainingSeconds = $seconds % 60;

                                                        if ($minutes < 60) {
                                                            $durationText = $remainingSeconds > 0
                                                                ? sprintf('%d min %d sec', $minutes, $remainingSeconds)
                                                                : sprintf('%d min', $minutes);
                                                        } else {
                                                            $hours = intdiv($minutes, 60);
                                                            $remainingMinutes = $minutes % 60;

                                                            $durationText = $remainingMinutes > 0
                                                                ? sprintf('%d hr %d min', $hours, $remainingMinutes)
                                                                : sprintf('%d hr', $hours);
                                                        }
                                                    }
                                                }
                                            @endphp
                                            {{ $durationText }}
                                        </div>
                                    </div>
                                </div>

                                <div class="text-xs text-slate-500 lg:text-right">
                                    <div>Started: {{ optional($run->started_at)->format('Y-m-d H:i:s') ?: '-' }}</div>
                                    <div class="mt-1">Finished: {{ optional($run->finished_at)->format('Y-m-d H:i:s') ?: '-' }}</div>
                                </div>
                            </div>

                            @php
                                $resultJson = is_array($run->result_json) ? $run->result_json : [];
                                $summaryLines = [];

                                if ($run->operation_type_code === 'NORMALIZE') {
                                    $summaryLines = array_filter([
                                        'Groups: ' . number_format((int) ($resultJson['group_count'] ?? 0)),
                                        'Processed: ' . number_format((int) ($resultJson['processed_count'] ?? 0)),
                                        'Inserted: ' . number_format((int) ($resultJson['inserted_count'] ?? 0)),
                                    ]);
                                } elseif ($run->operation_type_code === 'BUILD_DAILY') {
                                    $summaryLines = array_filter([
                                        'Employees: ' . number_format((int) ($resultJson['employee_count'] ?? 0)),
                                        'Processed Days: ' . number_format((int) ($resultJson['processed_days'] ?? 0)),
                                        'Upserted: ' . number_format((int) ($resultJson['upserted_count'] ?? 0)),
                                        'Skipped Days: ' . number_format((int) ($resultJson['skipped_days'] ?? 0)),
                                    ]);
                                } elseif ($run->operation_type_code === 'FULL_PIPELINE') {
                                    $normalize = $resultJson['normalize'] ?? [];
                                    $buildDaily = $resultJson['build_daily'] ?? [];

                                    $summaryLines = array_filter([
                                        'Normalize → Groups: ' . number_format((int) ($normalize['group_count'] ?? 0)),
                                        'Normalize → Processed: ' . number_format((int) ($normalize['processed_count'] ?? 0)),
                                        'Normalize → Inserted: ' . number_format((int) ($normalize['inserted_count'] ?? 0)),
                                        'Build Daily → Employees: ' . number_format((int) ($buildDaily['employee_count'] ?? 0)),
                                        'Build Daily → Processed Days: ' . number_format((int) ($buildDaily['processed_days'] ?? 0)),
                                        'Build Daily → Upserted: ' . number_format((int) ($buildDaily['upserted_count'] ?? 0)),
                                        'Build Daily → Skipped Days: ' . number_format((int) ($buildDaily['skipped_days'] ?? 0)),
                                    ]);
                                } elseif (!empty($resultJson)) {
                                    foreach (collect($resultJson)->take(6) as $key => $value) {
                                        $summaryLines[] = is_array($value)
                                            ? sprintf('%s: [complex]', (string) $key)
                                            : sprintf('%s: %s', (string) $key, (string) $value);
                                    }
                                } else {
                                    $summaryLines = ['No structured result'];
                                }
                            @endphp

                            @if (!empty($summaryLines))
                                <div class="mt-4 grid gap-2 md:grid-cols-2 xl:grid-cols-3">
                                    @foreach ($summaryLines as $line)
                                        <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2 text-xs text-slate-600">
                                            {{ $line }}
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            @if ($run->error_message)
                                <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                                    <div class="font-medium">Error Message</div>
                                    <div class="mt-1 whitespace-pre-wrap break-words">{{ $run->error_message }}</div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </x-ui.section-card>

        <x-ui.section-card
            title="Catatan"
            subtitle="Versi v1 ini masih synchronous, tetapi histori eksekusi dan snapshot pipeline dasar sudah tersedia."
        >
            <div class="grid gap-3 text-sm text-slate-600">
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                    Normalize hanya memproses raw logs yang belum punya pasangan di <code>attendance_logs_normalized</code>.
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                    Build daily saat ini mengikuti behavior service yang sudah ada: row yang sudah ada di <code>attendance_daily</code> akan di-skip.
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                    Snapshot di atas membantu memantau apakah raw logs sudah ternormalisasi, sudah dibangun ke attendance_daily, dan apakah review case mulai menumpuk pada scope aktif.
                </div>
            </div>
        </x-ui.section-card>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const payrollSelect = document.getElementById('payroll_period_id');
            const dateFromInput = document.getElementById('date_from');
            const dateToInput = document.getElementById('date_to');
            const forms = document.querySelectorAll('.js-operation-form');

            function syncHiddenFields() {
                forms.forEach((form) => {
                    form.querySelector('.js-payroll-period-id').value = payrollSelect.value || '';
                    form.querySelector('.js-date-from').value = dateFromInput.value || '';
                    form.querySelector('.js-date-to').value = dateToInput.value || '';
                });
            }

            function applyPeriodSelectionState() {
                const selected = payrollSelect.options[payrollSelect.selectedIndex];
                const hasPeriod = !!payrollSelect.value;

                if (hasPeriod && selected?.dataset?.dateFrom && selected?.dataset?.dateTo) {
                    dateFromInput.value = selected.dataset.dateFrom;
                    dateToInput.value = selected.dataset.dateTo;

                    dateFromInput.readOnly = true;
                    dateToInput.readOnly = true;

                    dateFromInput.classList.add('bg-slate-100', 'cursor-not-allowed');
                    dateToInput.classList.add('bg-slate-100', 'cursor-not-allowed');
                } else {
                    dateFromInput.readOnly = false;
                    dateToInput.readOnly = false;

                    dateFromInput.classList.remove('bg-slate-100', 'cursor-not-allowed');
                    dateToInput.classList.remove('bg-slate-100', 'cursor-not-allowed');
                }

                syncHiddenFields();
            }

            function validateScopeBeforeSubmit() {
                const hasPeriod = !!payrollSelect.value;
                const hasDateFrom = !!dateFromInput.value;
                const hasDateTo = !!dateToInput.value;

                if (hasPeriod) {
                    return true;
                }

                if (!hasDateFrom || !hasDateTo) {
                    alert('Pilih payroll period atau isi Date From dan Date To terlebih dahulu.');
                    return false;
                }

                if (dateFromInput.value > dateToInput.value) {
                    alert('Date To tidak boleh lebih kecil dari Date From.');
                    return false;
                }

                return true;
            }

            payrollSelect.addEventListener('change', function () {
                if (!payrollSelect.value) {
                    applyPeriodSelectionState();
                    return;
                }

                applyPeriodSelectionState();
            });

            dateFromInput.addEventListener('change', syncHiddenFields);
            dateToInput.addEventListener('change', syncHiddenFields);

            forms.forEach((form) => {
                form.addEventListener('submit', function (event) {
                    syncHiddenFields();

                    if (!validateScopeBeforeSubmit()) {
                        event.preventDefault();
                    }
                });
            });

            applyPeriodSelectionState();
        });
    </script>
@endsection