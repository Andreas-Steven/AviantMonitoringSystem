@extends('layouts.app')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-semibold tracking-tight">Edit Employee Work Pattern Assignment</h1>
    <p class="mt-1 text-sm text-slate-500">Perbarui assignment work pattern per employee</p>
</div>

<form method="POST" action="{{ route('scheduling.employee-work-pattern-assignments.update', $assignment->employee_work_pattern_assignment_id) }}" class="space-y-6">
    @csrf
    @method('PUT')
    @include('scheduling.employee-work-pattern-assignments.partials.form')
</form>
@endsection