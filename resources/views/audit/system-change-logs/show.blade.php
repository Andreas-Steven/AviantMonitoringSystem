@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="System Change Log Detail"
        subtitle="Detail perubahan data sistem, termasuk old data dan new data."
        :breadcrumbs="[
            ['label' => 'Audit'],
            ['label' => 'System Change Logs'],
            ['label' => 'Detail'],
        ]"
    >
        <x-slot:actions>
            <x-ui.button
                variant="ghost"
                onclick="window.location='{{ route('audit.system-change-logs.index') }}'"
            >
                Back to List
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <x-ui.section-card title="Audit Snapshot" subtitle="Metadata utama dari audit log ini.">
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <div class="text-xs text-slate-500">Table Name</div>
                        <div class="mt-1 font-medium text-slate-900">{{ $row->table_name }}</div>
                    </div>

                    <div>
                        <div class="text-xs text-slate-500">Record PK</div>
                        <div class="mt-1 font-medium text-slate-900">{{ $row->record_pk }}</div>
                    </div>

                    <div>
                        <div class="text-xs text-slate-500">Action Type</div>
                        <div class="mt-1 font-medium text-slate-900">{{ $row->action_type_code }}</div>
                    </div>

                    <div>
                        <div class="text-xs text-slate-500">Changed At</div>
                        <div class="mt-1 font-medium text-slate-900">{{ optional($row->changed_at)->format('Y-m-d H:i:s') ?: '-' }}</div>
                    </div>

                    <div>
                        <div class="text-xs text-slate-500">Changed By</div>
                        <div class="mt-1 font-medium text-slate-900">{{ $row->changer->full_name ?? '-' }}</div>
                        <div class="mt-1 text-xs text-slate-500">{{ $row->changer->emp_code ?? '' }}</div>
                    </div>
                </div>
            </x-ui.section-card>

            <div class="grid gap-6 xl:grid-cols-2">
                <x-ui.section-card title="Old Data" subtitle="Data sebelum perubahan.">
                    <pre class="overflow-x-auto rounded-2xl border border-slate-200 bg-slate-50 p-4 text-xs text-slate-700">{{ json_encode($row->old_data ?? null, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </x-ui.section-card>

                <x-ui.section-card title="New Data" subtitle="Data sesudah perubahan.">
                    <pre class="overflow-x-auto rounded-2xl border border-slate-200 bg-slate-50 p-4 text-xs text-slate-700">{{ json_encode($row->new_data ?? null, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </x-ui.section-card>
            </div>
        </div>

        <div class="space-y-6">
            <x-ui.section-card title="Notes" subtitle="Catatan tambahan untuk perubahan ini.">
                <div class="text-sm whitespace-pre-line text-slate-800">{{ $row->notes ?: '-' }}</div>
            </x-ui.section-card>
        </div>
    </div>
</div>
@endsection