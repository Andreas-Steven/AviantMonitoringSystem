@extends('layouts.app')

@section('title', 'Employee Debts')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Employee Debts"
        subtitle="Daftar hutang per kasus per employee, termasuk outstanding dan status pelunasan."
        :breadcrumbs="[
            ['label' => 'Payroll'],
            ['label' => 'Employee Debts'],
        ]"
    >
        <x-slot:actions>
            @if(auth()->user()?->hasPermission('employee_debt.manage'))
                <x-ui.button onclick="window.location='{{ route('payroll.debts.create') }}'">
                    Add Debt
                </x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.page-section
        title="Filters"
        subtitle="Cari berdasarkan debt code, debt name, employee, category, dan status."
    >
        <form method="GET" action="{{ route('payroll.debts.index') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.field label="Keyword">
                <input
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Cari debt code / debt name / employee"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                >
            </x-ui.field>

            <x-ui.field label="Status">
                <select name="status_code" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                    <option value="">Semua status</option>
                    @foreach($statusOptions as $status)
                        <option value="{{ $status }}" @selected(request('status_code') === $status)>
                            {{ $status }}
                        </option>
                    @endforeach
                </select>
            </x-ui.field>

            <x-ui.field label="Category">
                <select name="debt_category_code" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                    <option value="">Semua category</option>
                    @foreach($categoryOptions as $category)
                        <option value="{{ $category }}" @selected(request('debt_category_code') === $category)>
                            {{ $category }}
                        </option>
                    @endforeach
                </select>
            </x-ui.field>

            <div class="flex items-end gap-3">
                <x-ui.button type="submit">Search</x-ui.button>
                <x-ui.button type="button" variant="ghost" onclick="window.location='{{ route('payroll.debts.index') }}'">Reset</x-ui.button>
            </div>
        </form>
    </x-ui.page-section>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
        <x-ui.stat-card label="Rows" :value="$summaryStats['total_rows']" hint="Total debt rows hasil filter" />
        <x-ui.stat-card label="Open" :value="$summaryStats['open_rows']" hint="Debt yang masih outstanding" />
        <x-ui.stat-card label="Settled" :value="$summaryStats['settled_rows']" hint="Debt yang sudah lunas" />
        <x-ui.stat-card label="Original Total" :value="number_format($summaryStats['original_total'], 2)" hint="Total nilai awal hutang hasil filter" />
        <x-ui.stat-card label="Outstanding Total" :value="number_format($summaryStats['outstanding_total'], 2)" hint="Total sisa hutang hasil filter" />
    </div>

    <x-ui.table-shell>
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50/80">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Debt</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Employee</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Origin</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Amount</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse($rows as $row)
                    <tr class="transition hover:bg-slate-50">
                        <td class="px-5 py-4">
                            <div class="font-semibold text-slate-900">{{ $row->debt_code }}</div>
                            <div class="mt-1 text-sm text-slate-600">{{ $row->debt_name }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ $row->debt_category_code }}</div>
                        </td>

                        <td class="px-5 py-4">
                            <div class="font-medium text-slate-900">{{ $row->employee?->full_name ?? '-' }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ $row->employee?->emp_code ?? '-' }}</div>
                        </td>

                        <td class="px-5 py-4 text-slate-700">
                            {{ optional($row->origin_date)->format('Y-m-d') ?: '-' }}
                        </td>

                        <td class="px-5 py-4">
                            <div class="text-sm text-slate-500">Original</div>
                            <div class="font-semibold text-slate-900">{{ number_format((float) $row->original_amount, 2) }}</div>
                            <div class="mt-2 text-sm text-slate-500">Outstanding</div>
                            <div class="font-semibold {{ (float) $row->outstanding_amount > 0 ? 'text-amber-700' : 'text-emerald-700' }}">
                                {{ number_format((float) $row->outstanding_amount, 2) }}
                            </div>
                        </td>

                        <td class="px-5 py-4">
                            <x-ui.status-badge
                                :label="$row->status_code"
                                :tone="match($row->status_code) {
                                    'OPEN' => 'warning',
                                    'SETTLED' => 'success',
                                    'CANCELLED' => 'neutral',
                                    default => 'neutral'
                                }"
                            />
                        </td>

                        <td class="px-5 py-4 text-right">
                            <a
                                href="{{ route('payroll.debts.show', $row->employee_debt_id) }}"
                                class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
                            >
                                Detail
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-14">
                            <x-ui.empty-state
                                title="No debts found"
                                description="Belum ada employee debt sesuai filter."
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($rows->hasPages())
            <div class="border-t border-slate-200 bg-white px-5 py-4">
                {{ $rows->withQueryString()->links() }}
            </div>
        @endif
    </x-ui.table-shell>
</div>
@endsection