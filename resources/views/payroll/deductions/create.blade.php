@extends('layouts.app')

@section('content')
<div
    class="space-y-6"
    x-data="payrollDeductionForm({
        debts: @js($employeeDebts),
        old: {
            emp_id: @js(old('emp_id')),
            qty: @js(old('qty', '1')),
            rate_amount: @js(old('rate_amount', '0')),
            amount: @js(old('amount')),
            debt_forming_flag: @js((bool) old('debt_forming_flag')),
            employee_debt_id: @js(old('employee_debt_id')),
            create_new_debt: @js((bool) old('create_new_debt')),
            use_custom_debt_code: @js((bool) old('use_custom_debt_code')),
        }
    })"
>
    <x-ui.page-header
        title="Add Payroll Deduction"
        subtitle="Tambahkan potongan payroll manual. Debt integration hanya muncul bila memang diperlukan."
        :breadcrumbs="[
            ['label' => 'Payroll'],
            ['label' => 'Deductions', 'url' => route('payroll.deductions.index')],
            ['label' => 'Create'],
        ]"
    >
        <x-slot:actions>
            <a
                href="{{ route('payroll.deductions.index') }}"
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
        title="Deduction Form"
        subtitle="Mulai dari data dasar deduction dulu. Bagian debt akan muncul otomatis hanya jika debt-forming dicentang."
    >
        <form method="POST" action="{{ route('payroll.deductions.store') }}" class="grid gap-6 lg:grid-cols-2">
            @csrf

            <div class="lg:col-span-2 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <div class="text-sm font-semibold text-slate-900">1. Basic Context</div>
                <div class="mt-1 text-sm text-slate-500">
                    Tentukan employee, payroll period, dan jenis deduction terlebih dahulu.
                </div>

                <div class="mt-4 grid gap-6 lg:grid-cols-2">
                    <div class="lg:col-span-2">
                        <x-ui.employee-combobox
                            name="emp_id"
                            :employees="$employees"
                            :selected="old('emp_id')"
                            label="Employee"
                            :error="$errors->first('emp_id')"
                            x-model="empId"
                        />
                    </div>

                    <x-ui.field label="Payroll Period" :error="$errors->first('payroll_period_id')">
                        <select name="payroll_period_id" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                            <option value="">Pilih payroll period</option>
                            @foreach($payrollPeriods as $period)
                                <option value="{{ $period->payroll_period_id }}" @selected((string) old('payroll_period_id') === (string) $period->payroll_period_id)>
                                    {{ $period->period_code }} · {{ \Illuminate\Support\Carbon::parse($period->period_start_date)->format('d M Y') }} - {{ \Illuminate\Support\Carbon::parse($period->period_end_date)->format('d M Y') }}
                                </option>
                            @endforeach
                        </select>
                    </x-ui.field>

                    <x-ui.field label="Deduction Type" :error="$errors->first('payroll_deduction_type_id')">
                        <select name="payroll_deduction_type_id" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                            <option value="">Pilih deduction type</option>
                            @foreach($deductionTypes as $type)
                                <option value="{{ $type->payroll_deduction_type_id }}" @selected((string) old('payroll_deduction_type_id') === (string) $type->payroll_deduction_type_id)>
                                    {{ $type->deduction_name }} · {{ $type->deduction_code }}
                                    @if($type->debt_forming_default_flag)
                                        · debt default
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </x-ui.field>
                </div>
            </div>

            <div class="lg:col-span-2 rounded-2xl border border-slate-200 bg-white p-4">
                <div class="text-sm font-semibold text-slate-900">2. Source & Description</div>
                <div class="mt-1 text-sm text-slate-500">
                    Opsional, tapi berguna untuk audit trail dan penjelasan konteks deduction.
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
                            placeholder="Contoh: ATTENDANCE_DAILY_123"
                            class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                        >
                    </x-ui.field>

                    <div class="lg:col-span-2">
                        <x-ui.field label="Description" :error="$errors->first('description')">
                            <input
                                type="text"
                                name="description"
                                value="{{ old('description') }}"
                                placeholder="Contoh: Cicilan monitor bulan pertama"
                                class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                            >
                        </x-ui.field>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-2 rounded-2xl border border-slate-200 bg-white p-4">
                <div class="text-sm font-semibold text-slate-900">3. Amount</div>
                <div class="mt-1 text-sm text-slate-500">
                    Isi qty dan rate untuk hitung otomatis. Isi final amount hanya jika ingin override.
                </div>

                <div class="mt-4 grid gap-6 lg:grid-cols-3">
                    <x-ui.field label="Qty" :error="$errors->first('qty')">
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            name="qty"
                            value="{{ old('qty', '1') }}"
                            x-model="qty"
                            class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                        >
                    </x-ui.field>

                    <x-ui.field label="Rate Amount" :error="$errors->first('rate_amount')">
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            name="rate_amount"
                            value="{{ old('rate_amount', '0') }}"
                            x-model="rateAmount"
                            class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                        >
                    </x-ui.field>

                    <x-ui.field label="Final Amount (optional override)" :error="$errors->first('amount')">
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            name="amount"
                            value="{{ old('amount') }}"
                            x-model="amountOverride"
                            placeholder="Kosongkan jika auto qty × rate"
                            class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                        >
                    </x-ui.field>
                </div>

                <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                    Preview final amount:
                    <span class="font-semibold text-slate-900" x-text="formattedFinalAmount"></span>
                </div>
            </div>

            <div class="lg:col-span-2 rounded-2xl border border-slate-200 bg-white p-4">
                <div class="text-sm font-semibold text-slate-900">4. Debt Option</div>
                <div class="mt-1 text-sm text-slate-500">
                    Centang hanya jika deduction ini memang terkait hutang karyawan.
                </div>

                <div class="mt-4">
                    <x-ui.field label="Debt Forming">
                        <label class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 px-4 py-3">
                            <input type="checkbox" name="debt_forming_flag" value="1" x-model="debtForming">
                            <span class="text-sm text-slate-700">Potongan ini termasuk debt-forming</span>
                        </label>
                    </x-ui.field>
                </div>
            </div>

            <template x-if="debtForming">
                <div class="lg:col-span-2 space-y-6">
                    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        Debt-forming aktif. Pilih salah satu:
                        <strong>link existing debt</strong> atau <strong>create new debt</strong>.
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-sm font-semibold text-slate-900">5. Debt Integration</div>
                        <div class="mt-1 text-sm text-slate-500">
                            Bagian ini hanya muncul karena debt-forming dicentang.
                        </div>

                        <div class="mt-4 grid gap-4 lg:grid-cols-2">
                            <button
                                type="button"
                                @click="chooseExistingDebt()"
                                :class="existingDebtMode
                                    ? 'border-sky-300 bg-sky-50 text-sky-800'
                                    : 'border-slate-200 bg-white text-slate-700'"
                                class="rounded-2xl border px-4 py-4 text-left transition"
                            >
                                <div class="font-semibold">Link Existing Debt</div>
                                <div class="mt-1 text-sm">
                                    Pakai deduction ini sebagai cicilan / pembayaran untuk hutang yang sudah ada.
                                </div>
                            </button>

                            <button
                                type="button"
                                @click="chooseNewDebt()"
                                :class="createNewDebt
                                    ? 'border-sky-300 bg-sky-50 text-sky-800'
                                    : 'border-slate-200 bg-white text-slate-700'"
                                class="rounded-2xl border px-4 py-4 text-left transition"
                            >
                                <div class="font-semibold">Create New Debt</div>
                                <div class="mt-1 text-sm">
                                    Buat hutang baru dari kasus ini, lalu payroll deduction akan ditautkan ke hutang baru tersebut.
                                </div>
                            </button>
                        </div>

                        <div x-show="existingDebtMode" x-transition class="mt-6 space-y-4" style="display: none;">
                            <x-ui.field label="Link Existing Debt (for payroll installment)" :error="$errors->first('employee_debt_id')">
                                <select
                                    name="employee_debt_id"
                                    x-model="employeeDebtId"
                                    class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                                >
                                    <option value="">Pilih debt existing</option>
                                    <template x-for="debt in filteredDebts" :key="debt.employee_debt_id">
                                        <option :value="debt.employee_debt_id" x-text="debt.option_label"></option>
                                    </template>
                                </select>
                                <p class="mt-2 text-xs text-slate-500">
                                    Hanya menampilkan debt OPEN milik employee yang dipilih.
                                </p>
                            </x-ui.field>

                            <template x-if="selectedDebt">
                                <div class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">
                                    <div class="font-semibold">Selected Debt Preview</div>
                                    <div class="mt-2">
                                        <span x-text="selectedDebt.debt_code"></span>
                                        ·
                                        <span x-text="selectedDebt.debt_name"></span>
                                        ·
                                        sisa
                                        <span x-text="formatMoney(selectedDebt.outstanding_amount)"></span>
                                    </div>
                                    <div class="mt-1 text-xs">
                                        Setelah installment ini, estimasi sisa:
                                        <span class="font-semibold" x-text="formatMoney(estimatedRemainingOutstanding)"></span>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div x-show="createNewDebt" x-transition class="mt-6 grid gap-6 lg:grid-cols-2" style="display: none;">
                            <input type="hidden" name="create_new_debt" value="0">
                            <input type="checkbox" name="create_new_debt" value="1" x-model="createNewDebt" class="hidden">

                            <div class="lg:col-span-2">
                                <x-ui.field label="Debt Code Option">
                                    <label class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 px-4 py-3">
                                        <input type="checkbox" name="use_custom_debt_code" value="1" x-model="useCustomCode">
                                        <span class="text-sm text-slate-700">Use custom debt code</span>
                                    </label>
                                </x-ui.field>

                                <div x-show="!useCustomCode" class="mt-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600" style="display: none;">
                                    Debt code akan digenerate otomatis oleh sistem.
                                </div>
                            </div>

                            <div x-show="useCustomCode" x-transition style="display: none;">
                                <x-ui.field label="Custom Debt Code" :error="$errors->first('new_debt_code')">
                                    <input
                                        type="text"
                                        name="new_debt_code"
                                        value="{{ old('new_debt_code') }}"
                                        placeholder="Contoh: DEBT-2026-05-0007"
                                        class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                                    >
                                </x-ui.field>
                            </div>

                            <x-ui.field label="New Debt Name" :error="$errors->first('new_debt_name')">
                                <input
                                    type="text"
                                    name="new_debt_name"
                                    value="{{ old('new_debt_name') }}"
                                    placeholder="Contoh: Kerusakan Keyboard"
                                    class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                                >
                            </x-ui.field>

                            <x-ui.field label="New Debt Category" :error="$errors->first('new_debt_category_code')">
                                <select name="new_debt_category_code" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                                    <option value="">Pilih category</option>
                                    @foreach($debtCategoryOptions as $category)
                                        <option value="{{ $category }}" @selected(old('new_debt_category_code') === $category)>
                                            {{ $category }}
                                        </option>
                                    @endforeach
                                </select>
                            </x-ui.field>

                            <x-ui.field label="Origin Date" :error="$errors->first('new_debt_origin_date')">
                                <input
                                    type="date"
                                    name="new_debt_origin_date"
                                    value="{{ old('new_debt_origin_date', now()->toDateString()) }}"
                                    class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                                >
                            </x-ui.field>

                            <div class="lg:col-span-2">
                                <x-ui.field label="Debt Notes" :error="$errors->first('new_debt_notes')">
                                    <textarea
                                        name="new_debt_notes"
                                        rows="3"
                                        class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                                    >{{ old('new_debt_notes') }}</textarea>
                                </x-ui.field>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <div class="lg:col-span-2 rounded-2xl border border-slate-200 bg-white p-4">
                <div class="text-sm font-semibold text-slate-900">6. Notes</div>
                <div class="mt-1 text-sm text-slate-500">
                    Opsional. Isi jika perlu penjelasan tambahan.
                </div>

                <div class="mt-4">
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

            <div class="flex items-center gap-3 lg:col-span-2">
                <x-ui.button type="submit">Save</x-ui.button>
                <x-ui.button type="button" variant="ghost" onclick="window.location='{{ route('payroll.deductions.index') }}'">Cancel</x-ui.button>
            </div>
        </form>
    </x-ui.page-section>
