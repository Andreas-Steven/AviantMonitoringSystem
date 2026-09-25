@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Add Employee Assignment"
        subtitle="Buat histori assignment organisasi baru untuk employee."
        :breadcrumbs="[
            ['label' => 'Master'],
            ['label' => 'Employee Assignments', 'url' => route('master.employee-assignments.index')],
            ['label' => 'Create'],
        ]"
    />

    @if($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            {{ $errors->first() }}
        </div>
    @endif

    <form
        method="POST"
        action="{{ route('master.employee-assignments.store') }}"
        class="space-y-6"
        x-data="{ submitting: false }"
        @submit="if (submitting) { $event.preventDefault(); return; } submitting = true"
    >
        @php
            if (!isset($assignment) && !empty($prefillEmpId) && !old('emp_id')) {
                request()->merge(['emp_id' => $prefillEmpId]);
            }
        @endphp
        @csrf
        @include('master.employee-assignments.partials.form')
    </form>
</div>
@endsection