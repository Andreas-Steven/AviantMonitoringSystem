@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Login Audits"
        subtitle="Jejak login internal user untuk monitoring akses dan troubleshooting autentikasi."
        :breadcrumbs="[
            ['label' => 'Audit'],
            ['label' => 'Login Audits'],
        ]"
    />

    <x-ui.page-section
        title="Login Audit Filters"
        subtitle="Filter berdasarkan email, login status, ip, catatan, dan tanggal."
    >
        <form method="GET" action="{{ route('audit.login-audits.index') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.field label="Keyword">
                <input
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Cari email / google sub / ip / notes"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                >
            </x-ui.field>

            <x-ui.field label="Login Status">
                <select name="login_status" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                    <option value="">Semua status</option>
                    @foreach($loginStatuses as $status)
                        <option value="{{ $status }}" @selected(request('login_status') === $status)>
                            {{ $status }}
                        </option>
                    @endforeach
                </select>
            </x-ui.field>

            <x-ui.field label="Date From">
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
            </x-ui.field>

            <x-ui.field label="Date To">
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
            </x-ui.field>

            <div class="flex items-end gap-3 xl:col-span-4">
                <x-ui.button type="submit">Search</x-ui.button>
                <x-ui.button type="button" variant="ghost" onclick="window.location='{{ route('audit.login-audits.index') }}'">
                    Reset
                </x-ui.button>
            </div>
        </form>
    </x-ui.page-section>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat-card
            title="Rows"
            :value="$rows->total()"
            description="Total login audit hasil filter"
        />
        <x-ui.stat-card
            title="Success"
            :value="$rows->getCollection()->where('login_status', 'SUCCESS')->count()"
            description="SUCCESS di halaman ini"
        />
        <x-ui.stat-card
            title="Failed"
            :value="$rows->getCollection()->where('login_status', 'FAILED')->count()"
            description="FAILED di halaman ini"
        />
        <x-ui.stat-card
            title="With User"
            :value="$rows->getCollection()->filter(fn($row) => !empty($row->user_id))->count()"
            description="Terkait app user"
        />
    </div>

    <x-ui.table-shell>
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50/80">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Logged At</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Email</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">User</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">IP</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Notes</th>
                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse($rows as $row)
                    @php
                        $tone = match($row->login_status) {
                            'SUCCESS' => 'success',
                            'FAILED' => 'danger',
                            default => 'neutral',
                        };
                    @endphp

                    <tr class="transition hover:bg-slate-50">
                        <td class="px-5 py-4">
                            <div class="font-medium text-slate-900">{{ optional($row->logged_at)->format('Y-m-d H:i:s') ?: '-' }}</div>
                        </td>

                        <td class="px-5 py-4 text-slate-600">
                            {{ $row->email }}
                        </td>

                        <td class="px-5 py-4">
                            <x-ui.status-badge :label="$row->login_status" :tone="$tone" />
                        </td>

                        <td class="px-5 py-4">
                            @if($row->user)
                                <div class="font-medium text-slate-900">{{ $row->user->full_name }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $row->user->email }}</div>
                            @else
                                <span class="text-sm text-slate-400">-</span>
                            @endif
                        </td>

                        <td class="px-5 py-4 text-slate-600">
                            {{ $row->ip_address ?: '-' }}
                        </td>

                        <td class="px-5 py-4 text-slate-600">
                            <div class="line-clamp-2">{{ $row->notes ?: '-' }}</div>
                        </td>

                        <td class="px-5 py-4">
                            <div class="flex justify-end gap-2">
                                <x-ui.button
                                    variant="ghost"
                                    onclick="window.location='{{ route('audit.login-audits.show', $row->login_audit_id) }}'"
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
                                title="No login audits found"
                                description="Belum ada login audit sesuai filter."
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