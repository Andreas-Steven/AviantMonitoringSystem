<div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Work Pattern Code</label>
            <input type="text" name="work_pattern_code"
                value="{{ old('work_pattern_code', $workPattern->work_pattern_code ?? '') }}"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
            @error('work_pattern_code') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Work Pattern Name</label>
            <input type="text" name="work_pattern_name"
                value="{{ old('work_pattern_name', $workPattern->work_pattern_name ?? '') }}"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
            @error('work_pattern_name') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Evaluation Mode</label>
            <select name="evaluation_mode_code" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                <option value="">Select evaluation mode</option>
                @foreach($evaluationModes as $mode)
                    <option value="{{ $mode->evaluation_mode_code }}"
                        @selected(old('evaluation_mode_code', $workPattern->evaluation_mode_code ?? '') === $mode->evaluation_mode_code)>
                        {{ $mode->evaluation_mode_name }}
                    </option>
                @endforeach
            </select>
            @error('evaluation_mode_code') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Status</label>
            <select name="active" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                <option value="1" @selected((string) old('active', $workPattern->active ?? '1') === '1')>Active</option>
                <option value="0" @selected((string) old('active', $workPattern->active ?? '1') === '0')>Inactive</option>
            </select>
            @error('active') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div class="md:col-span-2">
            <label class="mb-2 block text-sm font-medium text-slate-700">Notes</label>
            <textarea name="notes" rows="4"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">{{ old('notes', $workPattern->notes ?? '') }}</textarea>
            @error('notes') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>
</div>

<div class="flex items-center gap-3">
    <button type="submit" class="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-medium text-white hover:bg-slate-800">
        Save
    </button>
    <a href="{{ route('scheduling.work-patterns.index') }}" class="rounded-2xl border border-slate-300 px-5 py-3 text-sm font-medium hover:bg-slate-50">
        Cancel
    </a>
</div>