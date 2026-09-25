@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Import Employee Work Pattern Assignment"
        subtitle="Import assignment work pattern employee berdasarkan employee code, work pattern code, dan periode efektif."
        :breadcrumbs="[
            ['label' => 'Scheduling'],
            ['label' => 'Employee Work Pattern Assignments', 'url' => route('scheduling.employee-work-pattern-assignments.index')],
            ['label' => 'Import'],
        ]"
    >
        <x-slot:actions>
            <x-ui.button
                type="button"
                onclick="window.location='{{ route('scheduling.employee-work-pattern-assignments.index') }}'"
            >
                Back
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.page-section
        title="Import Configuration"
        subtitle="Import ini bersifat safe insert only. Jika ada overlap periode work pattern assignment, row akan gagal dan tidak auto replace."
    >
        <form
            method="POST"
            action="{{ route('scheduling.employee-work-pattern-assignments.import.store') }}"
            enctype="multipart/form-data"
            class="space-y-6"
        >
            @csrf

            <div class="rounded-2xl border border-amber-200 bg-amber-50/70 p-4 text-sm text-amber-900">
                <div class="font-semibold">Import ini tidak melakukan replace work pattern assignment existing.</div>
                <div class="mt-1">
                    Jika employee sudah memiliki work pattern assignment yang overlap pada periode yang sama, row akan masuk failed.
                    Gunakan flow edit/replace assignment manual untuk perubahan historis.
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
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">work_pattern_code</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">effective_start_date</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">effective_end_date</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">notes</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            <tr>
                                <td class="px-4 py-3">EMP001</td>
                                <td class="px-4 py-3">OFFICE_HOLIDAY_WINS</td>
                                <td class="px-4 py-3">2025-01-01</td>
                                <td class="px-4 py-3"></td>
                                <td class="px-4 py-3">Initial work pattern assignment</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 text-xs text-slate-500">
                    Kolom wajib: emp_code, work_pattern_code, effective_start_date.
                    Kolom opsional: effective_end_date, notes.
                </div>
            </div>

            <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
                <x-ui.button
                    type="button"
                    variant="ghost"
                    onclick="window.location='{{ route('scheduling.employee-work-pattern-assignments.index') }}'"
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