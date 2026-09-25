<div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Branch</label>

            @if(isset($assignment) && $assignment?->exists)
                <input
                    type="hidden"
                    name="branch_id"
                    value="{{ old('branch_id', $assignment->branch_id) }}"
                >

                <div class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                    {{ $assignment->branch->branch_name ?? '-' }}
                </div>

                <p class="mt-2 text-xs text-slate-500">
                    Branch dikunci saat edit untuk menjaga konsistensi histori assignment.
                </p>
            @else
                <select
                    name="branch_id"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                >
                    <option value="">Select branch</option>
                    @foreach($branches as $branch)
                        <option
                            value="{{ $branch->branch_id }}"
                            @selected((string) old('branch_id', $assignment->branch_id ?? '') === (string) $branch->branch_id)
                        >
                            {{ $branch->branch_name }}
                        </option>
                    @endforeach
                </select>
            @endif

            @error('branch_id')
                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Policy</label>
            <select
                name="policy_id"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
            >
                <option value="">Select policy</option>
                @foreach($policies as $policy)
                    <option
                        value="{{ $policy->policy_id }}"
                        @selected((string) old('policy_id', $assignment->policy_id ?? '') === (string) $policy->policy_id)
                    >
                        {{ $policy->policy_name }} ({{ $policy->policy_code }})
                    </option>
                @endforeach
            </select>

            @error('policy_id')
                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Effective Start Date</label>
            <input
                type="date"
                name="effective_start_date"
                value="{{ old('effective_start_date', isset($assignment->effective_start_date) ? $assignment->effective_start_date->format('Y-m-d') : '') }}"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
            >

            @error('effective_start_date')
                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Effective End Date</label>
            <input
                type="date"
                name="effective_end_date"
                value="{{ old('effective_end_date', isset($assignment->effective_end_date) ? $assignment->effective_end_date->format('Y-m-d') : '') }}"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
            >

            @error('effective_end_date')
                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="md:col-span-2">
            <label class="mb-2 block text-sm font-medium text-slate-700">Notes</label>
            <textarea
                name="notes"
                rows="4"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
            >{{ old('notes', $assignment->notes ?? '') }}</textarea>

            @error('notes')
                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>

<div class="flex items-center gap-3">
    <button
        type="submit"
        class="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-medium text-white hover:bg-slate-800"
    >
        Save Assignment
    </button>

    <a
        href="{{ route('scheduling.branch-policy-assignments.index') }}"
        class="rounded-2xl border border-slate-300 px-5 py-3 text-sm font-medium hover:bg-slate-50"
    >
        Cancel
    </a>
</div>