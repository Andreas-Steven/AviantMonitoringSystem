@php
    $selectedScopeMode = old(
        'scope_mode',
        isset($event) && $event->scopes->contains(fn ($scope) => $scope->applies_to_all_branches) ? 'all' : 'selected'
    );

    $selectedBranches = old(
        'branch_ids',
        isset($event)
            ? $event->scopes->where('applies_to_all_branches', false)->pluck('branch_id')->all()
            : []
    );
@endphp

<div class="space-y-6">
    <x-ui.page-section title="Holiday Information" subtitle="Master event information.">
        <div class="grid gap-4 md:grid-cols-2">
            <x-ui.field label="Holiday Code" :error="$errors->first('holiday_code')">
                <input type="text" name="holiday_code" value="{{ old('holiday_code', $event->holiday_code ?? '') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
            </x-ui.field>

            <x-ui.field label="Holiday Name" :error="$errors->first('holiday_name')">
                <input type="text" name="holiday_name" value="{{ old('holiday_name', $event->holiday_name ?? '') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
            </x-ui.field>

            <x-ui.field label="Holiday Date" :error="$errors->first('holiday_date')">
                <input type="date" name="holiday_date" value="{{ old('holiday_date', isset($event) && $event->holiday_date ? $event->holiday_date->format('Y-m-d') : '') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
            </x-ui.field>

            <x-ui.field label="Day Type" :error="$errors->first('day_type_code')">
                <select name="day_type_code" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                    @foreach($dayTypes as $dayType)
                        <option value="{{ $dayType->day_type_code }}" @selected(old('day_type_code', $event->day_type_code ?? '') === $dayType->day_type_code)>
                            {{ $dayType->day_type_name }}
                        </option>
                    @endforeach
                </select>
            </x-ui.field>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <x-ui.field label="Notes" :error="$errors->first('notes')">
                <textarea name="notes" rows="4" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">{{ old('notes', $event->notes ?? '') }}</textarea>
            </x-ui.field>

            <x-ui.field label="Status">
                <label class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                    <input type="hidden" name="active" value="0">
                    <input type="checkbox" name="active" value="1" @checked(old('active', $event->active ?? true))>
                    <span>Active</span>
                </label>
            </x-ui.field>
        </div>
    </x-ui.page-section>

    <x-ui.page-section title="Scope" subtitle="Choose whether this event applies to all branches or selected branches.">
        <div class="space-y-4">
            <div class="flex flex-wrap gap-6">
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="radio" name="scope_mode" value="all" @checked($selectedScopeMode === 'all')>
                    <span>All Branches</span>
                </label>

                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="radio" name="scope_mode" value="selected" @checked($selectedScopeMode === 'selected')>
                    <span>Selected Branches</span>
                </label>
            </div>

            <x-ui.field label="Branches" :error="$errors->first('branch_ids')">
                <select name="branch_ids[]" multiple class="w-full min-h-[180px] rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                    @foreach($branches as $branch)
                        <option value="{{ $branch->branch_id }}" @selected(in_array($branch->branch_id, $selectedBranches))>
                            {{ $branch->branch_name }}
                        </option>
                    @endforeach
                </select>
            </x-ui.field>

            <x-ui.field label="Scope Notes" :error="$errors->first('scope_notes')">
                <textarea name="scope_notes" rows="3" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">{{ old('scope_notes') }}</textarea>
            </x-ui.field>
        </div>
    </x-ui.page-section>
</div>