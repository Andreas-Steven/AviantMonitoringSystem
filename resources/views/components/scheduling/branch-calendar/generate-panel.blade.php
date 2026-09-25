@props([
    'activeBranch' => null,
    'activePeriod' => null,
    'filters' => [],
    'canManage' => false,
    'hasCalendarData' => false,
])

@if ($canManage && $activeBranch && $activePeriod)
    <div id="generate-calendar-panel">
        <x-ui.section-card
            title="{{ $hasCalendarData ? 'Generate Missing / Rebuild Calendar' : 'Generate Missing Calendar' }}"
            subtitle="{{ $hasCalendarData
                ? 'Calendar sudah tersedia. Generate Missing hanya menambah tanggal yang belum ada. Rebuild akan menulis ulang periode ini dan sebaiknya hanya dipakai untuk maintenance.'
                : 'Calendar belum tersedia untuk branch dan payroll period ini. Generate Missing akan membuat tanggal periode ini tanpa overwrite data lama.' }}"
        >
            <form
                id="branch-calendar-generate-form"
                method="POST"
                action="{{ route('scheduling.branch-calendars.generate') }}"
                class="space-y-4"
                x-data="{ overwrite: false }"
            >
                @csrf

                <input type="hidden" name="branch_id" value="{{ $activeBranch['id'] }}">
                <input type="hidden" name="payroll_period_id" value="{{ $activePeriod['id'] }}">
                <input type="hidden" name="period_code" value="{{ $activePeriod['code'] }}">
                <input type="hidden" name="view_mode" value="{{ $filters['view_mode'] ?? 'grid' }}">
                <input type="hidden" name="overwrite_existing" :value="overwrite ? 1 : 0">

                <div class="grid gap-3 md:grid-cols-3">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Branch</div>
                        <div class="mt-1 text-sm font-semibold text-slate-900">{{ $activeBranch['name'] }}</div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Period</div>
                        <div class="mt-1 text-sm font-semibold text-slate-900">{{ $activePeriod['code'] }}</div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Date Range</div>
                        <div class="mt-1 text-sm font-semibold text-slate-900">
                            {{ $activePeriod['date_from'] }} — {{ $activePeriod['date_to'] }}
                        </div>
                    </div>
                </div>

                @if($hasCalendarData)
                    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3">
                        <label class="flex items-start gap-3">
                            <input
                                type="checkbox"
                                x-model="overwrite"
                                class="mt-1 rounded border-amber-300 text-amber-600 focus:ring-amber-500"
                            >

                            <span>
                                <span class="block text-sm font-semibold text-amber-900">
                                    Rebuild Calendar for This Period
                                </span>
                                <span class="mt-1 block text-xs leading-5 text-amber-700">
                                    Jika dicentang, existing calendar rows pada periode ini akan ditulis ulang berdasarkan rule terbaru.
                                    Gunakan hanya untuk maintenance, perbaikan data, atau perubahan rule besar.
                                </span>
                            </span>
                        </label>
                    </div>
                @else
                    <div class="rounded-2xl border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-800">
                        Generate ini hanya membuat calendar untuk periode yang belum tersedia.
                    </div>
                @endif

                <div class="flex items-center justify-end border-t border-slate-200 pt-4">
                    <button
                        type="submit"
                        class="inline-flex items-center rounded-xl px-4 py-2 text-sm font-semibold text-white"
                        :class="overwrite ? 'bg-amber-600 hover:bg-amber-500' : 'bg-indigo-600 hover:bg-indigo-500'"
                    >
                        <span x-text="overwrite ? 'Rebuild Calendar' : 'Generate Missing'"></span>
                    </button>
                </div>
            </form>
        </x-ui.section-card>
    </div>
@endif