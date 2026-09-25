@extends('layouts.app')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-semibold tracking-tight">Add Payroll Period</h1>
    <p class="mt-1 text-sm text-slate-500">Tambah periode payroll</p>
</div>

<form method="POST" action="{{ route('scheduling.payroll-periods.store') }}" class="space-y-6">
    @csrf
    @include('scheduling.payroll-periods.partials.form')
</form>
@endsection