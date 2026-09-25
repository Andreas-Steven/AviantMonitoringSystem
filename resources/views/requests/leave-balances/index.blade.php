@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Leave Balances"
        subtitle="Monitor bucket saldo cuti per employee, leave type, dan periode aktif."
        :breadcrumbs="[
            ['label' => 'Requests'],
            ['label' => 'Leave Balances'],
        ]"
    >
        <x-slot:actions>
            @if(auth()->user()?->hasPermission('leave.manage') && Route::has('requests.leave-balances.import-opening.create'))
                <x-ui.button
                    type="button"
                    onclick="window.location='{{ route('requests.leave-balances.import-opening.create') }}'"
                >
                    Import Opening Balance
                </x-ui.button>
            @endif
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

    @if(session('import_result'))
        @php
            $importResult = session('import_result');
        @endphp

        <x-ui.page-section
            title="Opening Balance Import Result"
            subtitle="Ringkasan hasil import sisa cuti terakhir."
        >
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <x-ui.stat-card
                    label="Total Rows"
                    :value="$importResult['total_rows'] ?? 0"
                    hint="Total row terbaca"
                />
                <x-ui.stat-card
                    label="Success"
                    :value="$importResult['success_rows'] ?? 0"
                    hint="Berhasil dibuat sebagai OPENING"
                />
                <x-ui.stat-card
                    label="Skipped"
                    :value="$importResult['skipped_rows'] ?? 0"
                    hint="Dilewati karena sudah ada / saldo 0"
                />
                <x-ui.stat-card
                    label="Failed"
                    :value="$importResult['failed_rows'] ?? 0"
                    hint="Gagal validasi"
                />
            </div>

            @if(!empty($importResult['errors']))
                <div class="mt-5 rounded-2xl border border-rose-200 bg-rose-50 p-4">
                    <div class="text-sm font-semibold text-rose-900">Failed Rows</div>
                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-rose-800">
                        @foreach(array_slice($importResult['errors'], 0, 10) as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(!empty($importResult['skipped']))
                <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 p-4">
                    <div class="text-sm font-semibold text-amber-900">Skipped Rows</div>
                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-amber-800">
                        @foreach(array_slice($importResult['skipped'], 0, 10) as $skipped)
                            <li>{{ $skipped }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </x-ui.page-section>
    @endif

    <x-ui.page-section
        title="Leave Balance Filters"
        subtitle="Filter saldo cuti berdasarkan employee, leave type, status aktif, dan tanggal periode."
    >
        <form method="GET" action="{{ route('requests.leave-balances.index') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.field label="Employee">
                <input
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Cari emp code / nama / biometric"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                >
            </x-ui.field>

            <x-ui.field label="Leave Type">
                <select name="leave_type_id" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                    <option value="">Semua leave type</option>
                    @foreach($leaveTypes as $leaveType)
                        <option value="{{ $leaveType->leave_type_id }}" @selected((string) request('leave_type_id') === (string) $leaveType->leave_type_id)>
                            {{ $leaveType->leave_type_name }}
                        </option>
                    @endforeach
                </select>
            </x-ui.field>

            <x-ui.field label="Active">
                <select name="active" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                    <option value="">Semua</option>
                    <option value="1" @selected(request('active') === '1')>Active</option>
                    <option value="0" @selected(request('active') === '0')>Inactive</option>
                </select>
            </x-ui.field>

            <x-ui.field label="Period Date">
                <input
                    type="date"
                    name="period_date"
                    value="{{ request('period_date') }}"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                >
            </x-ui.field>

            <div class="flex items-end gap-3 xl:col-span-4">
                <x-ui.button type="submit">Search</x-ui.button>
                <x-ui.button type="button" variant="ghost" onclick="window.location='{{ route('requests.leave-balances.index') }}'">Reset</x-ui.button>
            </div>
        </form>
    </x-ui.page-section>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat-card
            label="Rows"
            :value="$summaryStats['total_rows']"
            hint="Total bucket saldo cuti hasil filter"
        />
        <x-ui.stat-card
            label="Active"
            :value="$summaryStats['active_rows']"
            hint="Bucket yang masih active"
        />
        <x-ui.stat-card
            label="Annual"
            :value="$summaryStats['annual_rows']"
            hint="Bucket leave type ANNUAL"
        />
        <x-ui.stat-card
            label="Low Balance"
            :value="$summaryStats['low_balance_rows']"
            hint="Available balance <= 1"
        />
    </div>

    <x-ui.page-section
        title="Bulk Grant Leave"
        subtitle="Tambahkan saldo cuti massal ke bucket aktif berdasarkan leave type, tanggal periode, dan employment type."
    >
        <form method="POST" action="{{ route('requests.leave-balances.bulk-grant') }}" class="space-y-5">
            @csrf

            <div class="rounded-2xl border border-amber-200 bg-amber-50/70 p-4 text-sm text-amber-800">
                <div class="font-semibold text-amber-900">Bulk grant menggunakan ledger GRANT.</div>
                <div class="mt-1">
                    Sistem hanya akan memproses bucket saldo cuti yang aktif dan tanggal grant berada di dalam periode bucket.
                    Source Ref dibuat otomatis per employee agar submit ulang tidak membuat grant dobel.
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                <x-ui.field label="Leave Type" :error="$errors->first('leave_type_id')">
                    <select
                        name="leave_type_id"
                        class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                    >
                        <option value="">Pilih leave type</option>
                        @foreach($leaveTypes as $leaveType)
                            <option value="{{ $leaveType->leave_type_id }}" @selected((string) old('leave_type_id') === (string) $leaveType->leave_type_id)>
                                {{ $leaveType->leave_type_name }} ({{ $leaveType->leave_type_code }})
                            </option>
                        @endforeach
                    </select>
                </x-ui.field>

                <x-ui.field label="Grant Date" :error="$errors->first('transaction_date')">
                    <input
                        type="date"
                        name="transaction_date"
                        value="{{ old('transaction_date', now()->startOfYear()->toDateString()) }}"
                        class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                    >
                </x-ui.field>

                <x-ui.field label="Grant Qty" :error="$errors->first('qty')">
                    <input
                        type="number"
                        step="0.01"
                        min="0.01"
                        name="qty"
                        value="{{ old('qty') }}"
                        placeholder="Contoh: 12.00"
                        class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                    >
                </x-ui.field>
            </div>

            <x-ui.field label="Employment Type" :error="$errors->first('employment_type_ids')">
                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                    @foreach($employmentTypes as $employmentType)
                        <label class="group flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-indigo-200 hover:bg-indigo-50/40">
                            <input
                                type="checkbox"
                                name="employment_type_ids[]"
                                value="{{ $employmentType->employment_type_id }}"
                                @checked(in_array((string) $employmentType->employment_type_id, old('employment_type_ids', []), true))
                                class="mt-1 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                            >
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-slate-900">
                                    {{ $employmentType->employment_type_name }}
                                </span>
                                <span class="mt-1 inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-medium text-slate-600">
                                    {{ $employmentType->employment_type_code }}
                                </span>
                            </span>
                        </label>
                    @endforeach
                </div>

                @if($employmentTypes->isEmpty())
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-500">
                        Belum ada employment type aktif.
                    </div>
                @endif

                @error('employment_type_ids.*')
                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </x-ui.field>

            <x-ui.field label="Notes" :error="$errors->first('notes')">
                <textarea
                    name="notes"
                    rows="3"
                    placeholder="Opsional. Contoh: Annual leave grant tahun {{ now()->year }}"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                >{{ old('notes') }}</textarea>
            </x-ui.field>

            <div class="flex flex-col gap-3 border-t border-slate-100 pt-5 md:flex-row md:items-center md:justify-between">
                <div class="text-sm text-slate-500">
                    Hanya bucket aktif yang cocok dengan leave type, tanggal grant, dan employment type yang akan diproses.
                </div>

                <div class="flex justify-end gap-3">
                    <x-ui.button type="submit">
                        Run Bulk Grant
                    </x-ui.button>
                </div>
            </div>
        </form>
    </x-ui.page-section>

    <x-ui.table-shell>
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50/80">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Employee</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Leave Type</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Period</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Balance Snapshot</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse($balances as $balance)
                    @php
                        $available = (float) $balance->available_balance;
                        $isLowBalance = $available <= 1;
                        $leaveTypeCode = $balance->leaveType->leave_type_code ?? '-';

                        $leaveTypeTone = match($leaveTypeCode) {
                            'ANNUAL' => 'success',
                            'SICK' => 'info',
                            'PERMIT' => 'warning',
                            'UNPAID' => 'danger',
                            default => 'neutral',
                        };
                    @endphp

                    <tr class="transition hover:bg-slate-50">
                        <td class="px-5 py-4">
                            <div class="font-medium text-slate-900">
                                {{ $balance->employee->full_name ?? '-' }}
                            </div>
                            <div class="mt-1 text-xs text-slate-500">
                                {{ $balance->employee->emp_code ?? '-' }}
                            </div>
                        </td>

                        <td class="px-5 py-4">
                            <div class="flex flex-wrap gap-2">
                                <x-ui.status-badge
                                    :label="$balance->leaveType->leave_type_name ?? '-'"
                                    :tone="$leaveTypeTone"
                                />
                                @if($leaveTypeCode !== '-')
                                    <x-ui.status-badge :label="$leaveTypeCode" tone="neutral" />
                                @endif
                            </div>
                        </td>

                        <td class="px-5 py-4">
                            <div class="font-medium text-slate-900">
                                {{ optional($balance->period_start_date)->format('Y-m-d') }}
                                —
                                {{ optional($balance->period_end_date)->format('Y-m-d') }}
                            </div>
                            <div class="mt-1 text-xs text-slate-500">
                                Closing: {{ number_format((float) $balance->closing_balance, 2) }}
                            </div>
                        </td>

                        <td class="px-5 py-4">
                            <div class="flex items-center gap-2">
                                <span class="text-base font-semibold {{ $isLowBalance ? 'text-amber-700' : 'text-slate-900' }}">
                                    {{ number_format($available, 2) }}
                                </span>

                                @if($isLowBalance)
                                    <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-[11px] font-medium text-amber-700">
                                        Low
                                    </span>
                                @endif
                            </div>

                            <div class="mt-2 grid gap-1 text-xs text-slate-500">
                                <div>Opening: {{ number_format((float) $balance->opening_balance, 2) }}</div>
                                <div>Granted: {{ number_format((float) $balance->granted_amount, 2) }}</div>
                                <div>Used: {{ number_format((float) $balance->used_amount, 2) }}</div>
                                <div>Expired: {{ number_format((float) $balance->expired_amount, 2) }}</div>
                            </div>
                        </td>

                        <td class="px-5 py-4">
                            <div class="flex flex-wrap gap-2">
                                <x-ui.status-badge
                                    :label="$balance->active ? 'ACTIVE' : 'INACTIVE'"
                                    :tone="$balance->active ? 'success' : 'neutral'"
                                />

                                @if($isLowBalance)
                                    <x-ui.status-badge label="LOW BALANCE" tone="warning" />
                                @endif
                            </div>
                        </td>

                        <td class="px-5 py-4 text-right">
                            <div class="flex justify-end gap-2">
                                <x-ui.button
                                    variant="ghost"
                                    onclick="window.location='{{ route('requests.leave-balances.show', $balance->employee_leave_balance_id) }}'"
                                >
                                    Detail
                                </x-ui.button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-14">
                            <x-ui.empty-state
                                title="No leave balances found"
                                description="Belum ada leave balance sesuai filter."
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($balances->hasPages())
            <div class="border-t border-slate-200 bg-white px-5 py-4">
                {{ $balances->withQueryString()->links() }}
            </div>
        @endif
    </x-ui.table-shell>
</div>
@endsection