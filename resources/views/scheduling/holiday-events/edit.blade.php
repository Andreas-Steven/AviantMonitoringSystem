@extends('layouts.app')

@section('content')
<form method="POST" action="{{ route('scheduling.holiday-events.update', $event->holiday_event_id) }}" class="space-y-6">
    @csrf
    @method('PUT')

    <x-ui.page-header
        title="Edit Holiday Event"
        subtitle="Perbarui event holiday."
        :breadcrumbs="[
            ['label' => 'Scheduling'],
            ['label' => 'Holiday Calendar', 'url' => route('scheduling.holiday-events.index')],
            ['label' => 'Edit'],
        ]"
    >
        <x-slot:actions>
            <x-ui.button type="submit" variant="primary">Update</x-ui.button>
            <x-ui.button type="button" variant="ghost" onclick="window.location='{{ route('scheduling.holiday-events.index') }}'">Cancel</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @include('scheduling.holiday-events._form')
</form>
@endsection