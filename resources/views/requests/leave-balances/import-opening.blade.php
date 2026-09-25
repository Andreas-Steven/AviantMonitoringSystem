@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Import Opening Leave Balance"
        subtitle="Import sisa saldo cuti existing sebagai transaksi OPENING saat cutover implementasi sistem."
        :breadcrumbs="[
            ['label' => 'Requests'],
            ['label' => 'Leave Balances', 'url' => route('requests.leave-balances.index')],
            ['label' => 'Import Opening Balance'],
        ]"
    >
        <x-slot:actions>
            <x-ui.button
                type="button"
                onclick="window.location='{{ route('requests.leave-balances.index') }}'"
            >
                Back
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            {{ session('error') }}
        </div>
    @endif

    <x-ui.page-section
        title="Import Configuration"
        subtitle="Saldo cuti akan dibuat sebagai transaksi ledger OPENING dan otomatis recalculate balance bucket."
    >
        <form
            method="POST"
            action="{{ route('requests.leave-balances.import-opening.store') }}"
            enctype="multipart/form-data"
            class="space-y-6"
        >
            @csrf

            <div class="rounded-2xl border border-indigo-200 bg-indigo-50/70 p-4 text-sm text-indigo-900">
                <div class="font-semibold">
                    Import opening balance bersifat idempotent.
                </div>

                <div class="mt-1 text-indigo-800">
                    Employee yang sudah memiliki transaksi OPENING pada bucket periode yang sama akan otomatis dilewati untuk mencegah double opening balance.
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <x-ui.field
                    label="Leave Type"
                    :error="$errors->first('leave_type_id')"
                >
                    <select
                        name="leave_type_id"
                        class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                    >
                        <option value="">Pilih leave type</option>

                        @foreach($leaveTypes as $leaveType)
                            <option
                                value="{{ $leaveType->leave_type_id }}"
                                @selected((string) old('leave_type_id') === (string) $leaveType->leave_type_id)
                            >
                                {{ $leaveType->leave_type_name }}
                                ({{ $leaveType->leave_type_code }})
                            </option>
                        @endforeach
                    </select>
                </x-ui.field>

                <x-ui.field
                    label="Transaction Date"
                    :error="$errors->first('transaction_date')"
                >
                    <input
                        type="date"
                        name="transaction_date"
                        value="{{ old('transaction_date', now()->toDateString()) }}"
                        class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                    >
                </x-ui.field>

                <x-ui.field
                    label="Period Start Date"
                    :error="$errors->first('period_start_date')"
                >
                    <input
                        type="date"
                        name="period_start_date"
                        value="{{ old('period_start_date', now()->startOfYear()->toDateString()) }}"
                        class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                    >
                </x-ui.field>

                <x-ui.field
                    label="Period End Date"
                    :error="$errors->first('period_end_date')"
                >
                    <input
                        type="date"
                        name="period_end_date"
                        value="{{ old('period_end_date', now()->endOfYear()->toDateString()) }}"
                        class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                    >
                </x-ui.field>
            </div>

            <x-ui.field
                label="Import File"
                :error="$errors->first('file')"
            >
                <div class="space-y-4">
                    <input
                        type="file"
                        name="file"
                        accept=".xlsx,.xls,.csv"
                        class="block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 file:mr-4 file:rounded-xl file:border-0 file:bg-slate-900 file:px-4 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-slate-800"
                    >

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-sm font-semibold text-slate-900">
                            Supported Format
                        </div>

                        <div class="mt-3 overflow-hidden rounded-xl border border-slate-200 bg-white">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold text-slate-600">
                                            employee_code
                                        </th>

                                        <th class="px-4 py-3 text-left font-semibold text-slate-600">
                                            remaining_leave
                                        </th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-slate-100">
                                    <tr>
                                        <td class="px-4 py-3 text-slate-700">
                                            EMP001
                                        </td>

                                        <td class="px-4 py-3 text-slate-700">
                                            5
                                        </td>
                                    </tr>

                                    <tr>
                                        <td class="px-4 py-3 text-slate-700">
                                            EMP002
                                        </td>

                                        <td class="px-4 py-3 text-slate-700">
                                            3.5
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3 text-xs text-slate-500">
                            Header wajib berada di baris pertama.
                            Alias yang didukung:
                            <span class="font-medium text-slate-700">emp_code</span>
                            dan
                            <span class="font-medium text-slate-700">sisa_cuti</span>.
                        </div>
                    </div>
                </div>
            </x-ui.field>

            <div class="flex flex-col gap-3 border-t border-slate-100 pt-5 md:flex-row md:items-center md:justify-between">
                <div class="text-sm text-slate-500">
                    Sistem akan otomatis membuat bucket saldo jika employee belum memiliki bucket pada periode tersebut.
                </div>

                <div class="flex justify-end gap-3">
                    <x-ui.button
                        type="button"
                        variant="ghost"
                        onclick="window.location='{{ route('requests.leave-balances.index') }}'"
                    >
                        Cancel
                    </x-ui.button>

                    <x-ui.button type="submit">
                        Run Import
                    </x-ui.button>
                </div>
            </div>
        </form>
    </x-ui.page-section>
</div>
@endsection