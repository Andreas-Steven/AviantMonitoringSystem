@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Add Payroll Deduction Type"
        subtitle="Tambahkan master jenis potongan payroll baru."
        :breadcrumbs="[
            ['label' => 'Payroll'],
            ['label' => 'Deduction Types', 'url' => route('payroll.deduction-types.index')],
            ['label' => 'Create'],
        ]"
    />

    <x-ui.page-section
        title="Deduction Type Form"
        subtitle="Gunakan code yang stabil karena akan dipakai oleh transaksi dan report."
    >
        <form method="POST" action="{{ route('payroll.deduction-types.store') }}" class="grid gap-6 lg:grid-cols-2">
            @csrf

            <x-ui.field label="Deduction Code" :error="$errors->first('deduction_code')">
                <input
                    type="text"
                    name="deduction_code"
                    value="{{ old('deduction_code') }}"
                    placeholder="Contoh: DAMAGE_CHARGE"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                >
            </x-ui.field>

            <x-ui.field label="Deduction Name" :error="$errors->first('deduction_name')">
                <input
                    type="text"
                    name="deduction_name"
                    value="{{ old('deduction_name') }}"
                    placeholder="Contoh: Damage Charge"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                >
            </x-ui.field>

            <x-ui.field label="Category" :error="$errors->first('category_code')">
                <select name="category_code" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                    <option value="">Pilih category</option>
                    @foreach($categories as $category)
                        <option value="{{ $category }}" @selected(old('category_code') === $category)>
                            {{ $category }}
                        </option>
                    @endforeach
                </select>
            </x-ui.field>

            <div class="grid gap-4 md:grid-cols-2">
                <x-ui.field label="Debt Forming Default">
                    <label class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 px-4 py-3">
                        <input type="checkbox" name="debt_forming_default_flag" value="1" @checked(old('debt_forming_default_flag'))>
                        <span class="text-sm text-slate-700">Default membentuk hutang</span>
                    </label>
                </x-ui.field>

                <x-ui.field label="Active">
                    <label class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 px-4 py-3">
                        <input type="checkbox" name="active" value="1" @checked(old('active', '1'))>
                        <span class="text-sm text-slate-700">Active</span>
                    </label>
                </x-ui.field>
            </div>

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

            <div class="flex items-center gap-3 lg:col-span-2">
                <x-ui.button type="submit">Save</x-ui.button>
                <x-ui.button type="button" variant="ghost" onclick="window.location='{{ route('payroll.deduction-types.index') }}'">Cancel</x-ui.button>
            </div>
        </form>
    </x-ui.page-section>
</div>
@endsection