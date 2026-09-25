@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="System Change Logs"
        subtitle="Jejak perubahan data sistem untuk audit trail operasional."
        :breadcrumbs="[
            ['label' => 'Audit'],
            ['label' => 'System Change Logs'],
        ]"
    />

    <x-ui.page-section
        title="Audit Filters"
        subtitle="Filter berdasarkan tabel, aksi, pelaku perubahan, tanggal, dan keyword."
    >
        <form method="GET" action="{{ route('audit.system-change-logs.index') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.field label="Keyword">
                <input
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Cari record pk / notes / table"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                >
            </x-ui.field>

            <x-ui.field label="Table Name">
                <select name="table_name" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                    <option value="">Semua tabel</option>
                    @foreach($tableNames as $tableName)
                        <option value="{{ $tableName }}" @selected(request('table_name') === $tableName)>
                            {{ $tableName }}
                        </option>
                    @endforeach
                </select>
            </x-ui.field>

            <x-ui.field label="Action Type">
                <select name="action_type_code" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                    <option value="">Semua action</option>
                    @foreach($actionTypes as $actionType)
                        <option value="{{ $actionType }}" @selected(request('action_type_code') === $actionType)>
                            {{ $actionType }}
                        </option>
                    @endforeach
                </select>
            </x-ui.field>

            <x-ui.field label="Changed By">
                <input
                    type="text"
                    name="changed_by"
                    value="{{ request('changed_by') }}"
                    placeholder="Cari emp code / nama"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                >
            </x-ui.field>

            <x-ui.field label="Date From">
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
            </x-ui.field>

            <x-ui.field label="Date To">
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
            </x-ui.field>

            <div class="flex items-end gap-3 xl:col-span-4">
                <x-ui.button type="submit">Search</x-ui.button>
                <x-ui.button type="button" variant="ghost" onclick="window.location='{{ route('audit.system-change-logs.index') }}'">
                    Reset
                </x-ui.button>
            </div>
        </form>
    </x-ui.page-section>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat-card
            title="Rows"
            :value="$rows->total()"
            description="Total audit rows hasil filter"
        />
        <x-ui.stat-card
            title="Updates"
            :value="$rows->getCollection()->where('action_type_code', 'UPDATE')->count()"
            description="UPDATE di halaman ini"
        />
        <x-ui.stat-card
            title="Approvals"
            :value="$rows->getCollection()->where('action_type_code', 'APPROVE')->count()"
            description="APPROVE di halaman ini"
        />
        <x-ui.stat-card
            title="Deletes"
            :value="$rows->getCollection()->where('action_type_code', 'DELETE')->count()"
            description="DELETE di halaman ini"
        />
    </div>

    <x-ui.table-shell>
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50/80">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Changed At</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Table</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Record PK</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Changed By</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Notes</th>
                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse($rows as $row)
                    @php
                        $actionTone = match($row->action_type_code) {
                            'INSERT' => 'success',
                            'UPDATE' => 'info',
                            'DELETE' => 'danger',
                            'APPROVE' => 'success',
                            'REJECT' => 'danger',
                            'CALCULATE' => 'warning',
                            default => 'neutral',
                        };
                    @endphp

                    <tr class="transition hover:bg-slate-50">
                        <td class="px-5 py-4">
                            <div class="font-medium text-slate-900">{{ optional($row->changed_at)->format('Y-m-d H:i:s') ?: '-' }}</div>
                        </td>

                        <td class="px-5 py-4 text-slate-600">
                            {{ $row->table_name }}
                        </td>

                        <td class="px-5 py-4 text-slate-600">
                            {{ $row->record_pk }}
                        </td>

                        <td class="px-5 py-4">
                            <x-ui.status-badge :label="$row->action_type_code" :tone="$actionTone" />
                        </td>

                        <td class="px-5 py-4">
                            @if($row->changer)
                                <div class="font-medium text-slate-900">{{ $row->changer->full_name }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $row->changer->emp_code }}</div>
                            @else
                                <span class="text-sm text-slate-400">-</span>
                            @endif
                        </td>

                        <td class="px-5 py-4 text-slate-600">
                            <div class="line-clamp-2">{{ $row->notes ?: '-' }}</div>
                        </td>

                        <td class="px-5 py-4">
                            <div class="flex justify-end gap-2">
                                <x-ui.button
                                    variant="ghost"
                                    onclick="window.location='{{ route('audit.system-change-logs.show', $row->change_log_id) }}'"
                                >
                                    Detail
                                </x-ui.button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-14">
                            <x-ui.empty-state
                                title="No audit logs found"
                                description="Belum ada system change logs sesuai filter."
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