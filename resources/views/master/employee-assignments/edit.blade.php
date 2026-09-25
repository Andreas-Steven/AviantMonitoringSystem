@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Edit Employee Assignment"
        subtitle="Perbarui histori assignment organisasi yang sudah ada."
        :breadcrumbs="[
            ['label' => 'Master'],
            ['label' => 'Employee Assignments', 'url' => route('master.employee-assignments.index')],
            ['label' => 'Edit'],
        ]"
    />

    @if($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            {{ $errors->first() }}
        </div>
    @endif

    <form
        method="POST"
        action="{{ route('master.employee-assignments.update', $assignment->assignment_id) }}"
        class="space-y-6"
        x-data="{ submitting: false }"
        @submit="if (submitting) { $event.preventDefault(); return; } submitting = true"
    >
        @csrf
        @method('PUT')
        @include('master.employee-assignments.partials.form')
    </form>
</div>
@endsection