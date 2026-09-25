@php
    $prefill = $prefill ?? [];

    $selectedEmpId = old('emp_id', $attendanceException->emp_id ?? ($prefill['emp_id'] ?? ''));
    $selectedWorkDate = old(
        'work_date',
        isset($attendanceException) && $attendanceException->work_date
            ? $attendanceException->work_date->format('Y-m-d')
            : ($prefill['work_date'] ?? '')
    );
    $selectedExceptionType = old('exception_type_code', $attendanceException->exception_type_code ?? ($prefill['exception_type_code'] ?? ''));
    $selectedSourceType = old('source_type_code', $attendanceException->source_type_code ?? ($prefill['source_type_code'] ?? ''));
    $selectedSourceRefId = old('source_ref_id', $attendanceException->source_ref_id ?? ($prefill['source_ref_id'] ?? ''));
@endphp
<div
    x-data="attendanceExceptionForm({
        exceptionType: @js($selectedExceptionType),
        minutesValue: @js(old('minutes_value', $attendanceException->minutes_value ?? '')),
        timeValue: @js(old('time_value', isset($attendanceException) && $attendanceException->time_value ? $attendanceException->time_value->format('Y-m-d\TH:i') : '')),
        shiftIdValue: @js(old('shift_id_value', $attendanceException->shift_id_value ?? '')),
        statusValueCode: @js(old('status_value_code', $attendanceException->status_value_code ?? '')),
        reasonValue: @js(old('reason', $attendanceException->reason ?? '')),
        approvedBy: @js(old('approved_by', $attendanceException->approved_by ?? '')),
        approvedAt: @js(old('approved_at', isset($attendanceException) && $attendanceException->approved_at ? $attendanceException->approved_at->format('Y-m-d\TH:i') : '')),
    })"
    x-init="init()"
    class="space-y-6"
