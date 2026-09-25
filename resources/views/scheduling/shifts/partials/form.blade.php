@php
    $initialStartTime = old('start_time', isset($shift->start_time) ? substr($shift->start_time, 0, 5) : '');
    $initialEndTime = old('end_time', isset($shift->end_time) ? substr($shift->end_time, 0, 5) : '');
    $initialBreakMin = old('break_min', $shift->break_min ?? 0);
    $initialCrossDay = (string) old('cross_day_flag', $shift->cross_day_flag ?? '0');
    $initialDefaultWorkMin = old('default_work_min', $shift->default_work_min ?? 0);
@endphp

<div
    class="space-y-5"
    x-data="{
        startTime: @js($initialStartTime),
        endTime: @js($initialEndTime),
        breakMin: Number(@js($initialBreakMin)) || 0,
        crossDay: @js($initialCrossDay) === '1',
        defaultWorkMin: Number(@js($initialDefaultWorkMin)) || 0,

        timeToMinutes(value) {
            if (!value || !value.includes(':')) return null;

            const [hour, minute] = value.split(':').map(Number);

            if (Number.isNaN(hour) || Number.isNaN(minute)) return null;

            return (hour * 60) + minute;
        },

        calculateWorkMinutes() {
            const start = this.timeToMinutes(this.startTime);
            let end = this.timeToMinutes(this.endTime);
            const breakValue = Math.max(0, Number(this.breakMin) || 0);

            if (start === null || end === null) {
                this.defaultWorkMin = 0;
                return;
            }

            if (this.crossDay && end <= start) {
                end += 24 * 60;
            }

            const duration = Math.max(0, end - start);
            this.defaultWorkMin = Math.max(0, duration - breakValue);
        },

        formatHours(minutes) {
            const safeMinutes = Math.max(0, Number(minutes) || 0);
            const hours = Math.floor(safeMinutes / 60);
            const mins = safeMinutes % 60;

            if (hours === 0) return `${mins} min`;
            if (mins === 0) return `${hours}h`;

            return `${hours}h ${mins}m`;
        }
    }"
    x-init="calculateWorkMinutes()"
    x-effect="calculateWorkMinutes()"
>
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="grid gap-6 md:grid-cols-2">
            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">Shift Code</label>
                <input
                    type="text"
                    name="shift_code"
                    value="{{ old('shift_code', $shift->shift_code ?? '') }}"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                >
                @error('shift_code') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">Shift Name</label>
                <input
                    type="text"
                    name="shift_name"
                    value="{{ old('shift_name', $shift->shift_name ?? '') }}"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                >
                @error('shift_name') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">Start Time</label>
                <input
                    type="time"
                    name="start_time"
                    x-model="startTime"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                >
                @error('start_time') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">End Time</label>
                <input
                    type="time"
                    name="end_time"
                    x-model="endTime"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                >
                @error('end_time') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">Break Minutes</label>
                <input
                    type="number"
                    min="0"
                    name="break_min"
                    x-model.number="breakMin"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                >
                @error('break_min') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">Default Work Minutes</label>

                <input type="hidden" name="default_work_min" :value="defaultWorkMin">

                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="text-sm font-semibold text-slate-900" x-text="defaultWorkMin + ' min'"></div>
                            <div class="mt-0.5 text-xs text-slate-500" x-text="formatHours(defaultWorkMin)"></div>
                        </div>

                        <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-600 ring-1 ring-slate-200">
                            Auto calculated
                        </span>
                    </div>
                </div>

                @error('default_work_min') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror

                <p class="mt-2 text-xs text-slate-500">
                    Dihitung otomatis dari durasi shift dikurangi break. Nilai final tetap dihitung ulang di backend saat disimpan.
                </p>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">Cross Day</label>
                <select
                    name="cross_day_flag"
                    x-model="crossDay"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                >
                    <option value="0">No</option>
                    <option value="1">Yes</option>
                </select>
                @error('cross_day_flag') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror

                <p class="mt-2 text-xs text-slate-500">
                    Pilih Yes untuk shift melewati tengah malam, contoh 22:00–06:00.
                </p>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">Status</label>
                <select
                    name="active"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                >
                    <option value="1" @selected((string) old('active', $shift->active ?? '1') === '1')>Active</option>
                    <option value="0" @selected((string) old('active', $shift->active ?? '1') === '0')>Inactive</option>
                </select>
                @error('active') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div class="md:col-span-2">
                <label class="mb-2 block text-sm font-medium text-slate-700">Notes</label>
                <textarea
                    name="notes"
                    rows="4"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                >{{ old('notes', $shift->notes ?? '') }}</textarea>
                @error('notes') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    <div class="flex items-center gap-3">
        <button
            type="submit"
            class="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-medium text-white hover:bg-slate-800"
        >
            Save
        </button>

        <a
            href="{{ route('scheduling.shifts.index') }}"
            class="rounded-2xl border border-slate-300 px-5 py-3 text-sm font-medium hover:bg-slate-50"
        >
            Cancel
        </a>
    </div>
</div>