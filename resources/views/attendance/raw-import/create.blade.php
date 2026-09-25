@extends('layouts.app')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-semibold tracking-tight">Import Raw Attendance Logs</h1>
    <p class="mt-1 text-sm text-slate-500">Upload file absensi mentah dari mesin</p>
</div>

<form method="POST" action="{{ route('attendance.raw-logs.import.store') }}" enctype="multipart/form-data" class="space-y-6">
    @csrf

    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="grid gap-6 md:grid-cols-2">
            <div class="md:col-span-2">
                <label class="mb-2 block text-sm font-medium text-slate-700">File</label>
                <input type="file" name="file" required
                    class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                @error('file')
                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">Datetime Format</label>
                <input type="text" name="datetime_format" value="{{ old('datetime_format', 'n/j/Y g:i A') }}"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                <p class="mt-2 text-xs text-slate-500">
                    Contoh: 3/25/2025 8:44 AM
                </p>
                @error('datetime_format')
                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-sky-100 bg-sky-50/70 p-4">
        <div class="flex items-start gap-3">
            <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-sky-100 text-xs font-bold text-sky-700">
                i
            </div>

            <div class="min-w-0">
                <h3 class="text-sm font-semibold text-slate-900">
                    Employee Matching
                </h3>

                <div class="mt-1.5 space-y-1 text-sm leading-6 text-slate-600">
                    <p>
                        Sistem akan mencocokkan <strong>AC-No</strong> dari file import ke
                        <strong>biometric_code</strong> karyawan.
                    </p>

                    <p>
                        Jika tidak ditemukan, sistem akan mencoba fallback ke
                        <strong>emp_code</strong> menggunakan prefix default
                        <strong>{{ old('employee_prefix', 'EK') }}</strong>.
                    </p>

                    <p class="text-xs leading-5 text-slate-500">
                        Contoh fallback: AC-No <strong>123</strong> menjadi emp_code
                        <strong>{{ old('employee_prefix', 'EK') }}123</strong>.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-sm font-semibold text-slate-900">After Import Processing</h2>
        <p class="mt-1 text-sm text-slate-500">
            Pilih proses lanjutan setelah raw log berhasil diimport.
        </p>

        <div class="mt-4 space-y-3">
            <label class="flex cursor-pointer gap-3 rounded-2xl border border-slate-200 p-4 hover:bg-slate-50">
                <input type="radio" name="after_import_action" value="import_only"
                    @checked(old('after_import_action') === 'import_only')
                    class="mt-1">
                <span>
                    <span class="block text-sm font-medium text-slate-900">Import only</span>
                    <span class="block text-xs text-slate-500">Hanya simpan ke raw log. Normalize dan build daily dilakukan manual nanti.</span>
                </span>
            </label>

            <label class="flex cursor-pointer gap-3 rounded-2xl border border-slate-200 p-4 hover:bg-slate-50">
                <input type="radio" name="after_import_action" value="normalize"
                    @checked(old('after_import_action') === 'normalize')
                    class="mt-1">
                <span>
                    <span class="block text-sm font-medium text-slate-900">Import + Normalize</span>
                    <span class="block text-xs text-slate-500">Setelah import, sistem langsung membuat normalized logs untuk tanggal yang terimport.</span>
                </span>
            </label>

            <label class="flex cursor-pointer gap-3 rounded-2xl border border-amber-200 bg-amber-50/60 p-4 hover:bg-amber-50">
                <input type="radio" name="after_import_action" value="full_pipeline"
                    @checked(old('after_import_action','full_pipeline') === 'full_pipeline')
                    class="mt-1">
                <span>
                    <span class="block text-sm font-medium text-slate-900">Import + Normalize + Build Daily</span>
                    <span class="block text-xs text-amber-700">
                        Proses tercepat. Attendance daily akan dibuat/update berdasarkan tanggal yang ada di file import.
                    </span>
                </span>
            </label>
        </div>

        @error('after_import_action')
            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex items-center gap-3">
        <button type="submit" class="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-medium text-white hover:bg-slate-800">
            Import
        </button>
        <a href="{{ route('attendance.raw-logs.index') }}" class="rounded-2xl border border-slate-300 px-5 py-3 text-sm font-medium hover:bg-slate-50">
            Cancel
        </a>
    </div>
</form>
@endsection