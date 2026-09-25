@extends('layouts.app')

@section('content')
<div
    class="space-y-6"
    x-data="{ submitting: false }"
>
    <x-ui.page-header
        title="Edit Employee Shift Assignment"
        subtitle="Perbarui detail assignment shift yang sudah ada."
        :breadcrumbs="[
            ['label' => 'Scheduling'],
            ['label' => 'Employee Shift Assignments', 'url' => route('scheduling.employee-shift-assignments.index')],
            ['label' => 'Edit'],
        ]"
    />

    <form
        method="POST"
        action="{{ route('scheduling.employee-shift-assignments.update', $assignment->employee_shift_assignment_id) }}"
        class="space-y-6"
        @submit="submitting = true"
    >
        @csrf
        @method('PUT')
        @include('scheduling.employee-shift-assignments.partials.form')
    </form>
</div>
@endsection