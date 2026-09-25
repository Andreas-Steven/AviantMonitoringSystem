@extends('layouts.app')

@section('content')
<form method="POST" action="{{ route('requests.leave-requests.store') }}" class="space-y-6">
    @csrf

    <x-ui.page-header
        title="Create Leave Request"
        subtitle="Tambahkan request cuti atau izin baru."
        :breadcrumbs="[
            ['label' => 'Requests'],
            ['label' => 'Leave Requests', 'url' => route('requests.leave-requests.index')],
            ['label' => 'Create'],
        ]"
    >
        <x-slot:actions>
            <x-ui.button type="submit" variant="primary">Save</x-ui.button>
            <x-ui.button type="button" variant="ghost" onclick="window.location='{{ route('requests.leave-requests.index') }}'">Cancel</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @include('requests.leave-requests._form')
</form>
@endsection