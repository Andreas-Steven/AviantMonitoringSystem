@extends('layouts.app')

@section('content')
<div class="space-y-5">

    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <h1 class="text-2xl font-semibold text-gray-900">
            Attendance Tools
        </h1>
        <p class="mt-1 text-sm text-gray-500">
            Tools teknis untuk import log, normalisasi, operasi attendance, daily result, dan exception.
        </p>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <a href="{{ route('attendance.raw-logs.import.create') }}" class="rounded-2xl border border-blue-200 bg-blue-50 p-5 hover:bg-blue-100">
            <h3 class="text-base font-semibold text-blue-900">Import Raw Logs</h3>
            <p class="mt-1 text-sm text-blue-700">Upload data absensi dari mesin fingerprint/file.</p>
        </a>

        <a href="{{ route('attendance.raw-logs.index') }}" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm hover:bg-gray-50">
            <h3 class="text-base font-semibold text-gray-900">Raw Attendance Logs</h3>
            <p class="mt-1 text-sm text-gray-500">Lihat data mentah hasil import.</p>
        </a>

        <a href="{{ route('attendance.normalized-logs.index') }}" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm hover:bg-gray-50">
            <h3 class="text-base font-semibold text-gray-900">Normalized Logs</h3>
            <p class="mt-1 text-sm text-gray-500">Lihat log yang sudah dinormalisasi.</p>
        </a>

        <a href="{{ route('attendance.operations.index') }}" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm hover:bg-gray-50">
            <h3 class="text-base font-semibold text-gray-900">Attendance Operations</h3>
            <p class="mt-1 text-sm text-gray-500">Jalankan normalisasi dan build attendance daily.</p>
        </a>

        <a href="{{ route('attendance.daily.index') }}" class="rounded-2xl border border-blue-200 bg-blue-50 p-5 hover:bg-blue-100">
            <h3 class="text-base font-semibold text-blue-900">Attendance Daily</h3>
            <p class="mt-1 text-sm text-blue-700">Verifikasi hasil absensi harian.</p>
        </a>

        <a href="{{ route('attendance.attendance-exceptions.index') }}" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm hover:bg-gray-50">
            <h3 class="text-base font-semibold text-gray-900">Attendance Exceptions</h3>
            <p class="mt-1 text-sm text-gray-500">Kelola koreksi manual dan override absensi.</p>
        </a>
    </div>

</div>
@endsection