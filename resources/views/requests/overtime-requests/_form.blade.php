<div class="space-y-6">
    <x-ui.page-section title="Overtime Request Information" subtitle="Overtime request details.">
        <div class="grid gap-4 md:grid-cols-2">
            <x-ui.field label="Employee" :error="$errors->first('emp_id')">
                <select name="emp_id" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                    @foreach($employees as $employee)
                        <option value="{{ $employee->emp_id }}" @selected((string) old('emp_id', $overtimeRequest->emp_id ?? '') === (string) $employee->emp_id)>
                            {{ $employee->full_name }} ({{ $employee->emp_code }})
                        </option>
                    @endforeach
                </select>
            </x-ui.field>

            <x-ui.field label="Work Date" :error="$errors->first('work_date')">
                <input type="date" name="work_date" value="{{ old('work_date', isset($overtimeRequest) && $overtimeRequest->work_date ? $overtimeRequest->work_date->format('Y-m-d') : '') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
            </x-ui.field>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <x-ui.field label="Planned Start Datetime" :error="$errors->first('planned_start_datetime')">
                <input type="datetime-local" name="planned_start_datetime" value="{{ old('planned_start_datetime', isset($overtimeRequest) && $overtimeRequest->planned_start_datetime ? $overtimeRequest->planned_start_datetime->format('Y-m-d\TH:i') : '') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
            </x-ui.field>

            <x-ui.field label="Planned End Datetime" :error="$errors->first('planned_end_datetime')">
                <input type="datetime-local" name="planned_end_datetime" value="{{ old('planned_end_datetime', isset($overtimeRequest) && $overtimeRequest->planned_end_datetime ? $overtimeRequest->planned_end_datetime->format('Y-m-d\TH:i') : '') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
            </x-ui.field>

            <x-ui.field label="Actual Start Datetime" :error="$errors->first('actual_start_datetime')">
                <input type="datetime-local" name="actual_start_datetime" value="{{ old('actual_start_datetime', isset($overtimeRequest) && $overtimeRequest->actual_start_datetime ? $overtimeRequest->actual_start_datetime->format('Y-m-d\TH:i') : '') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
            </x-ui.field>

            <x-ui.field label="Actual End Datetime" :error="$errors->first('actual_end_datetime')">
                <input type="datetime-local" name="actual_end_datetime" value="{{ old('actual_end_datetime', isset($overtimeRequest) && $overtimeRequest->actual_end_datetime ? $overtimeRequest->actual_end_datetime->format('Y-m-d\TH:i') : '') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
            </x-ui.field>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <x-ui.field label="Reason" :error="$errors->first('reason')">
                <textarea name="reason" rows="4" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">{{ old('reason', $overtimeRequest->reason ?? '') }}</textarea>
            </x-ui.field>

            <x-ui.field label="Notes" :error="$errors->first('notes')">
                <textarea name="notes" rows="4" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">{{ old('notes', $overtimeRequest->notes ?? '') }}</textarea>
            </x-ui.field>
        </div>
    </x-ui.page-section>
</div>