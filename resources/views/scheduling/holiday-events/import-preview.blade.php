@extends('layouts.app')

@section('content')
@php
    $statusClasses = [
        'NEW' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'EXISTS' => 'bg-slate-50 text-slate-600 ring-slate-200',
        'CONFLICT' => 'bg-amber-50 text-amber-700 ring-amber-200',
    ];

    $newCount = $rows->where('status', 'NEW')->count();
    $existsCount = $rows->where('status', 'EXISTS')->count();
    $conflictCount = $rows->where('status', 'CONFLICT')->count();
@endphp

<div class="space-y-5">
    <x-ui.page-header
        title="Import Holidays"
        subtitle="Ambil data libur dari provider eksternal, preview, lalu pilih yang ingin dimasukkan ke Holiday Calendar."
        :breadcrumbs="[
            ['label' => 'Scheduling'],
            ['label' => 'Holiday Calendar', 'url' => route('scheduling.holiday-events.index')],
            ['label' => 'Import'],
        ]"
    />

    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <form
            method="GET"
            action="{{ route('scheduling.holiday-events.import.preview') }}"
            class="grid gap-4 md:grid-cols-[180px_220px_auto]"
        >
            <x-ui.field label="Year">
                <input
                    type="number"
                    name="year"
                    value="{{ old('year', $year) }}"
                    min="2020"
                    max="2100"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                >
            </x-ui.field>

            <x-ui.field label="Source">
                <select
                    name="source"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                >
                    <option value="calendarific" @selected($source === 'calendarific')>
                        Calendarific
                    </option>
                    <option value="google" @selected($source === 'google')>
                        Google Calendar (experimental)
                    </option>
                </select>
            </x-ui.field>

            <div class="flex items-end gap-2">
                <x-ui.button type="submit">
                    Fetch Preview
                </x-ui.button>

                <x-ui.button
                    type="button"
                    variant="ghost"
                    onclick="window.location='{{ route('scheduling.holiday-events.index') }}'"
                >
                    Back
                </x-ui.button>
            </div>
        </form>
    </div>

    @if($rows->isNotEmpty())
        <div class="grid gap-3 md:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                <div class="text-xs font-medium text-slate-500">Fetched</div>
                <div class="mt-1 text-2xl font-semibold text-slate-900">{{ $rows->count() }}</div>
            </div>

            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 shadow-sm">
                <div class="text-xs font-medium text-emerald-700">New</div>
                <div class="mt-1 text-2xl font-semibold text-emerald-800">{{ $newCount }}</div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 shadow-sm">
                <div class="text-xs font-medium text-slate-600">Exists</div>
                <div class="mt-1 text-2xl font-semibold text-slate-800">{{ $existsCount }}</div>
            </div>

            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 shadow-sm">
                <div class="text-xs font-medium text-amber-700">Conflict</div>
                <div class="mt-1 text-2xl font-semibold text-amber-800">{{ $conflictCount }}</div>
            </div>
        </div>

        <form
            method="POST"
            action="{{ route('scheduling.holiday-events.import.store') }}"
            x-data="{
                scopeMode: @js(old('scope_mode', $scopeMode)),
                selectAllNew: true,
                toggleAllNew() {
                    document.querySelectorAll('[data-import-new-row]').forEach((checkbox) => {
                        checkbox.checked = this.selectAllNew;
                    });
                }
            }"
            x-init="toggleAllNew()"
            class="space-y-5"
        >
            @csrf

            <input type="hidden" name="year" value="{{ $year }}">
            <input type="hidden" name="source" value="{{ $source }}">

            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="grid gap-4 lg:grid-cols-[220px_minmax(0,1fr)]">
                    <x-ui.field label="Default Scope" :error="$errors->first('scope_mode')">
                        <select
                            name="scope_mode"
                            x-model="scopeMode"
                            class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                        >
                            <option value="all">All Branches</option>
                            <option value="selected">Selected Branches</option>
                        </select>
                    </x-ui.field>

                    <div x-show="scopeMode === 'selected'" style="display:none;">
                        <x-ui.field label="Branches" :error="$errors->first('branch_ids')">
                            <div class="grid gap-2 rounded-2xl border border-slate-200 bg-slate-50 p-3 md:grid-cols-3">
                                @foreach($branches as $branch)
                                    <label class="flex items-center gap-2 rounded-xl bg-white px-3 py-2 text-sm text-slate-700 ring-1 ring-slate-200">
                                        <input
                                            type="checkbox"
                                            name="branch_ids[]"
                                            value="{{ $branch->branch_id }}"
                                            class="rounded border-slate-300"
                                            @checked(in_array($branch->branch_id, old('branch_ids', [])))
                                        >
                                        <span>{{ $branch->branch_name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </x-ui.field>
                    </div>
                </div>

                <div class="mt-4 flex items-center justify-between rounded-2xl border border-indigo-100 bg-indigo-50 px-4 py-3">
                    <div>
                        <div class="text-sm font-semibold text-indigo-900">
                            Import policy
                        </div>
                        <div class="mt-1 text-xs text-indigo-700">
                            Tahap awal hanya mengimport baris dengan status NEW. EXISTS dan CONFLICT akan dilewati. Calendarific digunakan sebagai source utama.
                        </div>
                    </div>

                    <label class="flex items-center gap-2 text-sm font-medium text-indigo-800">
                        <input
                            type="checkbox"
                            x-model="selectAllNew"
                            x-on:change="toggleAllNew()"
                            class="rounded border-indigo-300"
                        >
                        Select all NEW
                    </label>
                </div>
            </div>

            <x-ui.table-shell>
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="w-10 px-4 py-3 text-left"></th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Date
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Google Name
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Suggested Name
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Code
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Day Type
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Status
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach($rows as $index => $row)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 align-top">
                                    @if($row['status'] === 'NEW')
                                        <input
                                            type="checkbox"
                                            data-import-new-row
                                            name="selected_rows[{{ $index }}][enabled]"
                                            value="1"
                                            class="rounded border-slate-300"
                                        >
                                    @else
                                        <span class="text-xs text-slate-400">—</span>
                                    @endif

                                    @foreach($row as $key => $value)
                                        <input
                                            type="hidden"
                                            name="selected_rows[{{ $index }}][{{ $key }}]"
                                            value="{{ $value }}"
                                        >
                                    @endforeach
                                </td>

                                <td class="px-4 py-3 align-top text-slate-700">
                                    {{ $row['holiday_date'] }}
                                </td>

                                <td class="px-4 py-3 align-top text-slate-600">
                                    {{ $row['original_name'] }}
                                </td>

                                <td class="px-4 py-3 align-top font-medium text-slate-900">
                                    {{ $row['suggested_name'] }}
                                </td>

                                <td class="px-4 py-3 align-top text-xs text-slate-500">
                                    {{ $row['holiday_code'] }}
                                </td>

                                <td class="px-4 py-3 align-top">
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                                        {{ $row['day_type_code'] }}
                                    </span>
                                </td>

                                <td class="px-4 py-3 align-top">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $statusClasses[$row['status']] ?? 'bg-slate-50 text-slate-600 ring-slate-200' }}">
                                        {{ $row['status'] }}
                                    </span>
                                    <div class="mt-1 text-xs text-slate-500">
                                        {{ $row['status_note'] }}
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-ui.table-shell>

            <div class="sticky bottom-4 z-10 rounded-3xl border border-slate-200 bg-white/95 p-4 shadow-lg backdrop-blur">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div class="text-sm font-semibold text-slate-900">
                            Import selected holidays
                        </div>
                        <div class="mt-1 text-xs text-slate-500">
                            Holiday akan masuk ke Holiday Calendar, lalu branch calendar existing akan ikut tersinkron.
                        </div>
                    </div>

                    <div class="flex justify-end gap-3">
                        <x-ui.button
                            type="button"
                            variant="ghost"
                            onclick="window.location='{{ route('scheduling.holiday-events.index') }}'"
                        >
                            Cancel
                        </x-ui.button>

                        <x-ui.button type="submit" variant="primary">
                            Import Selected
                        </x-ui.button>
                    </div>
                </div>
            </div>
        </form>
    @endif
</div>
@endsection