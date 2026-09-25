@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Import Employees"
        subtitle="Import master employee dari Excel atau CSV. Data akan create/update berdasarkan emp_code."
        :breadcrumbs="[
            ['label' => 'Master'],
            ['label' => 'Employees', 'url' => route('master.employees.index')],
            ['label' => 'Import'],
        ]"
    >
        <x-slot:actions>
            <x-ui.button
                type="button"
                onclick="window.location='{{ route('master.employees.index') }}'"
            >
                Back
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.page-section
        title="Import Configuration"
        subtitle="Gunakan format sederhana agar aman untuk cutover data awal."
    >
        <form
            method="POST"
            action="{{ route('master.employees.import.store') }}"
            enctype="multipart/form-data"
            class="space-y-6"
        >
            @csrf

            <div class="rounded-2xl border border-indigo-200 bg-indigo-50/70 p-4 text-sm text-indigo-900">
                <div class="font-semibold">Import employee bersifat upsert.</div>
                <div class="mt-1">
                    Jika <b>emp_code</b> sudah ada, data employee akan diupdate. Jika belum ada, employee baru akan dibuat.
                    Import ini tidak membuat employee assignment.
                </div>
            </div>

            <x-ui.field label="Import File" :error="$errors->first('file')">
                <input
                    type="file"
                    name="file"
                    accept=".xlsx,.xls,.csv,.txt"
                    class="block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 file:mr-4 file:rounded-xl file:border-0 file:bg-slate-900 file:px-4 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-slate-800"
                >
            </x-ui.field>

            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <div class="text-sm font-semibold text-slate-900">Supported Format</div>

                <div class="mt-3 overflow-x-auto rounded-xl border border-slate-200 bg-white">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">emp_code</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">biometric_code</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">full_name</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">employment_type_code</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">join_date</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">resign_date</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">active</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">notes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr>
                                <td class="px-4 py-3">EMP001</td>
                                <td class="px-4 py-3">BIO001</td>
                                <td class="px-4 py-3">Andi Office</td>
                                <td class="px-4 py-3">PERM</td>
                                <td class="px-4 py-3">2025-01-01</td>
                                <td class="px-4 py-3"></td>
                                <td class="px-4 py-3">1</td>
                                <td class="px-4 py-3">optional</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 text-xs text-slate-500">
                    Kolom wajib untuk employee baru:
                    emp_code, full_name, employment_type_code, join_date.

                    Kolom opsional:
                    biometric_code, resign_date, active, notes.

                    Jika resign_date kosong berarti employee dianggap masih aktif bekerja.
                </div>
            </div>

            <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
                <x-ui.button
                    type="button"
                    variant="ghost"
                    onclick="window.location='{{ route('master.employees.index') }}'"
                >
                    Cancel
                </x-ui.button>

                <x-ui.button type="submit">
                    Run Import
                </x-ui.button>
            </div>
        </form>
    </x-ui.page-section>
</div>
@endsection