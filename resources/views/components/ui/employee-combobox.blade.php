@props([
    'name' => 'employee_id',
    'employees' => collect(),
    'selected' => null,
    'label' => 'Employee',
    'placeholder' => 'Cari employee...',
    'error' => null,
])

@php
    $normalizedEmployees = collect($employees)
        ->map(function ($employee) {
            return [
                'id' => (int) $employee->emp_id,
                'emp_code' => (string) $employee->emp_code,
                'full_name' => (string) $employee->full_name,
                'biometric_code' => (string) ($employee->biometric_code ?? ''),
                'search' => strtolower(trim(implode(' ', [
                    $employee->emp_code,
                    $employee->full_name,
                    $employee->biometric_code ?? '',
                ]))),
            ];
        })
        ->values();

    $selectedEmployee = $normalizedEmployees->firstWhere('id', (int) ($selected ?: 0));
@endphp

<x-ui.field :label="$label" :error="$error">
    <div
        {{
            $attributes->merge([
                'class' => 'relative',
            ])
        }}
        x-data="{
            open: false,
            query: @js($selectedEmployee ? ($selectedEmployee['emp_code'].' · '.$selectedEmployee['full_name']) : ''),
            value: @js($selected ? (int) $selected : null),
            employees: @js($normalizedEmployees),

            get filteredEmployees() {
                const q = (this.query || '').toLowerCase().trim();

                if (!q) {
                    return this.employees.slice(0, 12);
                }

                return this.employees
                    .filter(employee => employee.search.includes(q))
                    .slice(0, 12);
            },

            selectEmployee(employee) {
                this.value = employee.id;
                this.query = `${employee.emp_code} · ${employee.full_name}`;
                this.open = false;
            },

            clearEmployee() {
                this.value = null;
                this.query = '';
                this.open = false;
            },

            init() {
                this.$watch('value', (newValue) => {
                    if (newValue === null || newValue === '' || Number.isNaN(Number(newValue))) {
                        this.query = '';
                        return;
                    }

                    const selected = this.employees.find(employee => Number(employee.id) === Number(newValue));

                    if (selected) {
                        this.query = `${selected.emp_code} · ${selected.full_name}`;
                    }
                });
            }
        }"
        x-modelable="value"
        @click.away="open = false"
    >
        <input type="hidden" name="{{ $name }}" x-model="value">

        <div class="flex items-center gap-2">
            <input
                type="text"
                x-model="query"
                @focus="open = true"
                @input="open = true"
                placeholder="{{ $placeholder }}"
                class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                autocomplete="off"
            >

            <button
                type="button"
                @click="clearEmployee()"
                class="inline-flex shrink-0 items-center rounded-2xl border border-slate-200 px-3 py-2 text-xs font-medium text-slate-600 hover:bg-slate-50"
            >
                Clear
            </button>
        </div>

        <div
            x-show="open"
            x-transition
            class="absolute z-30 mt-2 w-full overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl"
            style="display: none;"
        >
            <div class="max-h-72 overflow-y-auto py-2">
                <template x-if="filteredEmployees.length === 0">
                    <div class="px-4 py-3 text-sm text-slate-500">
                        Tidak ada employee yang cocok.
                    </div>
                </template>

                <template x-for="employee in filteredEmployees" :key="employee.id">
                    <button
                        type="button"
                        @click="selectEmployee(employee)"
                        class="flex w-full items-start justify-between gap-3 px-4 py-3 text-left hover:bg-slate-50"
                    >
                        <span>
                            <span
                                class="block text-sm font-medium text-slate-900"
                                x-text="employee.emp_code + ' · ' + employee.full_name"
                            ></span>
                            <span
                                class="block text-xs text-slate-500"
                                x-text="employee.biometric_code ? ('Biometric: ' + employee.biometric_code) : 'Biometric: -'"
                            ></span>
                        </span>

                        <span
                            x-show="Number(value) === Number(employee.id)"
                            class="rounded-full bg-emerald-50 px-2 py-1 text-[11px] font-medium text-emerald-700"
                            style="display: none;"
                        >
                            Selected
                        </span>
                    </button>
                </template>
            </div>
        </div>

        <div class="mt-2 text-xs text-slate-500">
            Cari berdasarkan emp code, nama employee, atau biometric code.
        </div>
    </div>
</x-ui.field>