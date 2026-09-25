@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Create Overtime Request"
        subtitle="Create a new overtime request."
        :breadcrumbs="[
            ['label' => 'Requests'],
            ['label' => 'Overtime Requests', 'url' => route('requests.overtime-requests.index')],
            ['label' => 'Create'],
        ]"
    />

    <form method="POST" action="{{ route('requests.overtime-requests.store') }}" class="space-y-6">
        @csrf

        @include('requests.overtime-requests._form')

        <div class="flex justify-end gap-3">
            <x-ui.button type="button" variant="ghost" onclick="window.location='{{ route('requests.overtime-requests.index') }}'">
                Cancel
            </x-ui.button>
            <x-ui.button type="submit">
                Save Overtime Request
            </x-ui.button>
        </div>
    </form>
</div>
@endsection