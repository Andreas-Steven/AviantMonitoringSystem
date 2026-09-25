@extends('layouts.app')

@section('content')
<form method="POST" action="{{ route('requests.leave-requests.update', $leaveRequest->leave_request_id) }}" class="space-y-6">
    @csrf
    @method('PUT')

    <x-ui.page-header
        title="Edit Leave Request"
        subtitle="Perbarui request cuti atau izin."
        :breadcrumbs="[
            ['label' => 'Requests'],
            ['label' => 'Leave Requests', 'url' => route('requests.leave-requests.index')],
            ['label' => 'Edit'],
        ]"
    >
        <x-slot:actions>
            <x-ui.button type="submit" variant="primary">Update</x-ui.button>
            <x-ui.button type="button" variant="ghost" onclick="window.location='{{ route('requests.leave-requests.index') }}'">Cancel</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @include('requests.leave-requests._form')
</form>
@endsection