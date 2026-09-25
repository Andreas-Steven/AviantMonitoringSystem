@extends('layouts.app')

@section('content')
<div
    class="space-y-6"
    x-data="{ submitting: false }"
>
    <x-ui.page-header
        title="Add Employee Shift Assignment"
        subtitle="Tambahkan assignment shift baru untuk employee."
        :breadcrumbs="[
            ['label' => 'Scheduling'],
            ['label' => 'Employee Shift Assignments', 'url' => route('scheduling.employee-shift-assignments.index')],
            ['label' => 'Create'],
        ]"
    />

    <form
        method="POST"
        action="{{ route('scheduling.employee-shift-assignments.store') }}"
        class="space-y-6"
        @submit="submitting = true"
    >
        @csrf
        @include('scheduling.employee-shift-assignments.partials.form')
    </form>
</div>
@endsection