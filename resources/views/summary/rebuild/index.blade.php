@extends('layouts.app')

@section('content')
<div class="space-y-6">

    <x-ui.page-header
        title="Summary Rebuild"
        subtitle="Rebuild monthly summary, period summary, obligation, atau full payroll pipeline."
        :breadcrumbs="[
            ['label' => 'Summary'],
            ['label' => 'Rebuild'],
        ]"
    />

    {{-- SUCCESS --}}
    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    {{-- ERROR --}}
    @if(session('error'))
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            {{ session('error') }}
        </div>
    @endif

    {{-- VALIDATION --}}
    @if($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <div class="font-medium">Terdapat error:</div>
            <ul class="mt-2 list-disc pl-5 space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- SELECT PERIOD --}}
    <x-ui.section-card
        title="Payroll Period Selector"
        subtitle="Pilih payroll period, semua aksi rebuild akan menggunakan period ini."
    >
        <div class="max-w-sm">
            <label class="mb-2 block text-sm font-medium text-slate-700">
                Payroll Period
            </label>

            <select
                id="payroll_period_id"
                class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-sky-200"
            >
                <option value="">-- Select Payroll Period --</option>
                @foreach($payrollPeriods as $period)
                    <option value="{{ $period->payroll_period_id }}">
                        {{ $period->period_code }}
                    </option>
                @endforeach
            </select>
        </div>
    </x-ui.section-card>

    {{-- ACTION CARDS --}}
    <div class="grid gap-6 xl:grid-cols-4">

        {{-- MONTHLY --}}
        <x-ui.section-card
            title="Monthly Summary"
            subtitle="Rebuild attendance_monthly_summary."
        >
            <form method="POST" action="{{ route('summary.rebuild.monthly') }}" class="js-summary-form space-y-4">
                @csrf
                <input type="hidden" name="payroll_period_id" class="js-payroll-period-id">

                <div class="text-sm text-slate-500">
                    Layer paling dasar untuk agregasi bulanan.
                </div>

                <div class="flex justify-end">
                    <x-ui.button type="submit">
                        Rebuild Monthly
                    </x-ui.button>
                </div>
            </form>
        </x-ui.section-card>

        {{-- PERIOD --}}
        <x-ui.section-card
            title="Period Summary"
            subtitle="Rebuild attendance_period_summaries."
        >
            <form method="POST" action="{{ route('summary.rebuild.period') }}" class="js-summary-form space-y-4">
                @csrf
                <input type="hidden" name="payroll_period_id" class="js-payroll-period-id">

                <div class="text-sm text-slate-500">
                    Menghasilkan summary per employee per payroll period.
                </div>

                <div class="flex justify-end">
                    <x-ui.button type="submit">
                        Rebuild Period
                    </x-ui.button>
                </div>
            </form>
        </x-ui.section-card>

        {{-- OBLIGATION --}}
        <x-ui.section-card
            title="Obligation"
            subtitle="Rebuild employee_period_obligations."
        >
            <form method="POST" action="{{ route('summary.rebuild.obligation') }}" class="js-summary-form space-y-4">
                @csrf
                <input type="hidden" name="payroll_period_id" class="js-payroll-period-id">

                <div class="text-sm text-amber-600">
                    Digunakan untuk work pattern obligation (weekday + sabtu, dll).
                </div>

                <div class="flex justify-end">
                    <x-ui.button type="submit" variant="secondary">
                        Rebuild Obligation
                    </x-ui.button>
                </div>
            </form>
        </x-ui.section-card>

        {{-- FULL PIPELINE --}}
        <x-ui.section-card
            title="Payroll Pipeline"
            subtitle="Full rebuild: summary → obligation → result → amount."
        >
            <form method="POST" action="{{ route('summary.rebuild.payroll') }}" class="js-summary-form space-y-4">
                @csrf
                <input type="hidden" name="payroll_period_id" class="js-payroll-period-id">

                <div class="text-sm text-rose-600">
                    ⚠️ Akan overwrite semua hasil payroll untuk period ini.
                </div>

                <div class="flex justify-end">
                    <x-ui.button
                        type="submit"
                        onclick="return confirm('Rebuild full payroll pipeline?')"
                    >
                        Rebuild Payroll
                    </x-ui.button>
                </div>
            </form>
        </x-ui.section-card>

    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    const select = document.getElementById('payroll_period_id');
    const forms = document.querySelectorAll('.js-summary-form');

    function sync() {
        forms.forEach(form => {
            form.querySelector('.js-payroll-period-id').value = select.value;
        });
    }

    function validate(e) {
        if (!select.value) {
            e.preventDefault();
            alert('Pilih payroll period terlebih dahulu');
            select.focus();
        }
    }

    sync();

    select.addEventListener('change', sync);

    forms.forEach(f => f.addEventListener('submit', validate));

});
</script>
@endpush