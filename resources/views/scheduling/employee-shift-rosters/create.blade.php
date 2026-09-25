@extends('layouts.app')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-semibold tracking-tight">Add Employee Shift Roster</h1>
    <p class="mt-1 text-sm text-slate-500">Tambah roster harian employee</p>
</div>

<form method="POST" action="{{ route('scheduling.employee-shift-rosters.store') }}" class="space-y-6">
    @csrf
    @include('scheduling.employee-shift-rosters.partials.form')
</form>
@endsection