</div>

<script>
    function payrollDeductionForm(config) {
        return {
            debts: config.debts || [],
            empId: config.old.emp_id || '',
            qty: config.old.qty || '1',
            rateAmount: config.old.rate_amount || '0',
            amountOverride: config.old.amount || '',
            debtForming: !!config.old.debt_forming_flag,
            employeeDebtId: config.old.employee_debt_id || '',
            createNewDebt: !!config.old.create_new_debt,
            useCustomCode: !!config.old.use_custom_debt_code,
            existingDebtMode: !!config.old.employee_debt_id && !config.old.create_new_debt,

            init() {
                if (this.createNewDebt) {
                    this.existingDebtMode = false;
                }

                if (!this.debtForming) {
                    this.existingDebtMode = false;
                    this.createNewDebt = false;
                    this.employeeDebtId = '';
                    this.useCustomCode = false;
                }

                this.$watch('debtForming', (value) => {
                    if (!value) {
                        this.employeeDebtId = '';
                        this.createNewDebt = false;
                        this.existingDebtMode = false;
                        this.useCustomCode = false;
                    }
                });

                this.$watch('empId', () => {
                    if (this.employeeDebtId && !this.selectedDebt) {
                        this.employeeDebtId = '';
                    }
                });
            },

            chooseExistingDebt() {
                this.existingDebtMode = true;
                this.createNewDebt = false;
                this.useCustomCode = false;
            },

            chooseNewDebt() {
                this.createNewDebt = true;
                this.existingDebtMode = false;
                this.employeeDebtId = '';
            },

            get filteredDebts() {
                if (!this.empId) {
                    return [];
                }

                return this.debts.filter((debt) => Number(debt.emp_id) === Number(this.empId));
            },

            get selectedDebt() {
                return this.filteredDebts.find((debt) => Number(debt.employee_debt_id) === Number(this.employeeDebtId)) || null;
            },

            get finalAmount() {
                const qty = parseFloat(this.qty || 0);
                const rate = parseFloat(this.rateAmount || 0);
                const override = this.amountOverride !== '' && this.amountOverride !== null
                    ? parseFloat(this.amountOverride)
                    : null;

                if (override !== null && !Number.isNaN(override)) {
                    return override;
                }

                if (Number.isNaN(qty) || Number.isNaN(rate)) {
                    return 0;
                }

                return qty * rate;
            },

            get formattedFinalAmount() {
                return this.formatMoney(this.finalAmount);
            },

            get estimatedRemainingOutstanding() {
                if (!this.selectedDebt) {
                    return 0;
                }

                const remaining = Number(this.selectedDebt.outstanding_amount || 0) - Number(this.finalAmount || 0);
                return remaining > 0 ? remaining : 0;
            },

            formatMoney(value) {
                const number = Number(value || 0);

                return new Intl.NumberFormat('id-ID', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                }).format(Number.isNaN(number) ? 0 : number);
            },
        }
    }
</script>
@endsection