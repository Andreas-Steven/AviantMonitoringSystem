@extends('layouts.app')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-semibold tracking-tight">Edit Employee</h1>
    <p class="mt-1 text-sm text-slate-500">Perbarui data karyawan</p>
</div>

<form method="POST" action="{{ route('master.employees.update', $employee->emp_id) }}" class="space-y-6">
    @csrf
    @method('PUT')
    @include('master.employees.partials.form')
</form>
@endsection