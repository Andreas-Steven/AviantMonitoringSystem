@extends('layouts.app')

@section('content')
<div class="space-y-5">

    {{-- Header --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">
                    Attendance Operations
                </h1>
                <p class="mt-1 text-sm text-gray-500">
                    Pantau import, processing, verifikasi harian, dan koreksi absensi.
                </p>
            </div>

            <a href="{{ route('attendance.daily.index', [
                    'work_date' => $todayOverview['work_date'] ?? now()->toDateString(),
                    'anomaly' => 1,
                ]) }}"
            class="inline-flex w-fit items-center rounded-xl bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">
                Open
            </a>
        </div>
    </div>

    {{-- Quick Alert --}}
    @if(($todayOverview['anomaly_count'] ?? 0) > 0 || ($todayOverview['incomplete_count'] ?? 0) > 0)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-700">
            <div class="font-medium">
                Attendance data needs verification
            </div>
            <div class="mt-1 text-amber-600">
                Ada {{ $todayOverview['anomaly_count'] ?? 0 }} anomaly dan
                {{ $todayOverview['incomplete_count'] ?? 0 }} incomplete attendance hari ini.
            </div>
        </div>
    @else
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">
            <div class="font-medium">
                Attendance looks clean today
            </div>
            <div class="mt-1 text-emerald-600">
                Tidak ada anomaly atau incomplete attendance yang terdeteksi hari ini.
            </div>
        </div>
    @endif

    {{-- Flow --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-sm font-semibold text-gray-900">
                    Recommended Attendance Flow
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Import log → process data → verify daily result.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2 text-sm">
                <span class="rounded-lg bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">1 Import</span>
                <span class="text-gray-300">→</span>
                <span class="rounded-lg bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700">2 Process</span>
                <span class="text-gray-300">→</span>
                <span class="rounded-lg bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700">3 Verify</span>
            </div>
        </div>
    </div>

    {{-- Today Overview --}}
    <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
        <div class="mb-3 flex items-center justify-between gap-3">
            <div>
                <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                    Today Overview
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Snapshot cepat kondisi absensi hari ini.
                </p>
            </div>

            <a href="{{ route('attendance.daily.index') }}"
               class="hidden rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 sm:inline-flex">
                View Daily
            </a>
        </div>

        <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
            <a href="{{ route('attendance.daily.index', ['work_date' => $todayOverview['work_date'] ?? now()->toDateString()]) }}"
            class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 hover:bg-gray-100">
                <div class="text-xl font-semibold text-gray-900">
                    {{ $todayOverview['employee_count'] ?? 0 }}
                </div>
                <div class="text-xs text-gray-500">
                    Employee
                </div>
            </a>

            <a href="{{ route('attendance.daily.index', [
                'work_date' => $todayOverview['work_date'] ?? now()->toDateString(),
                'attendance_status_code' => 'PRESENT',
            ]) }}"
            class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 hover:bg-emerald-100">
                <div class="text-xl font-semibold text-emerald-700">
                    {{ $todayOverview['present_count'] ?? 0 }}
                </div>
                <div class="text-xs text-emerald-700">
                    Present
                </div>
            </a>

            <a href="{{ route('attendance.daily.index', [
                'work_date' => $todayOverview['work_date'] ?? now()->toDateString(),
                'late' => 1,
            ]) }}"
            class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 hover:bg-amber-100">
                <div class="text-xl font-semibold text-amber-700">
                    {{ $todayOverview['late_count'] ?? 0 }}
                </div>
                <div class="text-xs text-amber-700">
                    Late
                </div>
            </a>

            <a href="{{ route('attendance.daily.index', [
                'work_date' => $todayOverview['work_date'] ?? now()->toDateString(),
                'attendance_status_code' => 'INCOMPLETE',
            ]) }}"
            class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 hover:bg-red-100">
                <div class="text-xl font-semibold text-red-700">
                    {{ $todayOverview['incomplete_count'] ?? 0 }}
                </div>
                <div class="text-xs text-red-700">
                    Incomplete
                </div>
            </a>
        </div>
    </section>

    {{-- Empty State / Guidance --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-4 text-sm text-gray-600 shadow-sm">
        Jika data hari ini belum muncul, mulai dari <span class="font-medium text-gray-900">Import Attendance Logs</span>,
        lalu jalankan processing sebelum membuka Daily Verification.
    </div>

    {{-- Main Work Area --}}
    <section class="grid grid-cols-1 gap-4 lg:grid-cols-3">

        {{-- Daily Verification --}}
        <div class="rounded-2xl border-2 border-blue-200 bg-white p-5 shadow-sm lg:col-span-2">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="space-y-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="text-base font-semibold text-gray-900">
                            Daily Verification
                        </h3>
                        <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">
                            Main workspace
                        </span>
                    </div>

                    <p class="text-sm text-gray-500">
                        Verifikasi hasil absensi harian sebelum masuk summary dan payroll.
                    </p>

                    <p class="text-xs font-medium text-blue-700">
                        Titik utama validasi operasional harian.
                    </p>
                </div>

                <a href="{{ route('attendance.daily.index') }}"
                   class="inline-flex w-fit items-center rounded-xl bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">
                    Open
                </a>
            </div>
        </div>

        {{-- Exceptions --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="space-y-1">
                    <h3 class="text-base font-semibold text-gray-900">
                        Exceptions
                    </h3>

                    <p class="text-sm text-gray-500">
                        Koreksi manual dan override absensi.
                    </p>
                </div>

                <a href="{{ route('attendance.attendance-exceptions.index') }}"
                   class="inline-flex items-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Manage
                </a>
            </div>
        </div>
    </section>

    {{-- Import & Processing --}}
    <section class="space-y-3">
        <div>
            <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                Import & Processing
            </h2>
            <p class="text-sm text-gray-500">
                Masukkan data absensi dan jalankan pipeline processing.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">

            {{-- Import --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="space-y-3">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">
                            Import Logs
                        </h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Upload data absensi dari mesin.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('attendance.raw-logs.import.create') }}"
                        class="inline-flex items-center rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-800 hover:bg-gray-50">
                            Import File
                        </a>

                        <a href="{{ route('attendance.raw-logs.index') }}"
                        class="inline-flex items-center rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-800 hover:bg-gray-50">
                            View Raw
                        </a>
                    </div>
                </div>
            </div>

            {{-- Processing --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="space-y-3">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">
                            Processing
                        </h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Normalisasi log, build daily, dan jalankan full pipeline.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('attendance.operations.index') }}"
                        class="inline-flex items-center rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-800 hover:bg-gray-50">
                            Open Operations
                        </a>

                        <a href="{{ route('attendance.normalized-logs.index') }}"
                        class="inline-flex items-center rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-800 hover:bg-gray-50">
                            View Normalized
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

</div>
@endsection