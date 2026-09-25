<div class="space-y-6">
    <x-ui.page-section title="Request Information" subtitle="Employee leave request details.">
        <div class="grid gap-4 md:grid-cols-2">
            <x-ui.field label="Employee" :error="$errors->first('emp_id')">
                <select name="emp_id" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                    @foreach($employees as $employee)
                        <option value="{{ $employee->emp_id }}" @selected((string) old('emp_id', $leaveRequest->emp_id ?? '') === (string) $employee->emp_id)>
                            {{ $employee->full_name }} ({{ $employee->emp_code }})
                        </option>
                    @endforeach
                </select>
            </x-ui.field>

            <x-ui.field label="Leave Type" :error="$errors->first('leave_type_id')">
                <select name="leave_type_id" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                    @foreach($leaveTypes as $leaveType)
                        <option value="{{ $leaveType->leave_type_id }}" @selected((string) old('leave_type_id', $leaveRequest->leave_type_id ?? '') === (string) $leaveType->leave_type_id)>
                            {{ $leaveType->leave_type_name }}
                        </option>
                    @endforeach
                </select>
            </x-ui.field>

            <x-ui.field label="Start Date" :error="$errors->first('start_date')">
                <input type="date" name="start_date" value="{{ old('start_date', isset($leaveRequest) && $leaveRequest->start_date ? $leaveRequest->start_date->format('Y-m-d') : '') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
            </x-ui.field>

            <x-ui.field label="End Date" :error="$errors->first('end_date')">
                <input type="date" name="end_date" value="{{ old('end_date', isset($leaveRequest) && $leaveRequest->end_date ? $leaveRequest->end_date->format('Y-m-d') : '') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
            </x-ui.field>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <x-ui.field label="Partial Day">
                <label class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                    <input type="hidden" name="partial_day_flag" value="0">
                    <input type="checkbox" name="partial_day_flag" value="1" @checked(old('partial_day_flag', $leaveRequest->partial_day_flag ?? false))>
                    <span>Partial day request</span>
                </label>
            </x-ui.field>

            <x-ui.field label="Partial Start Time" :error="$errors->first('partial_start_time')">
                <input type="time" name="partial_start_time" value="{{ old('partial_start_time', $leaveRequest->partial_start_time ?? '') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
            </x-ui.field>

            <x-ui.field label="Partial End Time" :error="$errors->first('partial_end_time')">
                <input type="time" name="partial_end_time" value="{{ old('partial_end_time', $leaveRequest->partial_end_time ?? '') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
            </x-ui.field>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <x-ui.field label="Reason" :error="$errors->first('reason')">
                <textarea name="reason" rows="4" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">{{ old('reason', $leaveRequest->reason ?? '') }}</textarea>
            </x-ui.field>

            <x-ui.field label="Attachment URL" :error="$errors->first('attachment_url')">
                <input type="text" name="attachment_url" value="{{ old('attachment_url', $leaveRequest->attachment_url ?? '') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
            </x-ui.field>
        </div>
    </x-ui.page-section>

    <x-ui.page-section title="Internal Notes" subtitle="Catatan internal tanpa mengubah approval status langsung dari form edit umum.">
        <div class="grid gap-4 md:grid-cols-1">
            <x-ui.field label="Notes" :error="$errors->first('notes')">
                <textarea name="notes" rows="4" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">{{ old('notes', $leaveRequest->notes ?? '') }}</textarea>
            </x-ui.field>
        </div>
    </x-ui.page-section>
</div>