@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Holiday Event Detail"
        subtitle="Review holiday event and its scope."
        :breadcrumbs="[
            ['label' => 'Scheduling'],
            ['label' => 'Holiday Calendar', 'url' => route('scheduling.holiday-events.index')],
            ['label' => 'Detail'],
        ]"
    >
        <x-slot:actions>
            @if(auth()->user()->hasPermission('holiday.manage'))
                <x-ui.button
                    variant="primary"
                    onclick="window.location='{{ route('scheduling.holiday-events.edit', $event->holiday_event_id) }}'"
                >
                    Edit
                </x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.page-section title="Holiday Information">
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <div class="text-xs text-slate-500">Code</div>
                <div class="mt-1 font-medium text-slate-900">{{ $event->holiday_code }}</div>
            </div>
            <div>
                <div class="text-xs text-slate-500">Name</div>
                <div class="mt-1 font-medium text-slate-900">{{ $event->holiday_name }}</div>
            </div>
            <div>
                <div class="text-xs text-slate-500">Date</div>
                <div class="mt-1 font-medium text-slate-900">{{ optional($event->holiday_date)->format('Y-m-d') }}</div>
            </div>
            <div>
                <div class="text-xs text-slate-500">Day Type</div>
                <div class="mt-1 font-medium text-slate-900">{{ $event->dayType->day_type_name ?? $event->day_type_code }}</div>
            </div>
            <div>
                <div class="text-xs text-slate-500">Status</div>
                <div class="mt-1 font-medium text-slate-900">{{ $event->active ? 'Active' : 'Inactive' }}</div>
            </div>
            <div>
                <div class="text-xs text-slate-500">Notes</div>
                <div class="mt-1 font-medium text-slate-900">{{ $event->notes ?: '-' }}</div>
            </div>
        </div>
    </x-ui.page-section>

    <x-ui.page-section title="Scopes">
        <div class="space-y-3">
            @forelse($event->scopes as $scope)
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                    @if($scope->applies_to_all_branches)
                        <span class="font-medium text-slate-900">All Branches</span>
                    @else
                        <span class="font-medium text-slate-900">{{ $scope->branch?->branch_name ?? '-' }}</span>
                    @endif

                    @if($scope->notes)
                        <div class="mt-1 text-slate-500">{{ $scope->notes }}</div>
                    @endif
                </div>
            @empty
                <x-ui.empty-state
                    title="No scope data"
                    description="Belum ada scope yang tercatat untuk event ini."
                />
            @endforelse
        </div>
    </x-ui.page-section>
</div>
@endsection