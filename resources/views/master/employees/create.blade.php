@extends('layouts.app')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-semibold tracking-tight">Add Employee</h1>
    <p class="mt-1 text-sm text-slate-500">Tambah data karyawan baru</p>
</div>

<form method="POST" action="{{ route('master.employees.store') }}" class="space-y-6">
    @csrf
    @include('master.employees.partials.form')
</form>
@endsection