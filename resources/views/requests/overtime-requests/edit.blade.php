@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Edit Overtime Request"
        subtitle="Update overtime request information."
        :breadcrumbs="[
            ['label' => 'Requests'],
            ['label' => 'Overtime Requests', 'url' => route('requests.overtime-requests.index')],
            ['label' => 'Edit'],
        ]"
    />

    <form method="POST" action="{{ route('requests.overtime-requests.update', $overtimeRequest->overtime_request_id) }}" class="space-y-6">
        @csrf
        @method('PUT')

        @include('requests.overtime-requests._form')

        <div class="flex justify-end gap-3">
            <x-ui.button type="button" variant="ghost" onclick="window.location='{{ route('requests.overtime-requests.show', $overtimeRequest->overtime_request_id) }}'">
                Back
            </x-ui.button>
            <x-ui.button type="submit">
                Update Overtime Request
            </x-ui.button>
        </div>
    </form>
</div>
@endsection