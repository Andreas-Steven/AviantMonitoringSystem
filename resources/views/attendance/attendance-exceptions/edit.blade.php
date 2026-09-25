@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Edit Attendance Exception"
        subtitle="Perbarui exception absensi yang sudah ada."
        :breadcrumbs="[
            ['label' => 'Attendance'],
            ['label' => 'Attendance Exceptions'],
            ['label' => 'Edit'],
        ]"
    />

    <form method="POST" action="{{ route('attendance.attendance-exceptions.update', $attendanceException->attendance_exception_id) }}" class="space-y-6">
        @csrf
        @method('PUT')

        @include('attendance.attendance-exceptions._form')

        <div class="flex items-center justify-end gap-3">
            <x-ui.button type="button" variant="ghost" onclick="window.location='{{ route('attendance.attendance-exceptions.show', $attendanceException->attendance_exception_id) }}'">
                Back
            </x-ui.button>
            <x-ui.button type="submit" variant="primary">
                Update Exception
            </x-ui.button>
        </div>
    </form>
</div>
@endsection