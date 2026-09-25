@extends('layouts.app')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-semibold tracking-tight">Edit Employee Shift Roster</h1>
    <p class="mt-1 text-sm text-slate-500">Perbarui roster harian employee</p>
</div>

<form method="POST" action="{{ route('scheduling.employee-shift-rosters.update', $roster->roster_id) }}" class="space-y-6">
    @csrf
    @method('PUT')
    @include('scheduling.employee-shift-rosters.partials.form')
</form>
@endsection