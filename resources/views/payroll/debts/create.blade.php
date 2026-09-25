@extends('layouts.app')

@section('title', 'Add Employee Debt')

@section('content')
<div
    class="space-y-6"
    x-data="employeeDebtCreateForm({
        old: {
            use_custom_debt_code: @js((bool) old('use_custom_debt_code')),
        }
    })"
>
    <x-ui.page-header
        title="Add Employee Debt"
        subtitle="Buat hutang baru per kasus. Debt code akan digenerate otomatis kecuali kamu memilih custom code."
        :breadcrumbs="[
            ['label' => 'Payroll'],
            ['label' => 'Employee Debts', 'url' => route('payroll.debts.index')],
            ['label' => 'Create'],
        ]"
    >
        <x-slot:actions>
            <a
                href="{{ route('payroll.debts.index') }}"
                class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
            >
                Back
            </a>
        </x-slot:actions>
    </x-ui.page-header>

    @if(session('error'))
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            {{ session('error') }}
        </div>
    @endif

    <x-ui.page-section
        title="Debt Form"
        subtitle="Isi data inti hutang dulu. Custom debt code hanya perlu dipakai jika memang dibutuhkan."
    >
        <form method="POST" action="{{ route('payroll.debts.store') }}" class="grid gap-6 lg:grid-cols-2">
            @csrf

            <div class="lg:col-span-2 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <div class="text-sm font-semibold text-slate-900">1. Basic Context</div>
                <div class="mt-1 text-sm text-slate-500">
                    Pilih employee, nama hutang, kategori, tanggal, dan nominal awal.
                </div>

                <div class="mt-4 grid gap-6 lg:grid-cols-2">
                    <div class="lg:col-span-2">
                        <x-ui.employee-combobox
                            name="emp_id"
                            :employees="$employees"
                            :selected="old('emp_id')"
                            label="Employee"
                            :error="$errors->first('emp_id')"
                        />
                    </div>

                    <x-ui.field label="Debt Name" :error="$errors->first('debt_name')">
                        <input
                            type="text"
                            name="debt_name"
                            value="{{ old('debt_name') }}"
                            placeholder="Contoh: Kerusakan Monitor 24 inci"
                            class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                        >
                    </x-ui.field>

                    <x-ui.field label="Debt Category" :error="$errors->first('debt_category_code')">
                        <select name="debt_category_code" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                            <option value="">Pilih category</option>
                            @foreach($categoryOptions as $category)
                                <option value="{{ $category }}" @selected(old('debt_category_code') === $category)>
                                    {{ $category }}
                                </option>
                            @endforeach
                        </select>
                    </x-ui.field>

                    <x-ui.field label="Origin Date" :error="$errors->first('origin_date')">
                        <input
                            type="date"
                            name="origin_date"
                            value="{{ old('origin_date', now()->toDateString()) }}"
                            class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                        >
                    </x-ui.field>

                    <x-ui.field label="Original Amount" :error="$errors->first('original_amount')">
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            name="original_amount"
                            value="{{ old('original_amount') }}"
                            class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                        >
                    </x-ui.field>
                </div>
            </div>

            <div class="lg:col-span-2 rounded-2xl border border-slate-200 bg-white p-4">
                <div class="text-sm font-semibold text-slate-900">2. Debt Code</div>
                <div class="mt-1 text-sm text-slate-500">
                    Secara default debt code akan digenerate otomatis oleh sistem.
                </div>

                <div class="mt-4 space-y-4">
                    <x-ui.field label="Debt Code Option">
                        <label class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 px-4 py-3">
                            <input type="checkbox" name="use_custom_debt_code" value="1" x-model="useCustomCode">
                            <span class="text-sm text-slate-700">Use custom debt code</span>
                        </label>
                    </x-ui.field>

                    <div x-show="!useCustomCode" class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600" style="display: none;">
                        Debt code akan digenerate otomatis, misalnya:
                        <span class="font-semibold text-slate-800">DEBT-{{ now()->format('Y-m') }}-0001</span>
                    </div>

                    <div x-show="useCustomCode" x-transition style="display: none;">
                        <x-ui.field label="Custom Debt Code" :error="$errors->first('debt_code')">
                            <input
                                type="text"
                                name="debt_code"
                                value="{{ old('debt_code') }}"
                                placeholder="Contoh: DEBT-2026-05-0007"
                                class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                            >
                        </x-ui.field>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-2 rounded-2xl border border-slate-200 bg-white p-4">
                <div class="text-sm font-semibold text-slate-900">3. Source & Notes</div>
                <div class="mt-1 text-sm text-slate-500">
                    Opsional, tapi berguna untuk audit trail.
                </div>

                <div class="mt-4 grid gap-6 lg:grid-cols-2">
                    <x-ui.field label="Source Type" :error="$errors->first('source_type_code')">
                        <select name="source_type_code" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                            <option value="">Tanpa source type</option>
                            @foreach($sourceTypes as $sourceType)
                                <option value="{{ $sourceType->source_type_code }}" @selected(old('source_type_code') === $sourceType->source_type_code)>
                                    {{ $sourceType->source_type_name }} · {{ $sourceType->source_type_code }}
                                </option>
                            @endforeach
                        </select>
                    </x-ui.field>

                    <x-ui.field label="Source Ref ID" :error="$errors->first('source_ref_id')">
                        <input
                            type="text"
                            name="source_ref_id"
                            value="{{ old('source_ref_id') }}"
                            placeholder="Contoh: MANUAL_DAMAGE_20260501"
                            class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                        >
                    </x-ui.field>

                    <div class="lg:col-span-2">
                        <x-ui.field label="Notes" :error="$errors->first('notes')">
                            <textarea
                                name="notes"
                                rows="4"
                                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                                placeholder="Catatan tambahan..."
                            >{{ old('notes') }}</textarea>
                        </x-ui.field>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3 lg:col-span-2">
                <x-ui.button type="submit">Save</x-ui.button>
                <x-ui.button type="button" variant="ghost" onclick="window.location='{{ route('payroll.debts.index') }}'">Cancel</x-ui.button>
            </div>
        </form>
    </x-ui.page-section>
</div>

<script>
    function employeeDebtCreateForm(config) {
        return {
            useCustomCode: !!config.old.use_custom_debt_code,
        }
    }
</script>
@endsection