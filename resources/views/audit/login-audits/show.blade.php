@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Login Audit Detail"
        subtitle="Detail peristiwa login internal user."
        :breadcrumbs="[
            ['label' => 'Audit'],
            ['label' => 'Login Audits'],
            ['label' => 'Detail'],
        ]"
    >
        <x-slot:actions>
            <x-ui.button
                variant="ghost"
                onclick="window.location='{{ route('audit.login-audits.index') }}'"
            >
                Back to List
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <x-ui.section-card title="Login Snapshot" subtitle="Metadata utama dari login audit ini.">
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <div class="text-xs text-slate-500">Logged At</div>
                        <div class="mt-1 font-medium text-slate-900">{{ optional($row->logged_at)->format('Y-m-d H:i:s') ?: '-' }}</div>
                    </div>

                    <div>
                        <div class="text-xs text-slate-500">Login Status</div>
                        <div class="mt-1 font-medium text-slate-900">{{ $row->login_status }}</div>
                    </div>

                    <div>
                        <div class="text-xs text-slate-500">Email</div>
                        <div class="mt-1 font-medium text-slate-900">{{ $row->email }}</div>
                    </div>

                    <div>
                        <div class="text-xs text-slate-500">Google Sub</div>
                        <div class="mt-1 font-medium text-slate-900">{{ $row->google_sub ?: '-' }}</div>
                    </div>

                    <div>
                        <div class="text-xs text-slate-500">IP Address</div>
                        <div class="mt-1 font-medium text-slate-900">{{ $row->ip_address ?: '-' }}</div>
                    </div>

                    <div>
                        <div class="text-xs text-slate-500">User Agent</div>
                        <div class="mt-1 text-sm text-slate-800 break-words">{{ $row->user_agent ?: '-' }}</div>
                    </div>
                </div>
            </x-ui.section-card>

            <x-ui.section-card title="Notes" subtitle="Catatan tambahan untuk login audit ini.">
                <div class="text-sm whitespace-pre-line text-slate-800">{{ $row->notes ?: '-' }}</div>
            </x-ui.section-card>
        </div>

        <div class="space-y-6">
            <x-ui.section-card title="App User" subtitle="Relasi ke app user bila tersedia.">
                @if($row->user)
                    <div class="space-y-4">
                        <div>
                            <div class="text-xs text-slate-500">Full Name</div>
                            <div class="mt-1 font-medium text-slate-900">{{ $row->user->full_name }}</div>
                        </div>

                        <div>
                            <div class="text-xs text-slate-500">Email</div>
                            <div class="mt-1 font-medium text-slate-900">{{ $row->user->email }}</div>
                        </div>

                        <div>
                            <div class="text-xs text-slate-500">Active</div>
                            <div class="mt-1 font-medium text-slate-900">{{ $row->user->is_active ? 'Yes' : 'No' }}</div>
                        </div>

                        <div>
                            <div class="text-xs text-slate-500">Last Login At</div>
                            <div class="mt-1 font-medium text-slate-900">{{ optional($row->user->last_login_at)->format('Y-m-d H:i:s') ?: '-' }}</div>
                        </div>
                    </div>
                @else
                    <div class="text-sm text-slate-400">Tidak terhubung ke app user.</div>
                @endif
            </x-ui.section-card>
        </div>
    </div>
</div>
@endsection