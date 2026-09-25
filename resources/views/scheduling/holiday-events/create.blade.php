@extends('layouts.app')

@section('content')
<form method="POST" action="{{ route('scheduling.holiday-events.store') }}" class="space-y-6">
    @csrf

    <x-ui.page-header
        title="Create Holiday Event"
        subtitle="Tambahkan event holiday baru."
        :breadcrumbs="[
            ['label' => 'Scheduling'],
            ['label' => 'Holiday Calendar', 'url' => route('scheduling.holiday-events.index')],
            ['label' => 'Create'],
        ]"
    >
        <x-slot:actions>
            <x-ui.button type="submit" variant="primary">Save</x-ui.button>
            <x-ui.button type="button" variant="ghost" onclick="window.location='{{ route('scheduling.holiday-events.index') }}'">Cancel</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @include('scheduling.holiday-events._form')
</form>
@endsection