>
    <x-ui.page-section title="Exception Information" subtitle="Master exception information.">
        <div class="grid gap-4 md:grid-cols-2">
            <x-ui.field label="Employee" :error="$errors->first('emp_id')">
                <select name="emp_id" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                    <option value="">Pilih employee</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->emp_id }}" @selected((string) $selectedEmpId === (string) $employee->emp_id)>
                            {{ $employee->full_name }} ({{ $employee->emp_code }})
                        </option>
                    @endforeach
                </select>
            </x-ui.field>

            <x-ui.field label="Work Date" :error="$errors->first('work_date')">
                <input
                    type="date"
                    name="work_date"
                    value="{{ $selectedWorkDate }}"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                >
            </x-ui.field>

            <x-ui.field label="Exception Type" :error="$errors->first('exception_type_code')">
                <select
                    name="exception_type_code"
                    x-model="exceptionType"
                    @change="handleTypeChange()"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                >
                    <option value="">Pilih exception type</option>
                    @foreach($exceptionTypes as $type)
                        <option value="{{ $type->exception_type_code }}" @selected($selectedExceptionType === $type->exception_type_code)>
                            {{ $type->exception_type_name }} ({{ $type->exception_type_code }})
                        </option>
                    @endforeach
                </select>

                <p class="mt-2 text-xs text-slate-500" x-text="typeHelperText"></p>
            </x-ui.field>

            <x-ui.field label="Source Type" :error="$errors->first('source_type_code')">
                <select name="source_type_code" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                    <option value="">-</option>
                    @foreach($sourceTypes as $sourceType)
                        <option value="{{ $sourceType->source_type_code }}" @selected($selectedSourceType === $sourceType->source_type_code)>
                            {{ $sourceType->source_type_name }} ({{ $sourceType->source_type_code }})
                        </option>
                    @endforeach
                </select>
            </x-ui.field>
        </div>
    </x-ui.page-section>

    <x-ui.page-section title="Override Values" subtitle="Isi hanya field yang relevan untuk jenis exception tertentu.">
        <div class="grid gap-4 md:grid-cols-2">
            <div x-show="showMinutes" x-transition>
                <x-ui.field label="Minutes Value" :error="$errors->first('minutes_value')">
                    <input
                        type="number"
                        name="minutes_value"
                        x-model="minutesValue"
                        :class="requiredMinutes
                            ? 'w-full rounded-2xl border border-amber-400 bg-amber-50 px-4 py-2.5 text-sm'
                            : 'w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm'"
                        placeholder="Contoh: 15"
                    >
                    <p class="mt-2 text-xs" :class="requiredMinutes ? 'text-amber-700 font-medium' : 'text-slate-500'">
                        Dipakai untuk late dispensation, early out dispensation, atau overtime override.
                    </p>
                </x-ui.field>
            </div>

            <div x-show="showTime" x-transition>
                <x-ui.field label="Time Value" :error="$errors->first('time_value')">
                    <input
                        type="datetime-local"
                        name="time_value"
                        x-model="timeValue"
                        :class="requiredTime
                            ? 'w-full rounded-2xl border border-amber-400 bg-amber-50 px-4 py-2.5 text-sm'
                            : 'w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm'"
                    >
                    <p class="mt-2 text-xs" :class="requiredTime ? 'text-amber-700 font-medium' : 'text-slate-500'">
                        Dipakai untuk manual in atau manual out.
                    </p>
                </x-ui.field>
            </div>

            <div x-show="showShift" x-transition>
                <x-ui.field label="Shift Override" :error="$errors->first('shift_id_value')">
                    <select
                        name="shift_id_value"
                        x-model="shiftIdValue"
                        :class="requiredShift
                            ? 'w-full rounded-2xl border border-amber-400 bg-amber-50 px-4 py-2.5 text-sm'
                            : 'w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm'"
                    >
                        <option value="">-</option>
                        @foreach($shifts as $shift)
                            <option value="{{ $shift->shift_id }}" @selected((string) old('shift_id_value', $attendanceException->shift_id_value ?? '') === (string) $shift->shift_id)>
                                {{ $shift->shift_name }} ({{ $shift->shift_code }})
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-2 text-xs" :class="requiredShift ? 'text-amber-700 font-medium' : 'text-slate-500'">
                        Dipakai khusus untuk SHIFT_OVERRIDE.
                    </p>
                </x-ui.field>
            </div>

            <div x-show="showStatus" x-transition>
                <x-ui.field label="Status Override" :error="$errors->first('status_value_code')">
                    <select
                        name="status_value_code"
                        x-model="statusValueCode"
                        :class="requiredStatus
                            ? 'w-full rounded-2xl border border-amber-400 bg-amber-50 px-4 py-2.5 text-sm'
                            : 'w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm'"
                    >
                        <option value="">-</option>
                        @foreach($attendanceStatuses as $status)
                            <option value="{{ $status->attendance_status_code }}" @selected(old('status_value_code', $attendanceException->status_value_code ?? '') === $status->attendance_status_code)>
                                {{ $status->attendance_status_name }} ({{ $status->attendance_status_code }})
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-2 text-xs" :class="requiredStatus ? 'text-amber-700 font-medium' : 'text-slate-500'">
                        Dipakai untuk force present atau force absent.
                    </p>
                </x-ui.field>
            </div>
        </div>

        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <x-ui.field label="Reason" :error="$errors->first('reason')">
                <textarea
                    name="reason"
                    rows="4"
                    x-model="reasonValue"
                    :class="requiredReason
                        ? 'w-full rounded-2xl border border-amber-400 bg-amber-50 px-4 py-3 text-sm'
                        : 'w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm'"
                >{{ old('reason', $attendanceException->reason ?? '') }}</textarea>
                <p class="mt-2 text-xs" :class="requiredReason ? 'text-amber-700 font-medium' : 'text-slate-500'">
                    Jelaskan alasan exception ini dibuat.
                </p>
            </x-ui.field>

            <x-ui.field label="Notes" :error="$errors->first('notes')">
                <textarea name="notes" rows="4" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">{{ old('notes', $attendanceException->notes ?? '') }}</textarea>
            </x-ui.field>
        </div>

        <div
            x-show="showNoOverrideNotice"
            x-transition
            class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600"
        >
            Exception type ini tidak membutuhkan field override khusus. Lengkapi reason dan approval bila diperlukan.
        </div>
    </x-ui.page-section>

    <x-ui.page-section title="Approval Information" subtitle="Approval metadata dan referensi sumber.">
        <div class="grid gap-4 md:grid-cols-2">
            <x-ui.field label="Approved By Employee" :error="$errors->first('approved_by')">
                <select
                    name="approved_by"
                    x-model="approvedBy"
                    :class="approvalPairWarning
                        ? 'w-full rounded-2xl border border-sky-400 bg-sky-50 px-4 py-2.5 text-sm'
                        : 'w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm'"
                >
                    <option value="">-</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->emp_id }}" @selected((string) old('approved_by', $attendanceException->approved_by ?? '') === (string) $employee->emp_id)>
                            {{ $employee->full_name }} ({{ $employee->emp_code }})
                        </option>
                    @endforeach
                </select>
                <p class="mt-2 text-xs text-slate-500">
                    Jika approval diisi, approved by dan approved at sebaiknya diisi berpasangan.
                </p>
            </x-ui.field>

            <x-ui.field label="Approved At" :error="$errors->first('approved_at')">
                <input
                    type="datetime-local"
                    name="approved_at"
                    x-model="approvedAt"
                    :class="approvalPairWarning
                        ? 'w-full rounded-2xl border border-sky-400 bg-sky-50 px-4 py-2.5 text-sm'
                        : 'w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm'"
                >
                <p class="mt-2 text-xs text-slate-500">
                    Jika approval diisi, approved by dan approved at harus konsisten.
                </p>
            </x-ui.field>

            <x-ui.field label="Source Ref ID" :error="$errors->first('source_ref_id')">
                <input
                    type="text"
                    name="source_ref_id"
                    value="{{ $selectedSourceRefId }}"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                    placeholder="Contoh: EXC-OFF-20260505"
                >
            </x-ui.field>
        </div>
    </x-ui.page-section>
