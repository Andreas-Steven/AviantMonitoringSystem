@extends('layouts.app')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-semibold tracking-tight">Add Employee Work Pattern Assignment</h1>
    <p class="mt-1 text-sm text-slate-500">Tambah assignment work pattern per employee</p>
</div>

<form method="POST" action="{{ route('scheduling.employee-work-pattern-assignments.store') }}" class="space-y-6">
    @csrf
    @include('scheduling.employee-work-pattern-assignments.partials.form')
</form>
@endsection