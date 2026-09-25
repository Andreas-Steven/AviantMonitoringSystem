<div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Period Code</label>
            <input type="text" name="period_code"
                value="{{ old('period_code', $payrollPeriod->period_code ?? '') }}"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
            @error('period_code') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Status</label>
            <select name="payroll_period_status_code" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                <option value="">Select status</option>
                @foreach($statuses as $status)
                    <option value="{{ $status->payroll_period_status_code }}"
                        @selected(old('payroll_period_status_code', $payrollPeriod->payroll_period_status_code ?? '') === $status->payroll_period_status_code)>
                        {{ $status->payroll_period_status_name }}
                    </option>
                @endforeach
            </select>
            @error('payroll_period_status_code') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Start Date</label>
            <input type="date" name="period_start_date"
                value="{{ old('period_start_date', isset($payrollPeriod->period_start_date) ? $payrollPeriod->period_start_date->format('Y-m-d') : '') }}"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
            @error('period_start_date') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">End Date</label>
            <input type="date" name="period_end_date"
                value="{{ old('period_end_date', isset($payrollPeriod->period_end_date) ? $payrollPeriod->period_end_date->format('Y-m-d') : '') }}"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
            @error('period_end_date') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Payroll Year</label>
            <input type="number" min="2000" max="2100" name="payroll_year"
                value="{{ old('payroll_year', $payrollPeriod->payroll_year ?? now()->year) }}"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
            @error('payroll_year') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Payroll Month</label>
            <input type="number" min="1" max="12" name="payroll_month"
                value="{{ old('payroll_month', $payrollPeriod->payroll_month ?? now()->month) }}"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
            @error('payroll_month') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div class="md:col-span-2">
            <label class="mb-2 block text-sm font-medium text-slate-700">Notes</label>
            <textarea name="notes" rows="4"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">{{ old('notes', $payrollPeriod->notes ?? '') }}</textarea>
            @error('notes') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>
</div>

<div class="flex items-center gap-3">
    <button type="submit" class="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-medium text-white hover:bg-slate-800">
        Save
    </button>
    <a href="{{ route('scheduling.payroll-periods.index') }}" class="rounded-2xl border border-slate-300 px-5 py-3 text-sm font-medium hover:bg-slate-50">
        Cancel
    </a>
</div>