</div>

@once
    @push('scripts')
        <script>
            function attendanceExceptionForm(config = {}) {
                return {
                    exceptionType: config.exceptionType || '',
                    minutesValue: config.minutesValue || '',
                    timeValue: config.timeValue || '',
                    shiftIdValue: config.shiftIdValue || '',
                    statusValueCode: config.statusValueCode || '',
                    reasonValue: config.reasonValue || '',
                    approvedBy: config.approvedBy || '',
                    approvedAt: config.approvedAt || '',

                    init() {
                        this.normalizeIrrelevantFields(false);
                    },

                    handleTypeChange() {
                        this.normalizeIrrelevantFields(true);
                    },

                    normalizeIrrelevantFields(clearValues = true) {
                        if (!this.showMinutes && clearValues) {
                            this.minutesValue = '';
                        }

                        if (!this.showTime && clearValues) {
                            this.timeValue = '';
                        }

                        if (!this.showShift && clearValues) {
                            this.shiftIdValue = '';
                        }

                        if (!this.showStatus && clearValues) {
                            this.statusValueCode = '';
                        }
                    },

                    get requiredMinutes() {
                        return ['LATE_DISPENSATION', 'EARLY_OUT_DISPENSATION', 'OVERTIME_OVERRIDE'].includes(this.exceptionType);
                    },

                    get requiredTime() {
                        return ['MANUAL_IN', 'MANUAL_OUT'].includes(this.exceptionType);
                    },

                    get requiredShift() {
                        return this.exceptionType === 'SHIFT_OVERRIDE';
                    },

                    get requiredStatus() {
                        return ['FORCE_PRESENT', 'FORCE_ABSENT'].includes(this.exceptionType);
                    },

                    get requiredReason() {
                        return [
                            'LATE_DISPENSATION',
                            'EARLY_OUT_DISPENSATION',
                            'FORCE_PRESENT',
                            'FORCE_ABSENT',
                            'SHIFT_OVERRIDE',
                            'FORGOT_CHECKIN_APPROVAL',
                            'FORGOT_CHECKOUT_APPROVAL',
                            'MANUAL_IN',
                            'MANUAL_OUT',
                            'OVERTIME_OVERRIDE',
                        ].includes(this.exceptionType);
                    },

                    get showMinutes() {
                        return ['LATE_DISPENSATION', 'EARLY_OUT_DISPENSATION', 'OVERTIME_OVERRIDE'].includes(this.exceptionType);
                    },

                    get showTime() {
                        return ['MANUAL_IN', 'MANUAL_OUT'].includes(this.exceptionType);
                    },

                    get showShift() {
                        return this.exceptionType === 'SHIFT_OVERRIDE';
                    },

                    get showStatus() {
                        return ['FORCE_PRESENT', 'FORCE_ABSENT'].includes(this.exceptionType);
                    },

                    get showNoOverrideNotice() {
                        return this.exceptionType !== '' &&
                            !this.showMinutes &&
                            !this.showTime &&
                            !this.showShift &&
                            !this.showStatus;
                    },

                    get approvalPairWarning() {
                        return (this.approvedBy && !this.approvedAt) || (!this.approvedBy && this.approvedAt);
                    },

                    get typeHelperText() {
                        const map = {
                            LATE_DISPENSATION: 'Gunakan untuk dispensasi keterlambatan. Isi minutes value dan reason.',
                            EARLY_OUT_DISPENSATION: 'Gunakan untuk dispensasi pulang cepat. Isi minutes value dan reason.',
                            FORCE_PRESENT: 'Gunakan untuk memaksa status hadir. Isi status override dan reason.',
                            FORCE_ABSENT: 'Gunakan untuk memaksa status tidak hadir. Isi status override dan reason.',
                            SHIFT_OVERRIDE: 'Gunakan untuk mengganti shift hasil evaluasi. Isi shift override dan reason.',
                            FORGOT_CHECKIN_APPROVAL: 'Gunakan untuk approval lupa check-in. Isi reason, lalu lengkapi approval bila diperlukan.',
                            FORGOT_CHECKOUT_APPROVAL: 'Gunakan untuk approval lupa check-out. Isi reason, lalu lengkapi approval bila diperlukan.',
                            MANUAL_IN: 'Gunakan untuk input manual check-in. Isi time value dan reason.',
                            MANUAL_OUT: 'Gunakan untuk input manual check-out. Isi time value dan reason.',
                            OVERTIME_OVERRIDE: 'Gunakan untuk override menit lembur. Isi minutes value dan reason.',
                        };

                        return map[this.exceptionType] || 'Pilih exception type untuk melihat field yang relevan.';
                    },
                };
            }
        </script>
    @endpush
@endonce